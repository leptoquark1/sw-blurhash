<?php declare(strict_types=1);

namespace Eyecook\Blurhash\Command;

use Exception;
use Eyecook\Blurhash\Hash\Filter\NoHashFilter;
use Eyecook\Blurhash\Hash\HashMediaService;
use Eyecook\Blurhash\Hash\Media\HashMediaProvider;
use Eyecook\Blurhash\Message\GenerateHashMessage;
use Shopware\Core\Content\Media\Aggregate\MediaFolder\MediaFolderEntity;
use Shopware\Core\Content\Media\MediaCollection;
use Shopware\Core\Content\Media\MediaEntity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Bucket\TermsAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\AggregationResult\AggregationResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NandFilter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Messenger\MessageBusInterface;
use UnexpectedValueException;

/**
 * CLI command for Blurhash processing
 *
 * Process or enqueue Blurhash generation for either only missing or renew all existing.
 *
 * Example to regenerate all hashes for only Product Images:
 *
 * ```bash
 *   bin/console ec:blurhash:generate product --all
 * ```
 *
 * @package Eyecook\Blurhash
 * @author David Fecke (+leptoquark1)
 */
class GenerateCommand extends AbstractCommand
{
    protected MessageBusInterface $messageBus;
    protected EntityRepositoryInterface $mediaRepository;
    protected EntityRepositoryInterface $mediaFolderRepository;
    protected HashMediaService $hashMediaService;
    protected HashMediaProvider $hashMediaProvider;
    protected ?array $defaultFolders = null;

    public function __construct(
        MessageBusInterface $messageBus,
        EntityRepositoryInterface $mediaRepository,
        EntityRepositoryInterface $mediaFolderRepository,
        HashMediaService $hashMediaService,
        HashMediaProvider $hashMediaProvider
    ) {
        $this->messageBus = $messageBus;
        $this->mediaRepository = $mediaRepository;
        $this->mediaFolderRepository = $mediaFolderRepository;
        $this->hashMediaService = $hashMediaService;
        $this->hashMediaProvider = $hashMediaProvider;

        parent::__construct('generate');
    }

    protected function configure(): void
    {
        $this->setDescription('Generate or enqueue Blurhashes for either only missing or refresh existing.')
            ->addOption('all', 'a', InputOption::VALUE_NONE, 'Include images that already have a hash.')
            ->addOption('sync', 's', InputOption::VALUE_NONE, 'Rather work now than by message worker.')
            ->addOption('dryRun', 'd', InputOption::VALUE_NONE, 'Skip generation, just show how many media entities will be affected')
            ->addArgument('entities', InputArgument::IS_ARRAY, 'Restrict to specific models. (Comma separated)', null);
    }

    protected function initializeCommand(): void
    {
        $outputStyle = new OutputFormatterStyle('#00A000', null, ['bold', 'blink']);
        $this->output->getFormatter()->setStyle('check', $outputStyle);

        $this->printCommandInfo();
    }

    public function handle(): int
    {
        if ((bool)$this->input->getOption('sync') === false && $this->config->isPluginManualMode()) {
            $this->ioHelper->caution('When plugin running in manual mode, asynchronous generation is disabled. You can run this synchronous by using the `--sync` option though.. ');

            return Command::INVALID;
        }

        if (!$this->input->getArgument('entities')) {
            $this->askForEntities();
        }

        try {
            $mediaEntities = $this->getAffectedMediaEntities();

            if ($mediaEntities->count() === 0) {
                $this->ioHelper->info('There are no entities to process. You\'re done here');

                return Command::SUCCESS;
            }

            return $this->processMessage($mediaEntities);
        } catch (Exception $e) {
            $this->ioHelper->error($e->getMessage());

            return Command::FAILURE;
        }
    }

