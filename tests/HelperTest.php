<?php
/**
 * User: Warlof Tutsimo <loic.leuilliot@gmail.com>
 * Date: 13/12/2016
 * Time: 22:23
 */

namespace Warlof\Seat\Slackbot\Tests;


use Orchestra\Testbench\TestCase;
use Seat\Eveapi\Models\Eve\ApiKey;
use Warlof\Seat\Slackbot\Helpers\Helper;

class HelperTest extends TestCase
{
    public function testEnabledKey()
    {
        // prepare
        $key = new ApiKey([
            'key_id' => 5458791,
            'v_code' => 'oxidjfoxdjfoidjfgojdsfg',
            'user_id' => 1,
            'enabled' => true,
            'disabled_calls' => ''
        ]);

        $key1 = new ApiKey([
            'key_id' => 5458791,
            'v_code' => 'oxidjfoxdjfoidjfgojdsfg',
            'user_id' => 1,
            'enabled' => true,
            'disabled_calls' => ''
        ]);

        $keys = collect([$key, $key1]);

        // test
        $artifact = Helper::isEnabledKey($keys);

        $this->assertTrue($artifact);
    }

    public function testDisabledKey()
    {
        // prepare
        $key = new ApiKey([
            'key_id' => 5458791,
            'v_code' => 'oxidjfoxdjfoidjfgojdsfg',
            'user_id' => 1,
            'enabled' => true,
            'disabled_calls' => ''
        ]);

        $key1 = new ApiKey([
            'key_id' => 5458791,
            'v_code' => 'oxidjfoxdjfoidjfgojdsfg',
            'user_id' => 0,
            'enabled' => false,
            'disabled_calls' => ''
        ]);

        $keys = collect([$key, $key1]);

        // test
        $artifact = Helper::isEnabledKey($keys);

        $this->assertFalse($artifact);
    }
}
