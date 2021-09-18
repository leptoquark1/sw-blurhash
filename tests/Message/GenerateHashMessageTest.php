<?php declare(strict_types=1);

namespace Eyecook\Blurhash\Test\Message;

use Eyecook\Blurhash\Message\GenerateHashMessage;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Uuid\Uuid;

/**
 * @group MessageHandling
 * @covers \Eyecook\Blurhash\Message\GenerateHashMessage
 *
 * @package Eyecook\Blurhash\Test
 * @author David Fecke (+leptoquark1)
 */
class GenerateHashMessageTest extends TestCase
{
    protected GenerateHashMessage $message;

    public function testGetAndSetMediaIds(): void
    {
        $mediaId0 = Uuid::randomHex();
        $mediaId1 = Uuid::randomHex();
        $mediaId2 = Uuid::randomHex();
        $mediaIds = [$mediaId0, $mediaId1, $mediaId2];

        $this->message->setMediaIds($mediaIds);
        $result = $this->message->getMediaIds();

        static::assertIsArray($result);
        static::assertCount(3, $result);
        static::assertContainsEquals($mediaId0, $result);
        static::assertContainsEquals($mediaId1, $result);
        static::assertContainsEquals($mediaId2, $result);
    }

    public function testIsAndSetIgnoreManualMode(): void
    {
        $this->message->setIgnoreManualMode(true);
        static::assertTrue($this->message->isIgnoreManualMode());

        $this->message->setIgnoreManualMode(false);
        static::assertFalse($this->message->isIgnoreManualMode());
    }

    /**
     * @throws \Exception
     */
    public function testSetAndGetContextData(): void
    {
        $contextData = random_bytes(40);
        $this->message->setContextData($contextData);

        static::assertEquals($contextData, $this->message->getContextData());
    }

    /**
     * @throws \JsonException
     */
    public function testWithAndReadContext(): void
    {
        $context = Context::createDefaultContext();
        $this->message->withContext($context);

        $result = $this->message->readContext();

        static::assertEquals(
            json_encode($context->jsonSerialize(), JSON_THROW_ON_ERROR),
            json_encode($result->jsonSerialize(), JSON_THROW_ON_ERROR)
        );
        static::assertNotSame($context, $result);
    }

    protected function setUp(): void
    {
        $this->message = new GenerateHashMessage();
    }
}
