<?php

namespace EyeCook\BlurHash\Test\Util;

use EyeCook\BlurHash\Util\DC;
use PHPUnit\Framework\TestCase;

/**
 * @package kornrunner\Blurhash
 * @author kornrunner
 */
class DCTest extends TestCase
{
    public function testEncode()
    {
        $this->assertSame(65793, DC::encode([0, 0, 0]));
        $this->assertSame(16777215, DC::encode([255, 255, 255]));
        $this->assertSame(65793, DC::encode([-1, -1, -1]));
    }
}
