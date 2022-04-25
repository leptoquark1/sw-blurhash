<?php declare(strict_types=1);

namespace Eyecook\Blurhash\Command;

use Eyecook\Blurhash\Hash\Filter\NoHashFilter;
use Eyecook\Blurhash\Hash\Media\DataAbstractionLayer\HashMediaProvider;
use Shopware\Core\Content\Media\Aggregate\MediaFolder\MediaFolderEntity;
use Shopware\Core\Content\Media\MediaEntity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NandFilter;
use Symfony\Component\Console\Command\Command;

/**
 * @package Eyecook\Blurhash
 * @author David Fecke (+leptoquark1)
 */
class MissingCommand extends AbstractCommand
{
    protected HashMediaProvider $hashMediaProvider;
    protected EntityRepositoryInterface $mediaFolderRepository;

    public function __construct(HashMediaProvider $hashMediaProvider, EntityRepositoryInterface $mediaFolderRepository)
    {
        $this->hashMediaProvider = $hashMediaProvider;
        $this->mediaFolderRepository = $mediaFolderRepository;

        parent::__construct('missing');
    }

    public function handle(): ?int
    {
        $mediaCriteria = new Criteria();
        $mediaCriteria->addFilter(new NoHashFilter());
        $mediaResult = $this->hashMediaProvider->searchValidMedia($this->context, $mediaCriteria);

        $folderStructure = $this->mapToFolderStructure($mediaResult);

        dd($folderStructure);

        $this->ioHelper->info('There are <red>' . $mediaResult->getTotal() . '</red> images with missing Blurhash');

        $table = $this->ioHelper->createTable();

        $table->setHeaderTitle('Folders');
        $table->setHeaders(['Name', 'Count']);

        /** @var MediaEntity $item */
        foreach ($result as $item) {
            $table->addRow([$item->getMediaFolder()->pa]);
        }

        return Command::SUCCESS;
    }

    protected function configure(): void
    {
        $this->setDescription('Output which images are valid, but have no Blurhash yet');
    }

    private function mapToFolderStructure(EntityCollection $mediaEntities): array
    {
        $structure = [
            [
                '_Root Folder',
                $mediaEntities->filter(static function (MediaEntity $media) {
                    return $media->getMediaFolderId() === null;
                })->count()
            ]
        ];
        $folderCriteria = new Criteria();
        $folderCriteria->addFilter(new NandFilter([new EqualsAnyFilter('id', $this->config->getExcludedFolders())]));
        $folderResult = $this->mediaFolderRepository->search($folderCriteria, $this->context);

        /** @var MediaFolderEntity $folder */
        foreach ($folderResult as $folder) {
            $path = [$folder->getName()];
            $currentFolder = $folder;

            while ($currentFolder->getParentId()) {
                $currentFolder = $folderResult->get($currentFolder->getParentId());

                if ($currentFolder) {
                    array_unshift($path, $currentFolder->getName());
                }
            }

            $fittingMedia = $mediaEntities->filter(static function (MediaEntity $media) use ($folder) {
                return $media->getMediaFolderId() === $folder->getId();
            });

            $structure[] = [implode(' -> ', $path), $fittingMedia->count()];
        }

        usort($structure, static function ($a, $b) {
            if ($a[0] === $b[0]) {
                return 0;
            }

            return ($a[0] < $b[0]) ? -1 : 1;
        });

        return $structure;
    }
}
