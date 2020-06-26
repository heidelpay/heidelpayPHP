<?php
/**
 * This class is the base class for all integration tests of this SDK.
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
namespace heidelpayPHP\test;

use DateInterval;
use DateTime;
use heidelpayPHP\Exceptions\HeidelpayApiException;
use heidelpayPHP\Heidelpay;
use heidelpayPHP\Resources\Basket;
use heidelpayPHP\Resources\EmbeddedResources\BasketItem;
use heidelpayPHP\Resources\Payment;
use heidelpayPHP\Resources\PaymentTypes\Card;
use heidelpayPHP\Resources\PaymentTypes\Paypal;
use heidelpayPHP\Resources\PaymentTypes\SepaDirectDebit;
use heidelpayPHP\Resources\Recurring;
use heidelpayPHP\Resources\TransactionTypes\AbstractTransactionType;
use heidelpayPHP\Resources\TransactionTypes\Authorization;
use heidelpayPHP\Resources\TransactionTypes\Charge;
use heidelpayPHP\Services\EnvironmentService;
use heidelpayPHP\test\Fixtures\CustomerFixtureTrait;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\Exception;
use PHPUnit\Framework\TestCase;
use PHPUnit\Runner\BaseTestRunner;
use RuntimeException;

class BasePaymentTest extends TestCase
{
    use CustomerFixtureTrait;

    /** @var Heidelpay $heidelpay */
    protected $heidelpay;

    const RETURN_URL = 'http://dev.heidelpay.com';

    /**
     * {@inheritDoc}
     *
     * @throws RuntimeException
     */
    protected function setUp()
    {
        $privateKey = (new EnvironmentService())->getTestPrivateKey();
        $this->heidelpay = (new Heidelpay($privateKey))->setDebugHandler(new TestDebugHandler())->setDebugMode(true);
        $this->childSetup();
    }

    /**
     * If verbose test output is disabled echo debug log when test did not pass.
     *
     * {@inheritDoc}
     */
    protected function tearDown()
    {
        /** @var TestDebugHandler $debugHandler */
        $debugHandler = $this->heidelpay->getDebugHandler();

        if ($this->getStatus() === BaseTestRunner::STATUS_PASSED) {
            $debugHandler->clearTempLog();
        } else {
            echo "\n";
            $debugHandler->dumpTempLog();
            echo "\n";
        }
    }

    /**
     * Override this in the child test class to perform custom setup tasks e.g. setting a different Key.
     */
    protected function childSetup()
    {
        // do nothing here
    }

    //<editor-fold desc="Custom asserts">

    /**
     * This performs assertions to verify the tested value is an empty array.
     *
     * @param mixed $value
     *
     * @throws Exception
     */
    public function assertIsEmptyArray($value)
    {
        $this->assertIsArray($value);
        $this->assertEmpty($value);
    }

    /**
     * @param Payment $payment
     * @param float   $expectedRemaining
     * @param float   $expectedCharged
     * @param float   $expectedTotal
     * @param float   $expectedCanceled
     *
     * @throws Exception
     */
    protected function assertAmounts(
        $payment,
        $expectedRemaining,
        $expectedCharged,
        $expectedTotal,
        $expectedCanceled
    ) {
        $amount = $payment->getAmount();
        $this->assertEquals($expectedRemaining, $amount->getRemaining(), 'The remaining amount does not match.');
        $this->assertEquals($expectedCharged, $amount->getCharged(), 'The charged amount does not match.');
        $this->assertEquals($expectedTotal, $amount->getTotal(), 'The total amount does not match.');
        $this->assertEquals($expectedCanceled, $amount->getCanceled(), 'The canceled amount does not match.');
    }

    /**
     * @param mixed $transactionType
     *
     * @throws AssertionFailedError
     */
    public function assertTransactionResourceHasBeenCreated($transactionType)
    {
        $this->assertNotNull($transactionType);
        $this->assertNotEmpty($transactionType->getId());
        $this->assertNotEmpty($transactionType->getUniqueId());
        $this->assertNotEmpty($transactionType->getShortId());
    }

    /**
     * Asserts whether the given transaction was successful.
     *
     * @param AbstractTransactionType|Recurring $transaction
     *
     * @throws AssertionFailedError
     */
    protected function assertSuccess($transaction)
    {
        $this->assertTrue($transaction->isSuccess());
        $this->assertFalse($transaction->isPending());
        $this->assertFalse($transaction->isError());
    }

    /**
     * Asserts whether the given transaction was a failure.
     *
     * @param AbstractTransactionType|Recurring $transaction
     *
     * @throws AssertionFailedError
     */
    protected function assertError($transaction)
    {
        $this->assertFalse($transaction->isSuccess());
        $this->assertFalse($transaction->isPending());
        $this->assertTrue($transaction->isError());
    }

    /**
     * Asserts whether the given transaction is pending.
     *
     * @param AbstractTransactionType|Recurring $transaction
     *
     * @throws AssertionFailedError
     */
    protected function assertPending($transaction)
    {
        $this->assertFalse($transaction->isSuccess());
        $this->assertTrue($transaction->isPending());
        $this->assertFalse($transaction->isError());
    }

    //</editor-fold>

    //<editor-fold desc="Helpers">

    /**
     * Creates a Basket resource and returns it.
     *
     * @return Basket
     *
     * @throws HeidelpayApiException A HeidelpayApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException      A RuntimeException is thrown when there is an error while using the SDK.
     */
    public function createBasket(): Basket
    {
        $orderId = 'b' . self::generateRandomId();
        $basket = new Basket($orderId, 119.0, 'EUR');
        $basket->setAmountTotalVat(19.0);
        $basket->setNote('This basket is creatable!');
        $basketItem = (new BasketItem('myItem', 100.0, 100.0, 1))
            ->setBasketItemReferenceId('refId')
            ->setAmountVat(19.0)
            ->setAmountGross(119.0)
            ->setImageUrl('https://hpp-images.s3.amazonaws.com/7/bsk_0_6377B5798E5C55C6BF8B5BECA59529130226E580B050B913EAC3606DA0FF4F68.jpg');
        $basket->addBasketItem($basketItem);
        $this->heidelpay->createBasket($basket);
        return $basket;
    }

    /**
     * Creates a Card object for tests.
     *
     * @param string $cardNumber
     *
     * @return Card
     *
     * @throws RuntimeException
     * @throws \Exception
     */
    protected function createCardObject(string $cardNumber = '5453010000059543'): Card
    {
        $expiryDate = $this->getNextYearsTimestamp()->format('m/Y');
        $card = new Card($cardNumber, $expiryDate);
        $card->setCvc('123')->setCardHolder('max mustermann');
        return $card;
    }

    /**
     * Creates and returns an Authorization object with the API which can be used in test methods.
     *
     * @param float $amount
     *
     * @return Authorization
     *
     * @throws HeidelpayApiException A HeidelpayApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException      A RuntimeException is thrown when there is an error while using the SDK.
     */
    public function createCardAuthorization($amount = 100.0): Authorization
    {
        $card    = $this->heidelpay->createPaymentType($this->createCardObject());
        $orderId = microtime(true);
        return $this->heidelpay->authorize($amount, 'EUR', $card, self::RETURN_URL, null, $orderId, null, null, false);
    }

    /**
     * Creates and returns an Authorization object with the API which can be used in test methods.
     *
     * @return Authorization
     *
     * @throws HeidelpayApiException A HeidelpayApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException      A RuntimeException is thrown when there is an error while using the SDK.
     */
    public function createPaypalAuthorization(): Authorization
    {
        /** @var Paypal $paypal */
        $paypal  = $this->heidelpay->createPaymentType(new Paypal());
        $orderId = microtime(true);
        return $this->heidelpay->authorize(100.0, 'EUR', $paypal, self::RETURN_URL, null, $orderId, null, null, false);
    }

    /**
     * Creates and returns a Charge object with the API which can be used in test methods.
     *
     * @param float $amount
     *
     * @return Charge
     *
     * @throws HeidelpayApiException A HeidelpayApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException      A RuntimeException is thrown when there is an error while using the SDK.
     */
    public function createCharge($amount = 100.0): Charge
    {
        $card = $this->heidelpay->createPaymentType(new SepaDirectDebit('DE89370400440532013000'));
        return $this->heidelpay->charge($amount, 'EUR', $card, self::RETURN_URL);
    }

    /**
     * Creates and returns an order id.
     *
     * @return string
     */
    public static function generateRandomId(): string
    {
        return str_replace('.', '', microtime(true));
    }

    /**
     * Returns the current date as string in the format Y-m-d.
     *
     * @return string
     *
     * @throws \Exception
     */
    public function getTodaysDateString(): string
    {
        return (new DateTime())->format('Y-m-d');
    }

    /**
     * @return DateTime
     *
     * @throws \Exception
     */
    public function getYesterdaysTimestamp(): DateTime
    {
        return (new DateTime())->add(DateInterval::createFromDateString('yesterday'));
    }

    /**
     * @return DateTime
     *
     * @throws \Exception
     */
    public function getTomorrowsTimestamp(): DateTime
    {
        return (new DateTime())->add(DateInterval::createFromDateString('tomorrow'));
    }

    /**
     * @return DateTime
     *
     * @throws \Exception
     */
    public function getNextYearsTimestamp(): DateTime
    {
        return (new DateTime())->add(DateInterval::createFromDateString('next year'));
    }

    //</editor-fold>

    //<editor-fold desc="DataProviders">

    /**
     * Provides valid keys.
     *
     * @return array
     */
    public function validKeysDataProvider(): array
    {
        return [
            'private sandbox key' => ['s-priv-2a102ZMq3gV4I3zJ888J7RR6u75oqK3n'],
            'private production key' => ['p-priv-2a102ZMq3gV4I3zJ888J7RR6u75oqK3n']
        ];
    }

    /**
     * Provides invalid keys.
     *
     * @return array
     */
    public function invalidKeysDataProvider(): array
    {
        return [
            'public sandbox key' => ['s-pub-2a102ZMq3gV4I3zJ888J7RR6u75oqK3n'],
            'public production key' => ['p-pub-2a102ZMq3gV4I3zJ888J7RR6u75oqK3n'],
            'invalid environment' => ['t-priv-2a102ZMq3gV4I3zJ888J7RR6u75oqK3n'],
            'invalid key type' => ['s-xyz-2a102ZMq3gV4I3zJ888J7RR6u75oqK3n'],
            'invalid format 1' => ['spriv-2a102ZMq3gV4I3zJ888J7RR6u75oqK3n'],
            'invalid format 2' => ['2a102ZMq3gV4I3zJ888J7RR6u75oqK3n']
        ];
    }

    //</editor-fold>
}
