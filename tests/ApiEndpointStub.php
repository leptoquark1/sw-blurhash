<?php declare(strict_types=1);

namespace Eyecook\Blurhash\Test;

use Shopware\Core\Framework\Test\TestCaseBase\AdminFunctionalTestBehaviour;
use Symfony\Component\HttpFoundation\Response;

/**
 * @package Eyecook\Blurhash\Test
 * @author David Fecke (+leptoquark1)
 */
trait ApiEndpointStub
{
    use AdminFunctionalTestBehaviour;

    /**
     * Usage:
     * ```php
     * ['response' => $response, 'content' => $content] = $this->getResponseResult($method, $url, $params);
     *
     * static::assertInstance(Response::class, $response);
     * static::assert($expectedContent, $content);
     * ```
     *
     * @return array{response: Response, content: ?string}
     */
    final protected function fetch(string $method, string $url, $parameters = []): array
    {
        $this->getBrowser()->request($method, $url, $parameters);
        $response = $this->getBrowser()->getResponse();
        try {
            $content = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            $content = null;
        }

        return [
            'response' => $response,
            'content' => $content,
        ];
    }
}
