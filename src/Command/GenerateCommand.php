<?php declare(strict_types=1);
namespace EyeCook\BlurHash\Command;

use EyeCook\BlurHash\Hash\Media\MediaTypesEnum;
use EyeCook\BlurHash\Message\GenerateHashHandler;
use EyeCook\BlurHash\Message\GenerateHashMessage;
use Shopware\Core\Content\Media\Aggregate\MediaFolder\MediaFolderEntity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Bucket\TermsAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\AndFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\ContainsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NandFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NorFilter;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * CLI command for Blurhash processing
 *
 * Process or enqueue Blurhash generation for either only missing missing or renew all existing.
 *
 * Example to regenerate all hashes for only Product Images:
 *
 * ```bash
 *   bin/console ec:blurhash:generate product --all
 * ```
 *
 * @package EyeCook\BlurHash
 * @author David Fecke (+leptoquark1)
 */
class GenerateCommand extends AbstractCommand
{
    protected MessageBusInterface $messageBus;
    protected EntityRepositoryInterface $mediaRepository;
    protected EntityRepositoryInterface $mediaFolderRepository;

    public function __construct(
        MessageBusInterface $messageBus,
        EntityRepositoryInterface $mediaRepository,
        EntityRepositoryInterface $mediaFolderRepository
    ) {
        $this->messageBus = $messageBus;
        $this->mediaRepository = $mediaRepository;
        $this->mediaFolderRepository = $mediaFolderRepository;

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
        try {
            $mediaIds = $this->getAffectedMediaIds();

            return $this->processMessage($mediaIds);
        } catch (\Exception $e) {
            $this->ioHelper->error($e->getMessage());

            return 1;
        }
    }

    /**
     * @throws \Exception
     */
    protected function processMessage(array $mediaIds): int
    {
        if (count($mediaIds) === 0) {
            $this->ioHelper->info('There are no entities to process. You\'re done here');

            return 0;
        }

        if ($this->input->getOption('dryRun')) {
            $this->ioHelper->info(count($mediaIds) . '" media entities can be processed.');
        } else {
            $this->ioHelper->section('Prepare generation of ' . count($mediaIds) . ' Entities');

            if ($this->input->getOption('sync')) {
                $message = $this->createMessage($mediaIds);
                $this->generateSynchronous($message);
            } else {
                foreach (array_chunk($mediaIds, 10) as $chunk) {
                    $message = $this->createMessage($chunk);
                    $this->messageBus->dispatch($message);
                }
            }
            $this->ioHelper->success('Handled "' . count($mediaIds) . '" media entities.');
        }

        return 1;
    }

    protected function createMessage(array $mediaIds): GenerateHashMessage
    {
        $message = new GenerateHashMessage();
        $message->setMediaIds($mediaIds);
        $message->withContext($this->context);

        return $message;
    }

    /**
     * @throws \Exception
     */
    protected function generateSynchronous(GenerateHashMessage $message): void
    {
        $count = count($message->getMediaIds());
        $progressBar = $this->ioHelper->createProgressBar();
        $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% |> %name%');
        $progressBar->setMessage('', 'name');

        /** @noinspection NullPointerExceptionInspection */
        $processor = $this->container
            ->get(GenerateHashHandler::class)
            ->handleIterative($message);

        if ($processor) {
            $progressBar->start($count);
            while ($processor->valid()) {
                $itemProcessed = $processor->current();
                $progressBar->setMessage($itemProcessed['name'], 'name');
                $progressBar->advance();
                $processor->next();
            }
        }

        $this->ioHelper->newLine(2);
        $progressBar->finish();
    }

    private function getAffectedFolders(): ?array
    {
        $entities = $this->input->getArgument('entities') ?? [];

        if (count($entities) === 0) {
            return null;
        }

        $folderIds = [];
        $entityFolders = $this->aggregateEntityDefaultFolders();
        foreach ($entities as $entity) {
            if (isset($entityFolders[$entity]) === false) {
                throw new \UnexpectedValueException('Unknown entity "' . $entity . '"');
            }
            $folderIds[] = $entityFolders[$entity]['folderId'];
        }

        $tableRows = array_map(static function ($folder) {
            return [$folder['entityName'], $folder['folderName'], $folder['mediaCount']];
        }, array_filter(array_values($entityFolders), static function ($ef) use ($folderIds) {
            return in_array($ef['folderId'], $folderIds, true);
        }));

        $table = new Table($this->output);
        $table->setHeaders(['Entity', 'Folder', 'Media Files'])
            ->setHeaderTitle('Restrict to Folders:')
            ->setRows($tableRows)
            ->render();
        $this->ioHelper->newLine();

        return $folderIds;
    }

    private function getAffectedMediaIds(): array
    {
        $criteria = $this->buildMediaEntityCriteria();

        return $this->mediaRepository->searchIds($criteria, $this->context)->getIds();
    }

    private function buildMediaEntityCriteria(): Criteria
    {
        $folderIds = $this->getAffectedFolders();
        $criteria = new Criteria();
        $precludingConditions = [];
        $commensurateConditions = [];

        if (!$this->input->getOption('all')) {
            $precludingConditions[] = new ContainsFilter('metaData', 'blurhash');
            $this->ioHelper->text('<check>✔</> Generate missing hashes.');
        } else {
            $this->ioHelper->text('<check>✔</> Existing hashes will be refreshed.');
        }

        if ($this->config->isIncludedPrivate() === false) {
            $this->ioHelper->text('<check>✔</> Protected images will be skipped.');
            $commensurateConditions[] = new EqualsFilter('private', false);
        }

        if (count($this->config->getExcludedFolders()) > 0) {
            $precludingConditions[] = new EqualsAnyFilter('mediaFolderId', $this->config->getExcludedFolders());
            $this->ioHelper->text('<check>✔</> Some Folders will be skipped as specified in configuration.');
        }

        if (count($this->config->getExcludedTags()) > 0) {
            $criteria->addAssociation('tags')
                ->addFilter(new NorFilter([
                    new EqualsAnyFilter('tags.id', $this->config->getExcludedTags())
                ]));
            $this->ioHelper->text('<check>✔</> Some Tags will be skipped as specified in configuration.');
        }

        if ($folderIds !== null && count($folderIds)) {
            $commensurateConditions[] = new EqualsAnyFilter('mediaFolderId', $folderIds);
        }

        $criteria->addFilter(
            new AndFilter([
                new EqualsAnyFilter('fileExtension', MediaTypesEnum::FILE_EXTENSIONS),
                ...$commensurateConditions,
                new NorFilter($precludingConditions)
            ]),
        );

        return $criteria;
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

        /** @var \Shopware\Core\Framework\DataAbstractionLayer\Search\AggregationResult\Bucket\TermsResult $mediaInFolderCount */
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

        if ($syncMode === false && $pluginManualMode) {
            $this->ioHelper->caution('When plugin running in manual mode, asynchronous generation is disabled. You can run this synchronous by using the `--sync` option though.. ');
            exit(1);
        }
    }
}