    protected function processMessage(MediaCollection $mediaEntities): int
    {
        $count = $mediaEntities->count();
        if ($this->input->getOption('dryRun')) {
            $this->ioHelper->info($count . '" media entities can be processed.');

            return Command::SUCCESS;
        }

        $this->ioHelper->section('Prepare generation of ' . $count . ' Entities');

        if ($this->input->getOption('sync')) {
            $this->generateSynchronous($mediaEntities);
        } else {
            foreach (array_chunk($mediaEntities->getIds(), 10) as $chunk) {
                $message = new GenerateHashMessage();
                $message->setMediaIds($chunk);
                $message->withContext($this->context);

                $this->messageBus->dispatch($message);
            }
        }
        $this->ioHelper->success('Handled "' . $count . '" media entities.');

        return Command::SUCCESS;
    }

    protected function generateSynchronous(MediaCollection $mediaEntities): void
    {
        $progressBar = $this->initProcessProgressBar();
        $progressBar->start($mediaEntities->count());

        foreach ($mediaEntities->getElements() as $mediaEntity) {
            $this->preProcessUpdateProgressBar($progressBar, $mediaEntity);
            $this->hashMediaService->processHashForMedia($mediaEntity);
            $this->postProcessUpdateProgressBar($progressBar, $mediaEntity);
        }

        $progressBar->finish();
        $this->ioHelper->newLine(2);
    }

    protected function askForEntities(): void
    {
        $choiceMap = array_merge(['root' => null], $this->getEntityDefaultFolders());

        $choices = array_map(static function ($entry) {
            return $entry === null ? 'Root Folder' : $entry['folderName'] . ' - (' . $entry['entityName'] . ')';
        }, $choiceMap);

        $question = new ChoiceQuestion(
            'You have selected the root media folder as entry point. There are the following other options?',
            $choices,
            'root',
        );
        $question->setMultiselect(true);

        $defaultValidator = $question->getValidator();
        $question->setValidator(static function (string $answer) use ($defaultValidator) {
            $choices = explode(',', $answer);
            if (in_array('root', $choices, true) && count($choices) >= 2) {
                throw new Exception('"root" can not be combined with one of its subset');
            }

            return $defaultValidator($answer);
        });

        $questionHelper = $this->getHelper('question');
        $entities = $questionHelper->ask($this->input, $this->output, $question);
        $this->ioHelper->newLine(2);

        if (is_array($entities) !== false && in_array('root', $entities, true) === false) {
            $this->input->setArgument('entities', $entities);
        }
    }

    private function getAffectedFolders(): ?array
    {
        $entities = $this->input->getArgument('entities') ?? [];

        if (count($entities) === 0) {
            return null;
        }

        $folderIds = [];
        $entityFolders = $this->getEntityDefaultFolders();
        foreach ($entities as $entity) {
            if (isset($entityFolders[$entity]) === false) {
                throw new UnexpectedValueException('Unknown entity "' . $entity . '"');
            }
            $folderIds[] = $entityFolders[$entity]['folderId'];
        }

        $tableRows = array_map(static function ($folder) {
            return [$folder['entityName'], $folder['folderName'], $folder['mediaCount']];
        }, array_filter(array_values($entityFolders), static function ($ef) use ($folderIds) {
            return in_array($ef['folderId'], $folderIds, true);
        }));

        $this->ioHelper->newLine();
        $table = new Table($this->output);
        $table->setHeaders(['Entity', 'Folder', 'Media Files'])
            ->setHeaderTitle('Restrict to Folders:')
            ->setRows($tableRows)
            ->render();
        $this->ioHelper->newLine();

        return $folderIds;
    }

    private function getAffectedMediaEntities(): MediaCollection
    {
        /** @var MediaCollection $media */
        $media = $this->hashMediaProvider->searchValidMedia(
            $this->context,
            $this->buildMediaEntityCriteria()
        )->getEntities();

        return $media;
    }

