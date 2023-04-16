<?php declare(strict_types=1);

namespace Eyecook\Blurhash\Command\Concern;

use Eyecook\Blurhash\Command\AbstractCommand;
use Shopware\Core\Content\Media\Aggregate\MediaFolder\MediaFolderEntity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Bucket\TermsAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\AggregationResult\AggregationResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NandFilter;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Question\ChoiceQuestion;

/**
 * @package Eyecook\Blurhash
 * @author David Fecke (+leptoquark1)
 *
 * @extends AbstractCommand
 */
trait AcceptEntitiesArgument
{
    private ?array $defaultFolders = null;

    protected function askForEntities($default = null, string $message = 'Please specify a scope'): void
    {
        $choiceMap = array_merge(['root' => null], $this->getEntityDefaultFolders());

        $choices = array_map(static function ($entry) {
            return $entry === null ? 'Root Folder' : $entry['folderName'] . ' - (' . $entry['entityName'] . ')';
        }, $choiceMap);

        $question = new ChoiceQuestion(
            $message . ":\n",
            $choices,
            $default,
        );
        $question->setMultiselect(true);

        $defaultValidator = $question->getValidator();
        $question->setValidator(static function (?string $answer) use ($defaultValidator) {
            if ($answer === null) {
                return true;
            }

            $choices = explode(',', $answer);
            if (in_array('root', $choices, true) && count($choices) >= 2) {
                throw new \Exception('"root" can not be combined with one of its subset');
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
                throw new \UnexpectedValueException('Unknown entity "' . $entity . '"');
            }
            $folderIds[] = $entityFolders[$entity]['folderId'];
        }

        $tableRows = array_map(static function ($folder) {
            return [$folder['entityName'], $folder['folderName'], $folder['mediaCount']];
        },
            array_filter(array_values($entityFolders), static function ($ef) use ($folderIds) {
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

        /** @var EntityRepository $mediaFolderRepository */
        $mediaFolderRepository = $this->container->get('media_folder.repository');

        $result = $mediaFolderRepository->search($criteria, $this->context);
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
}
