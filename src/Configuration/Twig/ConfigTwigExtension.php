<?php declare(strict_types=1);

namespace EyeCook\BlurHash\Configuration\Twig;

use EyeCook\BlurHash\Configuration\Config;
use EyeCook\BlurHash\Configuration\ConfigService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Twig\TwigTest;

/**
 * @package EyeCook\BlurHash
 * @author David Fecke (+leptoquark1)
 * @internal
 */
class ConfigTwigExtension extends AbstractExtension
{
    protected static ?\ReflectionClass $configRef = null;
    protected ConfigService $configService;

    public function __construct(ConfigService $configService)
    {
        $this->configService = $configService;

        if (self::$configRef === null) {
            self::$configRef = new \ReflectionClass(Config::class);
        }
    }

    public function getTests(): array
    {
        return [
            new TwigTest('ecbEqualToConfConst', [$this, 'isConfigConst']),
        ];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('ecbConfig', [$this, 'config']),
            new TwigFunction('ecbConfEqualsConst', [$this, 'isConfigConst']),
        ];
    }

    /**
     * @return array|bool|float|int|string|null
     */
    public function config(string $key)
    {
        if ((str_starts_with($key, 'is') || str_starts_with($key, 'get')) === false) {
            if (method_exists($this->configService, $accessor = 'get' . ucfirst($key))) {
                return $this->configService->$accessor();
            }

            if (method_exists($this->configService, $accessor = 'is' . ucfirst($key))) {
                return $this->configService->$accessor();
            }
        } else if (method_exists($this->configService, $key)) {
            return $this->configService->$key();
        }

        return $this->configService->getRaw($key);
    }

    public function isConfigConst(string $key, string $constName): bool
    {
        $var = $this->config($key);
        return self::$configRef->getConstant($constName) === $var ?? false;
    }
}
