<?php declare(strict_types=1);

namespace Eyecook\Blurhash\Test\Util;

use Eyecook\Blurhash\Util\AC;
use PHPUnit\Framework\TestCase;

/**
 * @package kornrunner\Blurhash
 * @author kornrunner
 */
class ACTest extends TestCase
{
    public function testEncode(): void
    {
        $this->assertSame(3429.0, AC::encode([0, 0, 0], 1));
        $this->assertSame(6858.0, AC::encode([255, 255, 255], 1));
        $this->assertSame(0.0, AC::encode([-1, -1, -1], 1));
    }
}
