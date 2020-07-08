<?php
/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDocMissingThrowsInspection */
/**
 * This class defines integration tests to verify interface and functionality
 * of the card payment methods e.g. Credit Card and Debit Card.
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
use heidelpayPHP\Resources\EmbeddedResources\CardDetails;
use heidelpayPHP\Resources\PaymentTypes\BasePaymentType;
use heidelpayPHP\Resources\PaymentTypes\Card;
use heidelpayPHP\Services\ValueService;
use heidelpayPHP\test\BaseIntegrationTest;

class CardTest extends BaseIntegrationTest
{
    //<editor-fold desc="Tests">

    /**
     * Verify that card payment type resource can be created.
     *
     * @test
     *
     * @dataProvider cardShouldBeCreatableDP
     *
     * @param string      $cardNumber
     * @param CardDetails $expectedCardDetails
     *
     * @return BasePaymentType
     */
    public function cardShouldBeCreatable(string $cardNumber, CardDetails $expectedCardDetails): BasePaymentType
    {
        $card = $this->createCardObject($cardNumber);
        $this->assertNull($card->getId());

        $geoLocation = $card->getGeoLocation();
        $this->assertNull($geoLocation->getClientIp());
        $this->assertNull($geoLocation->getCountryCode());

        /** @var Card $card */
        $card = $this->heidelpay->createPaymentType($card);

        $this->assertInstanceOf(Card::class, $card);
        $this->assertNotNull($card->getId());
        $this->assertSame($this->heidelpay, $card->getHeidelpayObject());
        $this->assertEquals($expectedCardDetails, $card->getCardDetails());

        $geoLocation = $card->getGeoLocation();
        $this->assertNotEmpty($geoLocation->getClientIp());
        $this->assertNotEmpty($geoLocation->getCountryCode());

        return $card;
    }

    /**
     * Verify card creation with 3ds flag set will provide the flag in transactions.
     *
     * @test
     */
    public function cardWith3dsFlagShouldSetItAlsoInTransactions(): void
    {
        $card = $this->createCardObject()->set3ds(false);
        /** @var Card $card */
        $card = $this->heidelpay->createPaymentType($card);
        $this->assertFalse($card->get3ds());

        $charge = $card->charge(12.34, 'EUR', 'https://docs.heidelpay.com');
        $this->assertFalse($charge->isCard3ds());
    }

    /**
     * Verify that the card can perform an authorization with a card.
     *
     * @test
     */
    public function cardCanPerformAuthorizationAndCreatesPayment(): void
    {
        $card = $this->createCardObject();
        /** @var Card $card */
        $card = $this->heidelpay->createPaymentType($card);

        $authorization = $card->authorize(1.0, 'EUR', self::RETURN_URL);

        // verify authorization has been created
        $this->assertNotNull($authorization->getId());

        // verify payment object has been created
        $payment = $authorization->getPayment();
        $this->assertNotNull($payment);
        $this->assertNotNull($payment->getId());

        // verify resources are linked properly
        $this->assertSame($authorization, $payment->getAuthorization());
        $this->assertSame($card, $payment->getPaymentType());

        // verify the payment object has been updated properly
        $this->assertAmounts($payment, 1.0, 0.0, 1.0, 0.0);
        $this->assertTrue($payment->isPending());
    }

    /**
     * Verify the card can perform charges and creates a payment object doing so.
     *
     * @test
     */
    public function cardCanPerformChargeAndCreatesPaymentObject(): void
    {
        $card = $this->createCardObject();
        /** @var Card $card */
        $card = $this->heidelpay->createPaymentType($card);

        // card recurring is disabled by default
        $this->assertFalse($card->isRecurring());

        $charge = $card->charge(1.0, 'EUR', self::RETURN_URL, null, null, null, null, false);

        // card recurring is activated through charge transaction
        /** @var Card $fetchedCard */
        $fetchedCard = $this->heidelpay->fetchPaymentType($card->getId());
        $this->assertTrue($fetchedCard->isRecurring());

        // verify charge has been created
        $this->assertNotNull($charge->getId());

        // verify payment object has been created
        $payment = $charge->getPayment();
        $this->assertNotNull($payment);
        $this->assertNotNull($payment->getId());

        // verify resources are linked properly
        $this->assertEquals($charge->expose(), $payment->getCharge($charge->getId())->expose());
        $this->assertSame($card, $payment->getPaymentType());

        // verify the payment object has been updated properly
        $this->assertAmounts($payment, 0.0, 1.0, 1.0, 0.0);
        $this->assertTrue($payment->isCompleted());
    }

    /**
     * Verify that a card object can be fetched from the api using its id.
     *
     * @test
     */
    public function cardCanBeFetched(): void
    {
        $card = $this->createCardObject();
        /** @var Card $card */
        $card = $this->heidelpay->createPaymentType($card);
        $this->assertNotNull($card->getId());
        $this->assertNotNull($card->getCardHolder());

        /** @var Card $fetchedCard */
        $fetchedCard = $this->heidelpay->fetchPaymentType($card->getId());
        $this->assertNotNull($fetchedCard->getId());
        $this->assertEquals(ValueService::maskValue($card->getNumber()), $fetchedCard->getNumber());
        $this->assertEquals($card->getExpiryDate(), $fetchedCard->getExpiryDate());
        $this->assertEquals('***', $fetchedCard->getCvc());
        $this->assertEquals($card->getCardHolder(), $fetchedCard->getCardHolder());
    }

    /**
     * Verify that a card object can be fetched from the api using its id.
     *
     * @test
     *
     * @deprecated since 1.2.7.2
     */
    public function cardCanBeFetchedOld(): void
    {
        $card = $this->createCardObject();
        $this->heidelpay->createPaymentType($card);
        $this->assertNotNull($card->getId());
        $this->assertNotEmpty($card->getHolder());

        /** @var Card $fetchedCard */
        $fetchedCard = $this->heidelpay->fetchPaymentType($card->getId());
        $this->assertNotNull($fetchedCard->getId());
        $this->assertEquals(ValueService::maskValue($card->getNumber()), $fetchedCard->getNumber());
        $this->assertEquals($card->getExpiryDate(), $fetchedCard->getExpiryDate());
        $this->assertEquals('***', $fetchedCard->getCvc());
        $this->assertEquals($card->getHolder(), $fetchedCard->getHolder());
    }

    /**
     * Verify the card can charge the full amount of the authorization and the payment state is updated accordingly.
     *
     * @test
     */
    public function fullChargeAfterAuthorize(): void
    {
        $card = $this->createCardObject();
        /** @var Card $card */
        $card = $this->heidelpay->createPaymentType($card);

        $authorization = $card->authorize(1.0, 'EUR', self::RETURN_URL, null, null, null, null, false);
        $payment = $authorization->getPayment();

        // pre-check to verify changes due to fullCharge call
        $this->assertAmounts($payment, 1.0, 0.0, 1.0, 0.0);
        $this->assertTrue($payment->isPending());

        $charge     = $this->heidelpay->chargeAuthorization($payment->getId());
        $paymentNew = $charge->getPayment();

        // verify payment has been updated properly
        $this->assertAmounts($paymentNew, 0.0, 1.0, 1.0, 0.0);
        $this->assertTrue($paymentNew->isCompleted());
    }

    /**
     * Verify the card can charge part of the authorized amount and the payment state is updated accordingly.
     *
     * @test
     */
    public function partialChargeAfterAuthorization(): void
    {
        $card          = $this->createCardObject();
        /** @var Card $card */
        $card          = $this->heidelpay->createPaymentType($card);
        $authorization = $this->heidelpay->authorize(
            100.0,
            'EUR',
            $card,
            self::RETURN_URL,
            null,
            null,
            null,
            null,
            false
        );

        $payment = $authorization->getPayment();
        $this->assertAmounts($payment, 100.0, 0.0, 100.0, 0.0);
        $this->assertTrue($payment->isPending());

        $charge   = $this->heidelpay->chargeAuthorization($payment->getId(), 20);
        $payment1 = $charge->getPayment();
        $this->assertAmounts($payment1, 80.0, 20.0, 100.0, 0.0);
        $this->assertTrue($payment1->isPartlyPaid());

        $charge   = $this->heidelpay->chargeAuthorization($payment->getId(), 20);
        $payment2 = $charge->getPayment();
        $this->assertAmounts($payment2, 60.0, 40.0, 100.0, 0.0);
        $this->assertTrue($payment2->isPartlyPaid());

        $charge   = $this->heidelpay->chargeAuthorization($payment->getId(), 60);
        $payment3 = $charge->getPayment();
        $this->assertAmounts($payment3, 00.0, 100.0, 100.0, 0.0);
        $this->assertTrue($payment3->isCompleted());
    }

    /**
     * Verify that an exception is thrown when trying to charge more than authorized.
     *
     * @test
     */
    public function exceptionShouldBeThrownWhenChargingMoreThenAuthorized(): void
    {
        $card          = $this->createCardObject();
        /** @var Card $card */
        $card          = $this->heidelpay->createPaymentType($card);
        $authorization = $card->authorize(100.0000, 'EUR', self::RETURN_URL, null, null, null, null, false);
        $payment       = $authorization->getPayment();
        $this->assertAmounts($payment, 100.0, 0.0, 100.0, 0.0);
        $this->assertTrue($payment->isPending());

        $charge   = $this->heidelpay->chargeAuthorization($payment->getId(), 50);
        $payment1 = $charge->getPayment();
        $this->assertAmounts($payment1, 50.0, 50.0, 100.0, 0.0);
        $this->assertTrue($payment1->isPartlyPaid());

        $this->expectException(HeidelpayApiException::class);
        $this->expectExceptionCode(ApiResponseCodes::API_ERROR_CHARGED_AMOUNT_HIGHER_THAN_EXPECTED);
        $this->heidelpay->chargeAuthorization($payment->getId(), 70);
    }

    /**
     * Verify the card payment can be charged until it is fully charged and the payment is updated accordingly.
     *
     * @test
     */
    public function partialAndFullChargeAfterAuthorization(): void
    {
        $card          = $this->createCardObject();
        /** @var Card $card */
        $card          = $this->heidelpay->createPaymentType($card);
        $authorization = $card->authorize(100.0000, 'EUR', self::RETURN_URL, null, null, null, null, false);
        $payment       = $authorization->getPayment();

        $this->assertAmounts($payment, 100.0, 0.0, 100.0, 0.0);
        $this->assertTrue($payment->isPending());

        $charge   = $this->heidelpay->chargeAuthorization($payment->getId(), 20);
        $payment1 = $charge->getPayment();
        $this->assertAmounts($payment1, 80.0, 20.0, 100.0, 0.0);
        $this->assertTrue($payment1->isPartlyPaid());

        $charge   = $this->heidelpay->chargeAuthorization($payment->getId());
        $payment2 = $charge->getPayment();
        $this->assertAmounts($payment2, 0.0, 100.0, 100.0, 0.0);
        $this->assertTrue($payment2->isCompleted());
    }

    /**
     * Authorization can be fetched.
     *
     * @test
     */
    public function authorizationShouldBeFetchable(): void
    {
        $card          = $this->createCardObject();
        /** @var Card $card */
        $card          = $this->heidelpay->createPaymentType($card);
        $authorization = $card->authorize(100.0000, 'EUR', self::RETURN_URL);
        $payment       = $authorization->getPayment();

        $fetchedAuthorization = $this->heidelpay->fetchAuthorization($payment->getId());
        $this->assertEquals($fetchedAuthorization->getId(), $authorization->getId());
    }

    /**
     * @test
     */
    public function fullCancelAfterCharge(): void
    {
        $card    = $this->createCardObject();
        /** @var Card $card */
        $card    = $this->heidelpay->createPaymentType($card);
        $charge  = $card->charge(100.0, 'EUR', self::RETURN_URL, null, null, null, null, false);
        $payment = $charge->getPayment();

        $this->assertAmounts($payment, 0.0, 100.0, 100.0, 0.0);
        $this->assertTrue($payment->isCompleted());

        $payment->cancelAmount();
        $this->assertAmounts($payment, 0.0, 0.0, 100.0, 100.0);
        $this->assertTrue($payment->isCanceled());
    }

    /**
     * Verify a card payment can be cancelled after being fully charged.
     *
     * @test
     */
    public function fullCancelOnFullyChargedPayment(): void
    {
        $card = $this->createCardObject();
        /** @var Card $card */
        $card = $this->heidelpay->createPaymentType($card);

        $authorization = $card->authorize(100.0000, 'EUR', self::RETURN_URL, null, null, null, null, false);
        $payment       = $authorization->getPayment();

        $this->assertAmounts($payment, 100.0, 0.0, 100.0, 0.0);
        $this->assertTrue($payment->isPending());

        $payment->charge(10.0);
        $this->assertAmounts($payment, 90.0, 10.0, 100.0, 0.0);
        $this->assertTrue($payment->isPartlyPaid());

        $payment->charge(90.0);
        $this->assertAmounts($payment, 0.0, 100.0, 100.0, 0.0);
        $this->assertTrue($payment->isCompleted());

        $cancellation = $payment->cancelAmount();
        $this->assertNotEmpty($cancellation);
        $this->assertAmounts($payment, 0.0, 0.0, 100.0, 100.0);
        $this->assertTrue($payment->isCanceled());
    }

    /**
     * Full cancel on partly charged auth canceled charges.
     *
     * @test
     */
    public function fullCancelOnPartlyPaidAuthWithCanceledCharges(): void
    {
        $card = $this->createCardObject();
        /** @var Card $card */
        $card = $this->heidelpay->createPaymentType($card);

        $authorization = $card->authorize(100.0000, 'EUR', self::RETURN_URL, null, null, null, null, false);
        $payment       = $authorization->getPayment();

        $payment->charge(10.0);
        $this->assertAmounts($payment, 90.0, 10.0, 100.0, 0.0);

        $charge = $payment->charge(10.0);
        $this->assertAmounts($payment, 80.0, 20.0, 100.0, 0.0);
        $this->assertTrue($payment->isPartlyPaid());

        $charge->cancel();
        $this->assertAmounts($payment, 80.0, 10.0, 100.0, 10.0);
        $this->assertTrue($payment->isPartlyPaid());

        $payment->cancelAmount();
        $this->assertTrue($payment->isCanceled());
    }

    /**
     * Verify card charge can be canceled.
     *
     * @test
     */
    public function cardChargeCanBeCanceled(): void
    {
        /** @var Card $card */
        $card   = $this->heidelpay->createPaymentType($this->createCardObject());
        $charge = $card->charge(100.0, 'EUR', self::RETURN_URL, null, null, null, null, false);

        $cancel = $charge->cancel();
        $this->assertNotNull($cancel);
        $this->assertNotEmpty($cancel->getId());
    }

    /**
     * Verify card authorize can be canceled.
     *
     * @test
     */
    public function cardAuthorizeCanBeCanceled(): void
    {
        /** @var Card $card */
        $card      = $this->heidelpay->createPaymentType($this->createCardObject());
        $authorize = $card->authorize(100.0, 'EUR', self::RETURN_URL, null, null, null, null, false);

        $cancel = $authorize->cancel();
        $this->assertNotNull($cancel);
        $this->assertNotEmpty($cancel->getId());
    }

    //</editor-fold>

    //<editor-fold desc="Data Provider">

    /**
     * @return array
     */
    public function cardShouldBeCreatableDP(): array
    {
        $cardDetailsA = new CardDetails();
        $cardDetailsAObj          = (object)[
            'cardType'          => 'CLASSIC',
            'account'           => 'CREDIT',
            'countryIsoA2'      => 'RU',
            'countryName'       => 'RUSSIAN FEDERATION',
            'issuerName'        => '',
            'issuerUrl'         => '',
            'issuerPhoneNumber' => ''
        ];
        $cardDetailsA->handleResponse($cardDetailsAObj);

        $cardDetailsB = new CardDetails();
        $cardDetailsBObj          = (object)[
            'cardType'          => '',
            'account'           => 'CREDIT',
            'countryIsoA2'      => 'US',
            'countryName'       => 'UNITED STATES',
            'issuerName'        => 'JPMORGAN CHASE BANK, N.A.',
            'issuerUrl'         => 'HTTP://WWW.JPMORGANCHASE.COM',
            'issuerPhoneNumber' => '1-212-270-6000'
        ];
        $cardDetailsB->handleResponse($cardDetailsBObj);

        return [
            'card type set'   => ['4012001037461114', $cardDetailsA],
            'issuer data set' => ['5453010000059543', $cardDetailsB]
        ];
    }

    //</editor-fold>
}
