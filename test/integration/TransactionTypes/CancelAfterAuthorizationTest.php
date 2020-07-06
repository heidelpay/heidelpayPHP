<?php
/**
 * This class defines integration tests to verify cancellation of authorizations.
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
 * @package  heidelpayPHP\test\integration\TransactionTypes
 */
namespace heidelpayPHP\test\integration\TransactionTypes;

use heidelpayPHP\Exceptions\HeidelpayApiException;
use heidelpayPHP\Resources\TransactionTypes\Authorization;
use heidelpayPHP\test\BaseIntegrationTest;
use RuntimeException;

class CancelAfterAuthorizationTest extends BaseIntegrationTest
{
    /**
     * Verify that a full cancel on an authorization results in a cancelled payment.
     *
     * @test
     *
     * @throws HeidelpayApiException A HeidelpayApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException      A RuntimeException is thrown when there is an error while using the SDK.
     */
    public function fullCancelOnAuthorization()
    {
        $card = $this->heidelpay->createPaymentType($this->createCardObject());
        $authorization = $this->heidelpay->authorize(100.0000, 'EUR', $card, self::RETURN_URL, null, null, null, null, false);

        /** @var Authorization $fetchedAuthorization */
        $fetchedAuthorization = $this->heidelpay->fetchAuthorization($authorization->getPayment()->getId());
        $payment = $fetchedAuthorization->getPayment();
        $this->assertAmounts($payment, 100.0, 0.0, 100.0, 0.0);
        $this->assertEquals('EUR', $payment->getCurrency());
        $this->assertTrue($payment->isPending());

        $cancellation = $fetchedAuthorization->cancel();
        $secPayment = $this->heidelpay->fetchPayment($payment->getId());
        $this->assertNotEmpty($cancellation);
        $this->assertAmounts($secPayment, 0.0, 0.0, 0.0, 0.0);
        $this->assertTrue($secPayment->isCanceled());

        $traceId = $cancellation->getTraceId();
        $this->assertNotEmpty($traceId);
        $this->assertSame($traceId, $cancellation->getPayment()->getTraceId());
    }

    /**
     * Verify part cancel on an authorization.
     *
     * @test
     *
     * @throws HeidelpayApiException A HeidelpayApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException      A RuntimeException is thrown when there is an error while using the SDK.
     */
    public function partCancelOnPayment()
    {
        $card = $this->heidelpay->createPaymentType($this->createCardObject());
        $authorization = $this->heidelpay->authorize(100.0000, 'EUR', $card, self::RETURN_URL, null, null, null, null, false);
        $payment = $this->heidelpay->fetchPayment($authorization->getPayment()->getId());

        $cancelArray = $payment->cancelAmount(10.0);

        $cancel = $cancelArray[0];
        $this->assertTransactionResourceHasBeenCreated($cancel);
        $this->assertEquals(10.0, $cancel->getAmount());
    }

    /**
     * Verify part cancel after authorization.
     *
     * @test
     *
     * @throws HeidelpayApiException A HeidelpayApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException      A RuntimeException is thrown when there is an error while using the SDK.
     */
    public function partCancelOnAuthorize()
    {
        $card = $this->heidelpay->createPaymentType($this->createCardObject());
        $authorization = $this->heidelpay->authorize(100.0000, 'EUR', $card, self::RETURN_URL, null, null, null, null, false);

        /** @var Authorization $fetchedAuthorization */
        $fetchedAuthorization = $this->heidelpay->fetchAuthorization($authorization->getPayment()->getId());

        $cancel = $fetchedAuthorization->cancel(10.0);
        $this->assertTransactionResourceHasBeenCreated($cancel);
        $this->assertEquals(10.0, $cancel->getAmount());

        $payment = $this->heidelpay->fetchPayment($fetchedAuthorization->getPayment()->getId());
        $this->assertAmounts($payment, 90.0, 0.0, 90.0, 0.0);
        $this->assertTrue($payment->isPending());
    }

