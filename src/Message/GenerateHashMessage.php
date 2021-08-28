<?php declare(strict_types=1);

namespace EyeCook\BlurHash\Message;

use Shopware\Core\Framework\Context;

/**
 * Message that can be emitted to (re)generate Blurhash for specific media entities
 *
 * @package EyeCook\BlurHash
 * @author David Fecke (+leptoquark1)
 */
class GenerateHashMessage
{
    protected array $mediaIds = [];
    protected bool $ignoreManualMode = false;
    protected string $contextData;

    public function getMediaIds(): array
    {
        return $this->mediaIds;
    }

    public function setMediaIds(array $mediaIds): void
    {
        $this->mediaIds = $mediaIds;
    }

    public function isIgnoreManualMode(): bool
    {
        return $this->ignoreManualMode;
    }

    public function setIgnoreManualMode(bool $ignoreManualMode): void
    {
        $this->ignoreManualMode = $ignoreManualMode;
    }

    public function getContextData(): string
    {
        return $this->contextData;
    }

    public function setContextData(string $contextData): void
    {
        $this->contextData = $contextData;
    }

    public function withContext(Context $context): GenerateHashMessage
    {
        $this->contextData = serialize($context);

        return $this;
    }

    public function readContext(): Context
    {
        /** @noinspection UnserializeExploitsInspection */
        return unserialize($this->contextData);
    }
}
