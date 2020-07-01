<?php
/**
 * This class defines integration tests to verify interface and
 * functionality of the payment method sofort.
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
 * @package  heidelpayPHP\test\integration\PaymentTypes
 */
namespace heidelpayPHP\test\integration\PaymentTypes;

use heidelpayPHP\Constants\ApiResponseCodes;
use heidelpayPHP\Exceptions\HeidelpayApiException;
use heidelpayPHP\Resources\PaymentTypes\Sofort;
use heidelpayPHP\Resources\TransactionTypes\Charge;
use heidelpayPHP\test\BaseIntegrationTest;
use RuntimeException;

class SofortTest extends BaseIntegrationTest
{
    /**
     * Verify sofort can be created.
     *
     * @test
     *
     * @return Sofort
     *
     * @throws HeidelpayApiException A HeidelpayApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException      A RuntimeException is thrown when there is an error while using the SDK.
     */
    public function sofortShouldBeCreatableAndFetchable(): Sofort
    {
        $sofort = $this->heidelpay->createPaymentType(new Sofort());
        $this->assertInstanceOf(Sofort::class, $sofort);
        $this->assertNotNull($sofort->getId());

        /** @var Sofort $fetchedSofort */
        $fetchedSofort = $this->heidelpay->fetchPaymentType($sofort->getId());
        $this->assertInstanceOf(Sofort::class, $fetchedSofort);
        $this->assertEquals($sofort->expose(), $fetchedSofort->expose());

        return $fetchedSofort;
    }

    /**
     * Verify sofort is chargeable.
     *
     * @test
     *
     * @param Sofort $sofort
     *
     * @return Charge
     *
     * @throws HeidelpayApiException A HeidelpayApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException      A RuntimeException is thrown when there is an error while using the SDK.
     * @depends sofortShouldBeCreatableAndFetchable
     */
    public function sofortShouldBeAbleToCharge(Sofort $sofort): Charge
    {
        $charge = $sofort->charge(100.0, 'EUR', self::RETURN_URL);
        $this->assertNotNull($charge);
        $this->assertNotEmpty($charge->getId());
        $this->assertNotEmpty($charge->getRedirectUrl());

        return $charge;
    }

    /**
     * Verify sofort is not authorizable.
     *
     * @test
     *
     * @param Sofort $sofort
     *
     * @throws HeidelpayApiException A HeidelpayApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException      A RuntimeException is thrown when there is an error while using the SDK.
     * @depends sofortShouldBeCreatableAndFetchable
     */
    public function sofortShouldNotBeAuthorizable(Sofort $sofort)
    {
        $this->expectException(HeidelpayApiException::class);
        $this->expectExceptionCode(ApiResponseCodes::API_ERROR_TRANSACTION_AUTHORIZE_NOT_ALLOWED);

        $this->heidelpay->authorize(100.0, 'EUR', $sofort, self::RETURN_URL);
    }
}
