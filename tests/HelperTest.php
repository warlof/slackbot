<?php
/**
 * User: Warlof Tutsimo <loic.leuilliot@gmail.com>
 * Date: 13/12/2016
 * Time: 22:23
 */

namespace Warlof\Seat\Slackbot\Tests;


use Orchestra\Testbench\TestCase;
use Seat\Eveapi\Models\Eve\ApiKey;
use Seat\Web\Models\User;
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

    public function testVerifiedAccountWithMailActivation()
    {
        setting(['require_activation', 'yes'], true);

        $user = new User([
            'name' => 'Warlof Tutsimo',
            'email' => 'warlof@example.com',
            'password' => null,
            'mfa_token' => null,
            'active' => true,
            'account_status' => true,
            'eve_id' => null,
            'token' => null,
        ]);

        $this->assertTrue(Helper::isEnabledAccount($user));
    }

    public function testUnverifiedAccountWithMailActivation()
    {
        setting(['require_activation', 'yes'], true);

        $user = new User([
            'name' => 'Warlof Tutsimo',
            'email' => 'warlof@example.com',
            'password' => null,
            'mfa_token' => null,
            'active' => false,
            'account_status' => true,
            'eve_id' => null,
            'token' => null,
        ]);

        $this->assertFalse(Helper::isEnabledAccount($user));
    }

    public function testDisabledAccount()
    {
        setting(['require_activation', 'no'], true);

        $user = new User([
            'name' => 'Warlof Tutsimo',
            'email' => 'warlof@example.com',
            'password' => null,
            'mfa_token' => null,
            'active' => false,
            'account_status' => false,
            'eve_id' => null,
            'token' => null,
        ]);

        $this->assertFalse(Helper::isEnabledAccount($user));
    }

    public function testEnabledAccount()
    {
        setting(['require_activation', 'no'], true);

        $user = new User([
            'name' => 'Warlof Tutsimo',
            'email' => 'warlof@example.com',
            'password' => null,
            'mfa_token' => null,
            'active' => false,
            'account_status' => true,
            'eve_id' => null,
            'token' => null,
        ]);

        $this->assertTrue(Helper::isEnabledAccount($user));
    }
}
