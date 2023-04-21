<?php declare(strict_types=1);

namespace Eyecook\Blurhash\Command;

use Doctrine\DBAL\Exception as DBALException;
use Eyecook\Blurhash\Command\Concern\AcceptEntitiesArgument;
use Eyecook\Blurhash\Hash\Media\DataAbstractionLayer\HashMediaProvider;
use Eyecook\Blurhash\Hash\Media\DataAbstractionLayer\HashMediaUpdater;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Question\ConfirmationQuestion;

/**
 * CLI command to remove existing Blurhashes
 *
 * @package Eyecook\Blurhash
 * @author David Fecke (+leptoquark1)
 */
class RemoveCommand extends AbstractCommand
{
    use AcceptEntitiesArgument;

    public function __construct(
        protected readonly HashMediaProvider $hashMediaProvider,
        protected readonly HashMediaUpdater $hashMediaUpdater
    ) {
        parent::__construct('remove');
    }

    protected function configure(): void
    {
        $this->setDescription('Remove existing Blurhashes.')
            ->addOption('all', 'a', InputOption::VALUE_NONE, 'All Blurhashes will be removed. Everywhere!')
            ->addOption('dryRun', 'd', InputOption::VALUE_NONE, 'Just show how many media entities will be affected')
            ->addArgument('entities', InputArgument::IS_ARRAY, 'Restrict to specific entities. (Comma separated)', null);
    }

    /**
     * @throws DBALException
     */
    public function handle(): ?int
    {
        if ($this->input->getOption('all') === true) {
            return $this->handleRemoveAll();
        }

        if (!$this->input->getArgument('entities')) {
            $this->askForEntities();

            if ($this->input->getArgument('entities') && count($this->input->getArgument('entities')) === 0) {
                $this->ioHelper->error('Please specify a scope. If you want to delete all Blurhashes, you can provide the --all option.');

                return Command::FAILURE;
            }
        }

        return $this->handleEntities();
    }

    /**
     * @throws DBALException
     */
    protected function handleRemoveAll(): ?int
    {
        $dryRun = (bool)$this->input->getOption('dryRun');

        if ($dryRun === false) {
            $this->ioHelper->caution('This will delete all generated Blurhashes!');

            if ($this->input->hasArgument('entities') && count($this->input->getArgument('entities'))) {
                $this->ioHelper->note('Your specified entities will be ignored when using the --all option!');
            }

            $this->ioHelper->newLine();

            $questionHelper = $this->getHelper('question');
            $question = new ConfirmationQuestion('<question>Do you really want to continue?</question> [y/N]', false);

            $answer = $questionHelper->ask($this->input, $this->output, $question);

            if ($answer === false) {
                return Command::SUCCESS;
            }
        }

        $affectedResult = $this->hashMediaProvider->searchMediaIdsWithHash($this->context);
        if ($dryRun) {
            $this->ioHelper->info($affectedResult->getTotal() . ' Blurhashes would be affected.');

            return Command::SUCCESS;
        }

        $this->hashMediaUpdater->removeAllMediaHashes();
        $this->ioHelper->success($affectedResult->getTotal() . ' Blurhashes were deleted.');

        return Command::SUCCESS;
    }

    protected function handleEntities(): ?int
    {
        $folderIds = $this->getAffectedFolders();

        $criteria = new Criteria();

        $filter = $folderIds === null
            ? new EqualsFilter('mediaFolderId', null)
            : new EqualsAnyFilter('mediaFolderId', $folderIds);

        $criteria->addFilter($filter);

        $mediaIdsResult = $this->hashMediaProvider->searchMediaIdsWithHash($this->context, $criteria);

        if ($this->input->getOption('dryRun')) {
            $this->ioHelper->info($mediaIdsResult->getTotal() . ' Blurhashes would be affected.');

            return Command::SUCCESS;
        }

        if ($mediaIdsResult->getTotal() > 0) {
            $this->hashMediaUpdater->removeMediaHash($mediaIdsResult->getIds());
        }

        $this->ioHelper->success($mediaIdsResult->getTotal() . ' Blurhashes were deleted.');

        return Command::SUCCESS;
    }
}
