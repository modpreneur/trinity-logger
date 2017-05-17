<?php
/**
 * Created by PhpStorm.
 * User: gabriel
 * Date: 26.1.17
 * Time: 12:26
 */

namespace Trinity\Bundle\LoggerBundle\Tests\Services;

use PHPUnit\Framework\TestCase;
use Trinity\Bundle\LoggerBundle\Services\DefaultTtlProvider;

/**
 * Class DefaultTtlProviderTest
 * @package Trinity\Bundle\LoggerBundle\Tests\Services
 */
class DefaultTtlProviderTest extends TestCase
{
    public function testDefaultTtl(): void
    {
        $provider = new DefaultTtlProvider();

        static::assertEquals(0, $provider->getTtlForType('anyName'));
    }
}
