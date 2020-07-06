<?php
/**
 * This class defines integration tests to verify interface and
 * functionality of the Payment resource.
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

use heidelpayPHP\Constants\ApiResponseCodes;
use heidelpayPHP\Exceptions\HeidelpayApiException;
use heidelpayPHP\Resources\PaymentTypes\Card;
use heidelpayPHP\Resources\PaymentTypes\Paypal;
use heidelpayPHP\Resources\TransactionTypes\Authorization;
use heidelpayPHP\Resources\TransactionTypes\Charge;
use heidelpayPHP\test\BaseIntegrationTest;
use RuntimeException;

class PaymentTest extends BaseIntegrationTest
{
    /**
     * Verify fetching payment by authorization.
     *
     * @test
     *
     * @throws HeidelpayApiException
     * @throws RuntimeException
     */
    public function paymentShouldBeFetchableById(): void
    {
        $authorize = $this->createPaypalAuthorization();
        $payment = $this->heidelpay->fetchPayment($authorize->getPayment()->getId());
        $this->assertNotEmpty($payment->getId());
        $this->assertInstanceOf(Authorization::class, $payment->getAuthorization());
        $this->assertNotEmpty($payment->getAuthorization()->getId());
        $this->assertNotNull($payment->getState());

        $traceId = $authorize->getTraceId();
        $this->assertNotEmpty($traceId);
        $this->assertSame($traceId, $payment->getTraceId());
    }

    /**
     * Verify full charge on payment with authorization.
     *
     * @test
     *
     * @throws HeidelpayApiException
     * @throws RuntimeException
     */
    public function fullChargeShouldBePossibleOnPaymentObject(): void
    {
        $authorization = $this->createCardAuthorization();
        $payment = $authorization->getPayment();

        // pre-check to verify changes due to fullCharge call
        $this->assertAmounts($payment, 100.0, 0.0, 100.0, 0.0);
        $this->assertTrue($payment->isPending());

        /** @var Charge $charge */
        $charge = $payment->charge();
        $paymentNew = $charge->getPayment();

        // verify payment has been updated properly
        $this->assertAmounts($paymentNew, 0.0, 100.0, 100.0, 0.0);
        $this->assertTrue($paymentNew->isCompleted());
    }

    /**
     * Verify payment can be fetched with charges.
     *
     * @test
     *
     * @throws HeidelpayApiException
     * @throws RuntimeException
     */
    public function paymentShouldBeFetchableWithCharges(): void
    {
        $authorize = $this->createCardAuthorization();
        $payment = $authorize->getPayment();
        $this->assertNotNull($payment);
        $this->assertNotNull($payment->getId());
        $this->assertNotNull($payment->getAuthorization());
        $this->assertNotNull($payment->getAuthorization()->getId());

        $charge = $payment->charge();
        $fetchedPayment = $this->heidelpay->fetchPayment($charge->getPayment()->getId());
        $this->assertNotNull($fetchedPayment->getCharges());
        $this->assertCount(1, $fetchedPayment->getCharges());

        $fetchedCharge = $fetchedPayment->getChargeByIndex(0);
        $this->assertEquals($charge->getAmount(), $fetchedCharge->getAmount());
        $this->assertEquals($charge->getCurrency(), $fetchedCharge->getCurrency());
        $this->assertEquals($charge->getId(), $fetchedCharge->getId());
        $this->assertEquals($charge->getReturnUrl(), $fetchedCharge->getReturnUrl());
        $this->assertEquals($charge->expose(), $fetchedCharge->expose());
    }

    /**
     * Verify partial charge after authorization.
     *
     * @test
     *
     * @throws HeidelpayApiException
     * @throws RuntimeException
     */
    public function partialChargeAfterAuthorization(): void
    {
        $authorization = $this->createCardAuthorization();
        $fetchedPayment = $this->heidelpay->fetchPayment($authorization->getPayment()->getId());
        $charge = $fetchedPayment->charge(10.0);
        $this->assertNotNull($charge);
        $this->assertEquals('s-chg-1', $charge->getId());
        $this->assertEquals('10.0', $charge->getAmount());
    }

    /**
     * Verify authorization on payment.
     *
     * @test
     *
     * @throws HeidelpayApiException
     * @throws RuntimeException
     */
    public function authorizationShouldBePossibleOnHeidelpayObject(): void
    {
        /** @var Paypal $paypal */
        $paypal = $this->heidelpay->createPaymentType(new Paypal());
        $authorize = $this->heidelpay->authorize(100.0, 'EUR', $paypal, self::RETURN_URL);
        $this->assertNotNull($authorize);
        $this->assertNotEmpty($authorize->getId());
    }

    /**
     * Verify heidelpay payment charge is possible using a paymentId.
     *
     * @test
     *
     * @throws HeidelpayApiException
     * @throws RuntimeException
     */
    public function paymentChargeOnAuthorizeShouldBePossibleUsingPaymentId(): void
    {
        $card = $this->heidelpay->createPaymentType($this->createCardObject());
        $authorization = $this->heidelpay->authorize(100.00, 'EUR', $card, 'http://heidelpay.com', null, null, null, null, false);
        $charge = $this->heidelpay->chargePayment($authorization->getPaymentId());

        $this->assertNotEmpty($charge->getId());
    }

    /**
     * Verify heidelpay payment charge is possible using a paymentId and optional ids.
     *
     * @test
     *
     * @throws HeidelpayApiException
     * @throws RuntimeException
     */
    public function paymentChargeOnAuthorizeShouldTakeResourceIds(): void
    {
        $card = $this->heidelpay->createPaymentType($this->createCardObject());
        $authorization = $this->heidelpay->authorize(100.00, 'EUR', $card, 'http://heidelpay.com', null, null, null, null, false);
        $charge = $this->heidelpay->chargePayment($authorization->getPaymentId(), null, 'EUR', 'o' . self::generateRandomId(), 'i' . self::generateRandomId());

        $this->assertNotEmpty($charge->getId());
    }

    /**
     * Verify heidelpay payment charge throws an error if the id does not belong to a payment.
     *
     * @test
     *
     * @throws HeidelpayApiException
     * @throws RuntimeException
     */
    public function chargePaymentShouldThrowErrorOnNonPaymentId(): void
    {
        $this->expectException(HeidelpayApiException::class);
        $this->expectExceptionCode(ApiResponseCodes::API_ERROR_PAYMENT_NOT_FOUND);
        $this->heidelpay->chargePayment('s-crd-xlj0qhdiw40k');
    }

    /**
     * Verify a payment is fetched by orderId if the id is not set.
     *
     * @test
     *
     * @throws HeidelpayApiException
     * @throws RuntimeException
     */
    public function paymentShouldBeFetchedByOrderIdIfIdIsNotSet(): void
    {
        $orderId = str_replace(' ', '', microtime());
        $paypal = $this->heidelpay->createPaymentType(new Paypal());
        $authorization = $this->heidelpay->authorize(100.00, 'EUR', $paypal, 'http://heidelpay.com', null, $orderId, null, null, false);
        $payment = $authorization->getPayment();
        $fetchedPayment = $this->heidelpay->fetchPaymentByOrderId($orderId);

        $this->assertNotSame($payment, $fetchedPayment);
        $this->assertEquals($payment->expose(), $fetchedPayment->expose());
    }

    /**
     * Verify orderId does not need to be unique.
     *
     * @test
     *
     * @throws HeidelpayApiException
     * @throws RuntimeException
     */
    public function shouldAllowNonUniqueOrderId(): void
    {
        $orderId = 'o' . self::generateRandomId();

        /** @var Card $card */
        $card = $this->heidelpay->createPaymentType($this->createCardObject());
        $card->charge(1023, 'EUR', self::RETURN_URL, null, $orderId);

        try {
            /** @var Card $card2 */
            $card2 = $this->heidelpay->createPaymentType($this->createCardObject());
            $card2->charge(1023, 'EUR', self::RETURN_URL, null, $orderId);
            $this->assertTrue(true);
        } catch (HeidelpayApiException $e) {
            $this->assertTrue(false, "No exception expected here. ({$e->getMerchantMessage()})");
        }
    }

    /**
     * Verify invoiceId does not need to be unique.
     *
     * @test
     *
     * @throws HeidelpayApiException
     * @throws RuntimeException
     */
    public function shouldAllowNonUniqueInvoiceId(): void
    {
        $invoiceId = 'i' . self::generateRandomId();

        /** @var Card $card */
        $card = $this->heidelpay->createPaymentType($this->createCardObject());
        $card->charge(1023, 'EUR', self::RETURN_URL, null, null, null, null, null, $invoiceId);

        try {
            /** @var Card $card2 */
            $card2 = $this->heidelpay->createPaymentType($this->createCardObject());
            $card2->charge(1023, 'EUR', self::RETURN_URL, null, null, null, null, null, $invoiceId);
            $this->assertTrue(true);
        } catch (HeidelpayApiException $e) {
            $this->assertTrue(false, "No exception expected here. ({$e->getMerchantMessage()})");
        }
    }
}
