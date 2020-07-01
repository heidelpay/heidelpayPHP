<?php
/**
 * This class defines integration tests to verify keypair functionalities.
 *
 * Copyright (C) 2018 heidelpay GmbH
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * @link  https://docs.heidelpay.com/
 *
 * @author  Simon Gabriel <development@heidelpay.com>
 *
 * @package  heidelpayPHP\test\integration
 */
namespace heidelpayPHP\test\integration;

use heidelpayPHP\Exceptions\HeidelpayApiException;
use heidelpayPHP\Heidelpay;
use heidelpayPHP\test\BaseIntegrationTest;
use RuntimeException;

class KeypairTest extends BaseIntegrationTest
{
    /**
     * Validate valid keys are accepted.
     *
     * @test
     * @dataProvider validKeysDataProvider
     *
     * @param string $key
     *
     * @throws RuntimeException
     */
    public function validKeysShouldBeExcepted($key)
    {
        $heidelpay = new Heidelpay($key);
        $this->assertEquals($key, $heidelpay->getKey());
    }

    /**
     * Validate invalid keys are revoked.
     *
     * @test
     * @dataProvider invalidKeysDataProvider
     *
     * @param string $key
     *
     * @throws RuntimeException
     */
    public function invalidKeysShouldResultInException($key)
    {
        $this->expectException(RuntimeException::class);
        new Heidelpay($key);
    }

    /**
     * Verify key pair config can be fetched.
     *
     * @test
     *
     * @throws HeidelpayApiException A HeidelpayApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException      A RuntimeException is thrown when there is an error while using the SDK.
     */
    public function keypairShouldReturnExpectedValues()
    {
        $keypair = $this->heidelpay->fetchKeypair();
        $this->assertNotNull($keypair);
        $this->assertNotEmpty($keypair->getPublicKey());
        $this->assertNotEmpty($keypair->getPrivateKey());
        $this->assertNotEmpty($keypair->getAvailablePaymentTypes());
        $this->assertNotEmpty($keypair->getSecureLevel());
    }

    /**
     * Verify key pair config can be fetched with details.
     *
     * @test
     *
     * @throws HeidelpayApiException A HeidelpayApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException      A RuntimeException is thrown when there is an error while using the SDK.
     */
    public function keypairShouldBeFetchableWithDetails()
    {
        $keypair = $this->heidelpay->fetchKeypair(true);
        $this->assertNotNull($keypair);
        $this->assertNotEmpty($keypair->getPublicKey());
        $this->assertNotEmpty($keypair->getPrivateKey());
        $this->assertNotEmpty($keypair->getPaymentTypes());
        $this->assertNotEmpty($keypair->getSecureLevel());
    }
}
