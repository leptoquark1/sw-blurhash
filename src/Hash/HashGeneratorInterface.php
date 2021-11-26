<?php declare(strict_types=1);

namespace Eyecook\Blurhash\Hash;

use Eyecook\Blurhash\Hash\Media\MediaHashId;

/**
 * HashGenerator Interface
 *
 * Use this interface when customizing the hash generation
 *
 * @package Eyecook\Blurhash
 * @author David Fecke (+leptoquark1)
 */
interface HashGeneratorInterface
{
    /**
     * Generates the Blurhash using the provided image data or file path and given hashId is updated.
     *
     * @param MediaHashId $hashId The referenced hash object
     * @param string $filename A string representing the absolute path to the file
     */
    public function generate(MediaHashId $hashId, string $filename): void;
}
