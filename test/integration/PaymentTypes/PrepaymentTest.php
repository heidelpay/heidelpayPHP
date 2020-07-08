<?php
/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDocMissingThrowsInspection */
/**
 * This class defines integration tests to verify interface and functionality of the payment method prepayment.
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
use heidelpayPHP\Resources\AbstractHeidelpayResource;
use heidelpayPHP\Resources\PaymentTypes\Prepayment;
use heidelpayPHP\Resources\TransactionTypes\Charge;
use heidelpayPHP\test\BaseIntegrationTest;

class PrepaymentTest extends BaseIntegrationTest
{
    /**
     * Verify Prepayment can be created and fetched.
     *
     * @return Prepayment
     * @test
     */
    public function prepaymentShouldBeCreatableAndFetchable(): AbstractHeidelpayResource
    {
        $prepayment = $this->heidelpay->createPaymentType(new Prepayment());
        $this->assertInstanceOf(Prepayment::class, $prepayment);
        $this->assertNotEmpty($prepayment->getId());

        $fetchedPrepayment = $this->heidelpay->fetchPaymentType($prepayment->getId());
        $this->assertInstanceOf(Prepayment::class, $fetchedPrepayment);
        $this->assertEquals($prepayment->expose(), $fetchedPrepayment->expose());

        return $fetchedPrepayment;
    }

    /**
     * Verify authorization of prepayment type.
     *
     * @test
     *
     * @depends prepaymentShouldBeCreatableAndFetchable
     *
     * @param Prepayment $prepayment
     *
     * @return Charge
     */
    public function prepaymentTypeShouldBeChargeable(Prepayment $prepayment): Charge
    {
        $charge = $prepayment->charge(100.0, 'EUR', self::RETURN_URL);
        $this->assertNotNull($charge);
        $this->assertNotNull($charge->getId());
        $this->assertNotEmpty($charge->getIban());
        $this->assertNotEmpty($charge->getBic());
        $this->assertNotEmpty($charge->getHolder());
        $this->assertNotEmpty($charge->getDescriptor());

        return $charge;
    }

    /**
     * Verify charging a prepayment throws an exception.
     *
     * @test
     *
     * @depends prepaymentShouldBeCreatableAndFetchable
     *
     * @param Prepayment $prepayment
     */
    public function prepaymentTypeShouldNotBeAuthorizable(Prepayment $prepayment): void
    {
        $this->expectException(HeidelpayApiException::class);
        $this->expectExceptionCode(ApiResponseCodes::API_ERROR_TRANSACTION_AUTHORIZE_NOT_ALLOWED);

        $this->heidelpay->authorize(100.0, 'EUR', $prepayment, self::RETURN_URL);
    }

    /**
     * Verify shipment on a prepayment throws an exception.
     *
     * @test
     *
     * @depends prepaymentTypeShouldBeChargeable
     *
     * @param Charge $charge
     */
    public function prepaymentTypeShouldNotBeShippable(Charge $charge): void
    {
        $this->expectException(HeidelpayApiException::class);
        $this->expectExceptionCode(ApiResponseCodes::API_ERROR_TRANSACTION_SHIP_NOT_ALLOWED);

        $this->heidelpay->ship($charge->getPayment());
    }

    /**
     * Verify authorization of prepayment type.
     *
     * @test
     *
     * @depends prepaymentShouldBeCreatableAndFetchable
     *
     * @param Prepayment $prepayment
     */
    public function prepaymentChargeCanBeCanceled(Prepayment $prepayment): void
    {
        $charge = $prepayment->charge(100.0, 'EUR', self::RETURN_URL);
        $this->assertPending($charge);
        $cancellation = $charge->cancel();
        $this->assertTransactionResourceHasBeenCreated($cancellation);
    }
}
