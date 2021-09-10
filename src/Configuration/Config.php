<?php declare(strict_types=1);

namespace EyeCook\BlurHash\Configuration;

/**
 * @package EyeCook\BlurHash
 * @author David Fecke (+leptoquark1)
 * @internal
 */
final class Config
{
    /**
     * @internal Configuration Domain
     */
    public const PLUGIN_CONFIG_DOMAIN = 'EcBlurHash.config';

    /**#@+
     * @internal Configuration Paths
     */
    public const PATH_THUMB_THRESHOLD_HEIGHT = 'thumbnailThresholdHeight';
    public const PATH_THUMB_THRESHOLD_WIDTH = 'thumbnailThresholdWidth';
    public const PATH_EXCLUDED_FOLDERS = 'excludedFolders';
    public const PATH_INTEGRATION_MODE = 'integrationMode';
    public const PATH_MANUAL_MODE = 'manualMode';
    public const PATH_EXCLUDED_TAGS = 'excludedTags';
    public const PATH_COMPONENTS_X = 'componentsX';
    public const PATH_INCLUDE_PRIVATE = 'includePrivate';
    public const PATH_COMPONENTS_Y = 'componentsY';
    /**#@-*/

    /**#@+
     * @internal Configuration Values
     */
    public const VALUE_INTEGRATION_MODE_EMULATED = 'emulated';
    public const VALUES_INTEGRATION_MODE = [
        self::VALUE_INTEGRATION_MODE_EMULATED,
    ];
    /**#@-*/

    /**#@+
     * @internal Configuration Defaults
     */
    public const DEFAULT_TAG_NAME = 'No Blurhash';
    /**#@-*/

    /**
     * Validate configuration for integration mode.
     * Only fixed values are allowed
     * @internal
     */
    public static function validateIntegrationModeValue(): \Closure
    {
        return (static function ($value): string {
            return in_array($value, self::VALUES_INTEGRATION_MODE, true)
                ? $value
                : self::VALUE_INTEGRATION_MODE_EMULATED;
        });
    }

    /**
     * Validate configuration for component input.
     * Only values between 1-9 unsigned integer are valid
     * @internal
     */
    public static function validateComponentValue(int $default): \Closure
    {
        return (static function ($value) use ($default): int {
            return (is_int($value) === false || $value < 1 || $value > 9)
                ? $default
                : $value;
        });
    }
}
