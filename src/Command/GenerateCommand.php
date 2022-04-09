<?php declare(strict_types=1);

namespace Eyecook\Blurhash\Command;

use Exception;
use Eyecook\Blurhash\Command\Concern\AcceptEntitiesArgument;
use Eyecook\Blurhash\Hash\Filter\NoHashFilter;
use Eyecook\Blurhash\Hash\HashMediaService;
use Eyecook\Blurhash\Hash\Media\DataAbstractionLayer\HashMediaProvider;
use Eyecook\Blurhash\Message\GenerateHashMessage;
use Shopware\Core\Content\Media\MediaCollection;
use Shopware\Core\Content\Media\MediaEntity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * CLI command for Blurhash processing
 *
 * Generate Blurhashes for your images.
 *
 * Example to regenerate all hashes for product images:
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
    use AcceptEntitiesArgument;

    protected MessageBusInterface $messageBus;
    protected EntityRepositoryInterface $mediaRepository;
    protected EntityRepositoryInterface $mediaFolderRepository;
    protected HashMediaService $hashMediaService;
    protected HashMediaProvider $hashMediaProvider;

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
        $this->setDescription('Generate Blurhashes for your images.')
            ->addOption('all', 'a', InputOption::VALUE_NONE, 'Include images that already have a hash.')
            ->addOption('sync', 's', InputOption::VALUE_NONE, 'Process the generation in this thread.')
            ->addOption('dryRun', 'd', InputOption::VALUE_NONE, 'Just show how many media entities will be affected')
            ->addArgument('entities', InputArgument::IS_ARRAY, 'Restrict to specific entities. (Comma separated)', null);
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
            $this->askForEntities('root', 'Please specify a scope. This may help speed up the generation process');
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
