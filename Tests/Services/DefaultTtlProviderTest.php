<?php
/**
 * Created by PhpStorm.
 * User: gabriel
 * Date: 26.1.17
 * Time: 12:26
 */

namespace Trinity\Bundle\LoggerBundle\Tests\Services;

use Trinity\Bundle\LoggerBundle\Services\DefaultTtlProvider;

/**
 * Class DefaultTtlProviderTest
 * @package Trinity\Bundle\LoggerBundle\Tests\Services
 */
class DefaultTtlProviderTest extends \PHPUnit_Framework_TestCase
{
    public function testDefaultTtl()
    {
        $provider = new DefaultTtlProvider();

        $this->assertEquals(0, $provider->getTtlForType('anyName'));
    }
}
