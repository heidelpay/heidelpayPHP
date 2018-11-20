<?php
/**
 * This class defines unit tests to verify functionality of the Authorization transaction type.
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
 * @link  http://dev.heidelpay.com/
 *
 * @author  Simon Gabriel <development@heidelpay.com>
 *
 * @package  heidelpay/mgw_sdk/test/unit
 */
namespace heidelpay\MgwPhpSdk\test\unit\Resources\TransactionTypes;

use heidelpay\MgwPhpSdk\Exceptions\HeidelpayApiException;
use heidelpay\MgwPhpSdk\Heidelpay;
use heidelpay\MgwPhpSdk\Resources\Customer;
use heidelpay\MgwPhpSdk\Resources\Payment;
use heidelpay\MgwPhpSdk\Resources\PaymentTypes\Sofort;
use heidelpay\MgwPhpSdk\Resources\TransactionTypes\Authorization;
use heidelpay\MgwPhpSdk\Resources\TransactionTypes\Cancellation;
use PHPUnit\Framework\Exception;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\MockObject\RuntimeException;
use PHPUnit\Framework\TestCase;

class AuthorizationTest extends TestCase
{
    /**
     * Verify getters and setters.
     *
     * @test
     *
     * @throws ExpectationFailedException
     * @throws Exception
     */
    public function gettersAndSettersShouldWorkProperly()
    {
        $authorization = new Authorization();
        $this->assertNull($authorization->getAmount());
        $this->assertNull($authorization->getCurrency());
        $this->assertNull($authorization->getReturnUrl());

        $authorization = new Authorization(123.4, 'myCurrency', 'https://my-return-url.test');
        $this->assertEquals(123.4, $authorization->getAmount());
        $this->assertEquals('myCurrency', $authorization->getCurrency());
        $this->assertEquals('https://my-return-url.test', $authorization->getReturnUrl());

        $authorization->setAmount(567.8)->setCurrency('myNewCurrency')->setReturnUrl('https://another-return-url.test');
        $this->assertEquals(567.8, $authorization->getAmount());
        $this->assertEquals('myNewCurrency', $authorization->getCurrency());
        $this->assertEquals('https://another-return-url.test', $authorization->getReturnUrl());
    }

    /**
     * Verify that an Authorization can be updated on handle response.
     *
     * @test
     *
     * @throws Exception
     * @throws ExpectationFailedException
     * @throws \RuntimeException
     * @throws HeidelpayApiException
     */
    public function anAuthorizationShouldBeUpdatedThroughResponseHandling()
    {
        $authorization = new Authorization();
        $this->assertNull($authorization->getAmount());
        $this->assertNull($authorization->getCurrency());
        $this->assertNull($authorization->getReturnUrl());
        $this->assertNull($authorization->getIban());
        $this->assertNull($authorization->getBic());
        $this->assertNull($authorization->getHolder());
        $this->assertNull($authorization->getDescriptor());

        $authorization = new Authorization(123.4, 'myCurrency', 'https://my-return-url.test');
        $this->assertEquals(123.4, $authorization->getAmount());
        $this->assertEquals('myCurrency', $authorization->getCurrency());
        $this->assertEquals('https://my-return-url.test', $authorization->getReturnUrl());

        $testResponse = new \stdClass();
        $testResponse->amount = '789.0';
        $testResponse->currency = 'TestCurrency';
        $testResponse->returnUrl = 'https://return-url.test';
        $testResponse->Iban = 'DE89370400440532013000';
        $testResponse->Bic = 'COBADEFFXXX';
        $testResponse->Holder = 'Merchant Khang';
        $testResponse->Descriptor = '4065.6865.6416';

        $authorization->handleResponse($testResponse);
        $this->assertEquals(789.0, $authorization->getAmount());
        $this->assertEquals('TestCurrency', $authorization->getCurrency());
        $this->assertEquals('https://return-url.test', $authorization->getReturnUrl());
        $this->assertEquals('DE89370400440532013000', $authorization->getIban());
        $this->assertEquals('COBADEFFXXX', $authorization->getBic());
        $this->assertEquals('Merchant Khang', $authorization->getHolder());
        $this->assertEquals('4065.6865.6416', $authorization->getDescriptor());
    }

    /**
     * Verify path.
     *
     * @test
     * @throws Exception
     * @throws ExpectationFailedException
     */
    public function getResourcePathShouldReturnCorrectUri()
    {
        $this->assertEquals('authorize', (new Authorization())->getResourcePath());
    }

    /**
     * Verify getLinkedResources throws exception if the paymentType is not set.
     *
     * @test
     * @throws \RuntimeException
     */
    public function getLinkedResourcesShouldThrowExceptionWhenThePaymentTypeIsNotSet()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Payment type is undefined!');

        (new Authorization())->getLinkedResources();
    }

    /**
     * Verify linked resource.
     *
     * @test
     * @throws Exception
     * @throws ExpectationFailedException
     * @throws \RuntimeException
     * @throws HeidelpayApiException
     */
    public function getLinkedResourceShouldReturnResourcesBelongingToAuthorization()
    {
        $heidelpayObj    = new Heidelpay('s-priv-123345');
        $paymentType     = (new Sofort())->setId('123');
        $customer        = (new Customer('Max', 'Mustermann'))->setId('123');
        $payment         = new Payment();
        $payment->setParentResource($heidelpayObj)->setPaymentType($paymentType)->setCustomer($customer);

        $authorize       = (new Authorization())->setParentResource($payment)->setPayment($payment);
        $linkedResources = $authorize->getLinkedResources();
        $this->assertArrayHasKey('customer', $linkedResources);
        $this->assertArrayHasKey('type', $linkedResources);

        $this->assertSame($paymentType, $linkedResources['type']);
        $this->assertSame($customer, $linkedResources['customer']);
    }

    /**
     * Verify cancel() calls cancel Authorization on heidelpay object with the given amount.
     *
     * @test
     * @throws Exception
     * @throws HeidelpayApiException
     * @throws RuntimeException
     * @throws \ReflectionException
     * @throws \RuntimeException
     */
    public function cancelShouldCallCancelAuthorizationOnHeidelpayObject()
    {
        $authorization =  new Authorization();
        $heidelpayMock = $this->getMockBuilder(Heidelpay::class)
            ->disableOriginalConstructor()
            ->setMethods(['cancelAuthorization'])
            ->getMock();
        $heidelpayMock->expects($this->exactly(2))
            ->method('cancelAuthorization')->willReturn(new Cancellation())
            ->withConsecutive(
                [$this->identicalTo($authorization), $this->isNull()],
                [$this->identicalTo($authorization), 321.9]
            );

        /** @var Heidelpay $heidelpayMock */
        $authorization->setParentResource($heidelpayMock);
        $authorization->cancel();
        $authorization->cancel(321.9);
    }
}