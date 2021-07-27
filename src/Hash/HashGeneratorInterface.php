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
     * Generates the Blurhash using the provided image data and given hashId is updated.
     *
     * @param MediaHashId $hashId The referenced hash object
     * @param string|null $imageData A string containing the image data
     */
    public function generate(MediaHashId $hashId, string &$imageData): void;
}