    /**
     * Verify a cancel can be fetched.
     *
     * @test
     *
     * @throws HeidelpayApiException A HeidelpayApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException      A RuntimeException is thrown when there is an error while using the SDK.
     */
    public function anAuthorizationsFullReversalShallBeFetchable()
    {
        $card = $this->heidelpay->createPaymentType($this->createCardObject());
        $authorization = $this->heidelpay->authorize(100.0000, 'EUR', $card, self::RETURN_URL, null, null, null, null, false);
        $payment = $authorization->getPayment();
        $this->assertAmounts($payment, 100.0, 0, 100.0, 0);
        $this->assertTrue($payment->isPending());

        $cancel = $this->heidelpay->cancelAuthorization($authorization);
        $this->assertTransactionResourceHasBeenCreated($cancel);
        $this->assertEquals(100.0, $cancel->getAmount());
        $secondPayment = $cancel->getPayment();
        $this->assertAmounts($secondPayment, 0, 0, 0, 0);
        $this->assertTrue($secondPayment->isCanceled());


        $fetchedCancel = $this->heidelpay->fetchReversalByAuthorization($authorization, $cancel->getId());
        $this->assertTransactionResourceHasBeenCreated($fetchedCancel);
        $thirdPayment = $authorization->getPayment();
        $this->assertAmounts($thirdPayment, 0, 0, 0, 0);
        $this->assertTrue($thirdPayment->isCanceled());

        $fetchedCancelSecond = $this->heidelpay->fetchReversal($authorization->getPayment()->getId(), $cancel->getId());
        $this->assertTransactionResourceHasBeenCreated($fetchedCancelSecond);
        $this->assertEquals($fetchedCancel->expose(), $fetchedCancelSecond->expose());
        $fourthPayment = $fetchedCancelSecond->getPayment();
        $this->assertAmounts($fourthPayment, 0, 0, 0, 0);
        $this->assertTrue($fourthPayment->isCanceled());
    }

    /**
     * Verify cancels can be fetched.
     *
     * @test
     *
     * @throws HeidelpayApiException A HeidelpayApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException      A RuntimeException is thrown when there is an error while using the SDK.
     */
    public function anAuthorizationsReversalsShouldBeFetchable()
    {
        $card = $this->heidelpay->createPaymentType($this->createCardObject());
        $authorization = $this->heidelpay->authorize(100.0000, 'EUR', $card, self::RETURN_URL, null, null, null, null, false);
        $payment = $authorization->getPayment();
        $this->assertAmounts($payment, 100.0, 0, 100.0, 0);
        $this->assertTrue($payment->isPending());

        $firstCancel = $this->heidelpay->cancelAuthorization($authorization, 50.0);
        $this->assertNotNull($firstCancel);
        $this->assertNotNull($firstCancel->getId());
        $this->assertEquals(50.0, $firstCancel->getAmount());
        $secondPayment = $firstCancel->getPayment();
        $this->assertAmounts($secondPayment, 50.0, 0, 50.0, 0);
        $this->assertTrue($secondPayment->isPending());
        $this->assertCount(1, $authorization->getCancellations());

        $secondCancel = $this->heidelpay->cancelAuthorization($authorization, 20.0);
        $this->assertNotNull($secondCancel);
        $this->assertNotNull($secondCancel->getId());
        $this->assertEquals(20.0, $secondCancel->getAmount());
        $thirdPayment = $secondCancel->getPayment();
        $this->assertAmounts($thirdPayment, 30.0, 0, 30.0, 0);
        $this->assertTrue($thirdPayment->isPending());
        $this->assertCount(2, $authorization->getCancellations());

        $firstCancelFetched = $this->heidelpay->fetchReversalByAuthorization($authorization, $firstCancel->getId());
        $this->assertNotNull($firstCancelFetched);
        $this->assertEquals($firstCancel->expose(), $firstCancelFetched->expose());

        $secondCancelFetched = $this->heidelpay->fetchReversalByAuthorization($authorization, $secondCancel->getId());
        $this->assertNotNull($secondCancelFetched);
        $this->assertEquals($secondCancel->expose(), $secondCancelFetched->expose());
    }
}