    private function buildMediaEntityCriteria(): Criteria
    {
        $criteria = new Criteria();

        if (!$this->input->getOption('all')) {
            $criteria->addFilter(new NoHashFilter());
            $this->ioHelper->text('<check>✔</> Generate missing hashes.');
        } else {
            $this->ioHelper->text('<check>✔</> Existing hashes will be refreshed.');
        }

        $folderIds = $this->getAffectedFolders();
        if ($folderIds !== null && count($folderIds)) {
            $criteria->addFilter(new EqualsAnyFilter('mediaFolderId', $folderIds));
        }

        if ($this->config->isIncludedPrivate() === false) {
            $this->ioHelper->text('<check>✔</> Protected images will be skipped.');
        }

        if (count($this->config->getExcludedFolders()) > 0) {
            $this->ioHelper->text('<check>✔</> Some Folders will be skipped as specified in configuration.');
        }

        if (count($this->config->getExcludedTags()) > 0) {
            $this->ioHelper->text('<check>✔</> Some Tags will be skipped as specified in configuration.');
        }

        return $criteria;
    }

    private function getEntityDefaultFolders(): array
    {
        if ($this->defaultFolders === null) {
            $this->defaultFolders = $this->aggregateEntityDefaultFolders();
        }

        return $this->defaultFolders;
    }

    private function aggregateEntityDefaultFolders(): array
    {
        $criteria = (new Criteria())
            ->addFilter(
                new NandFilter([
                    new EqualsFilter('defaultFolderId', null)
                ]),
            )
            ->addAssociations(['defaultFolder', 'media'])
            ->addAggregation(new TermsAggregation('media-in-folder-count', 'media.mediaFolderId'));

        $result = $this->mediaFolderRepository->search($criteria, $this->context);
        $aggregations = $result->getAggregations();

        /** @var AggregationResult $mediaInFolderCount */
        $mediaInFolderCount = $aggregations->get('media-in-folder-count');

        return $result->reduce(static function ($arr, MediaFolderEntity $e) use ($mediaInFolderCount) {
            $defaultFolder = $e->getDefaultFolder();
            $entity = $defaultFolder ? $defaultFolder->getEntity() : '';
            $bucket = $mediaInFolderCount->get($e->getId());

            $arr[$entity] = [
                'folderId' => $e->getId(),
                'folderName' => $e->getName(),
                'entityName' => $entity,
                'mediaCount' => ($bucket ? $bucket->getCount() : 0),
            ];

            return $arr;
        }, []);
    }

    private function printCommandInfo(): void
    {
        $syncMode = (bool)$this->input->getOption('sync');
        $pluginManualMode = $this->config->isPluginManualMode();
        $dryRun = (bool)$this->input->getOption('dryRun');

        $this->ioHelper->title('Prepare and ' . ($syncMode ? 'generate' : 'enqueue') . ' Hashes');

        $infoMessages = [];
        !$dryRun && $infoMessages[] = '» Generation will be ' . ($syncMode ? 'synchronous' : 'asynchronous');
        $pluginManualMode && $infoMessages[] = '» Plugin is running in Manual Mode.';

        if (count($infoMessages) > 0) {
            $this->ioHelper->block(implode(PHP_EOL, $infoMessages), 'INFO', 'fg=green', ' ', false);
        }
    }

    private function initProcessProgressBar(): ProgressBar
    {
        $progressBar = $this->ioHelper->createProgressBar();
        $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% |> Current: %currentName% |> Last: %lastName% (Mem: %memory:6s%, tØ: %apt:s% sec)');
        $progressBar->setMessage('0', 'apt');
        $progressBar->setMessage('-', 'lastName');

        return $progressBar;
    }

    private function preProcessUpdateProgressBar(ProgressBar $progressBar, MediaEntity $entity): void
    {
        $progressBar->setMessage($entity->getTitle() ?? $entity->getFileName(), 'currentName');
        $progressBar->display();
    }

    private function postProcessUpdateProgressBar(ProgressBar $progressBar, MediaEntity $entity): void
    {
        $avgProcessTime = ((time() - $progressBar->getStartTime()) / ($progressBar->getProgress() + 1));
        $progressBar->setMessage((string)$avgProcessTime, 'apt');
        $progressBar->setMessage($entity->getTitle() ?? $entity->getFileName(), 'lastName');
        $progressBar->advance();
    }
}
