<?php
/**
 * This class defines unit tests to verify functionality of the Payment resource.
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
 * @package  heidelpayPHP\test\unit
 */
namespace heidelpayPHP\test\unit\Resources;

use heidelpayPHP\Constants\PaymentState;
use heidelpayPHP\Exceptions\HeidelpayApiException;
use heidelpayPHP\Heidelpay;
use heidelpayPHP\Interfaces\ResourceServiceInterface;
use heidelpayPHP\Resources\Basket;
use heidelpayPHP\Resources\Customer;
use heidelpayPHP\Resources\CustomerFactory;
use heidelpayPHP\Resources\EmbeddedResources\Amount;
use heidelpayPHP\Resources\Metadata;
use heidelpayPHP\Resources\Payment;
use heidelpayPHP\Resources\PaymentTypes\Sofort;
use heidelpayPHP\Resources\TransactionTypes\Authorization;
use heidelpayPHP\Resources\TransactionTypes\Cancellation;
use heidelpayPHP\Resources\TransactionTypes\Charge;
use heidelpayPHP\Resources\TransactionTypes\Payout;
use heidelpayPHP\Resources\TransactionTypes\Shipment;
use heidelpayPHP\Services\ResourceService;
use heidelpayPHP\test\BasePaymentTest;
use PHPUnit\Framework\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionException;
use RuntimeException;
use stdClass;

class PaymentTest extends BasePaymentTest
{
    /**
     * Verify getters and setters work properly.
     *
     * @test
     *
     * @throws HeidelpayApiException A HeidelpayApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException      A RuntimeException is thrown when there is an error while using the SDK.
     */
    public function gettersAndSettersShouldWorkProperly()
    {
        // initial check
        $payment = (new Payment())->setParentResource(new Heidelpay('s-priv-1234'));
        $this->assertNull($payment->getRedirectUrl());
        $this->assertNull($payment->getCustomer());
        /** @noinspection UnnecessaryAssertionInspection */
        $this->assertInstanceOf(Amount::class, $payment->getAmount());
        $this->assertNull($payment->getTraceId());

        // update
        $ids = (object)['traceId' => 'myTraceId'];
        $payment->handleResponse((object)['redirectUrl' => 'https://my-redirect-url.test', 'processing' => $ids]);
        $authorize = new Authorization();
        $payment->setAuthorization($authorize);
        $payout = new Payout();
        $payment->setPayout($payout);

        // check
        $this->assertEquals('https://my-redirect-url.test', $payment->getRedirectUrl());
        $this->assertSame($authorize, $payment->getAuthorization(true));
        $this->assertSame($payout, $payment->getPayout(true));
        $this->assertSame('myTraceId', $payment->getTraceId());
    }

    /**
     * @test
     *
     * @throws HeidelpayApiException A HeidelpayApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException      A RuntimeException is thrown when there is an error while using the SDK.
     *
     * Todo: Workaround to be removed when API sends TraceID in processing-group
     */
    public function checkTraceIdWorkaround()
    {
        // initial check
        $payment = (new Payment())->setParentResource(new Heidelpay('s-priv-1234'));
        $this->assertNull($payment->getTraceId());

        // update
        $payment->handleResponse((object)['resources' => (object)['traceId' => 'myTraceId']]);

        // check
        $this->assertSame('myTraceId', $payment->getTraceId());
    }

    /**
     * Verify getAuthorization should try to fetch resource if lazy loading is off and the authorization is not null.
     *
     * @test
     *
     * @throws ReflectionException
     * @throws HeidelpayApiException A HeidelpayApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException      A RuntimeException is thrown when there is an error while using the SDK.
     */
    public function getAuthorizationShouldFetchAuthorizeIfNotLazyAndAuthIsNotNull()
    {
        $payment = (new Payment())->setId('myPaymentId');
        $authorization = new Authorization();
        $payment->setAuthorization($authorization);

        $resourceServiceMock = $this->getMockBuilder(ResourceService::class)
            ->disableOriginalConstructor()->setMethods(['getResource'])->getMock();
        $resourceServiceMock->expects($this->once())->method('getResource')->with($authorization);

        /** @var ResourceServiceInterface $resourceServiceMock */
        $heidelpayObj = (new Heidelpay('s-priv-123'))->setResourceService($resourceServiceMock);
        $payment->setParentResource($heidelpayObj);

        $payment->getAuthorization();
    }

    /**
     * Verify getAuthorization should try to fetch resource if lazy loading is off and the authorization is not null.
     *
     * @test
     *
     * @throws ReflectionException
     * @throws HeidelpayApiException A HeidelpayApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException      A RuntimeException is thrown when there is an error while using the SDK.
     */
    public function getAuthorizationShouldNotFetchAuthorizeIfNotLazyAndAuthIsNull()
    {
        $payment = (new Payment())->setId('myPaymentId');

        $resourceServiceMock = $this->getMockBuilder(ResourceService::class)
            ->disableOriginalConstructor()->setMethods(['getResource'])->getMock();
        $resourceServiceMock->expects($this->never())->method('getResource');

        /** @var ResourceServiceInterface $resourceServiceMock */
        $heidelpayObj = (new Heidelpay('s-priv-123'))->setResourceService($resourceServiceMock);
        $payment->setParentResource($heidelpayObj);

        $payment->getAuthorization();
    }

    /**
     * Verify Charge array is handled properly.
     *
     * @test
     *
     * @throws HeidelpayApiException A HeidelpayApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException      A RuntimeException is thrown when there is an error while using the SDK.
     */
    public function chargesShouldBeHandledProperly()
    {
        $payment = new Payment();
        $this->assertIsEmptyArray($payment->getCharges());

        $charge1 = (new Charge())->setId('firstCharge');
        $charge2 = (new Charge())->setId('secondCharge');

        $subset[] = $charge1;
        $payment->addCharge($charge1);
        $this->assertArraySubset($subset, $payment->getCharges());

        $subset[] = $charge2;
        $payment->addCharge($charge2);
        $this->assertArraySubset($subset, $payment->getCharges());

        $this->assertSame($charge2, $payment->getCharge('secondCharge', true));
        $this->assertSame($charge1, $payment->getCharge('firstCharge', true));

        $this->assertSame($charge1, $payment->getChargeByIndex(0, true));
        $this->assertSame($charge2, $payment->getChargeByIndex(1, true));
    }

    /**
     * Verify getChargeById will fetch the Charge if lazy loading is off and the charge exists.
     *
     * @test
     *
     * @throws RuntimeException
     * @throws ReflectionException
     * @throws HeidelpayApiException
     */
    public function getChargeByIdShouldFetchChargeIfItExistsAndLazyLoadingIsOff()
    {
        $payment = (new Payment())->setId('myPaymentId');
        $charge1 = (new Charge())->setId('firstCharge');
        $charge2 = (new Charge())->setId('secondCharge');

        $payment->addCharge($charge1);
        $payment->addCharge($charge2);

        $resourceServiceMock = $this->getMockBuilder(ResourceService::class)
            ->disableOriginalConstructor()->setMethods(['getResource'])->getMock();
        $resourceServiceMock->expects($this->exactly(2))
            ->method('getResource')
            ->withConsecutive([$charge1], [$charge2]);

        /** @var ResourceServiceInterface $resourceServiceMock */
        $heidelpayObj = (new Heidelpay('s-priv-123'))->setResourceService($resourceServiceMock);
        $payment->setParentResource($heidelpayObj);

        $payment->getCharge('firstCharge');
        $payment->getCharge('secondCharge');
    }

    /**
     * Verify getCharge will fetch the Charge if lazy loading is off and the charge exists.
     *
     * @test
     *
     * @throws RuntimeException
     * @throws ReflectionException
     * @throws HeidelpayApiException
     */
    public function getChargeShouldFetchChargeIfItExistsAndLazyLoadingIsOff()
    {
        $payment = (new Payment())->setId('myPaymentId');
        $charge1 = (new Charge())->setId('firstCharge');
        $charge2 = (new Charge())->setId('secondCharge');

        $payment->addCharge($charge1);
        $payment->addCharge($charge2);

        $resourceServiceMock = $this->getMockBuilder(ResourceService::class)
            ->disableOriginalConstructor()->setMethods(['getResource'])->getMock();
        $resourceServiceMock->expects($this->exactly(2))
            ->method('getResource')
            ->withConsecutive([$charge1], [$charge2]);

        /** @var ResourceServiceInterface $resourceServiceMock */
        $heidelpayObj = (new Heidelpay('s-priv-123'))->setResourceService($resourceServiceMock);
        $payment->setParentResource($heidelpayObj);

        $payment->getChargeByIndex(0);
        $payment->getChargeByIndex(1);
    }

    /**
     * Verify getCharge and getChargeById will return null if the Charge does not exist.
     *
     * @test
     *
     * @throws HeidelpayApiException A HeidelpayApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException      A RuntimeException is thrown when there is an error while using the SDK.
     */
    public function getChargeMethodsShouldReturnNullIfTheChargeIdUnknown()
    {
        $payment = (new Payment())->setId('myPaymentId');
        $charge1 = (new Charge())->setId('firstCharge');
        $charge2 = (new Charge())->setId('secondCharge');
        $payment->addCharge($charge1);
        $payment->addCharge($charge2);

        $this->assertSame($charge1, $payment->getCharge('firstCharge', true));
        $this->assertSame($charge2, $payment->getCharge('secondCharge', true));
        $this->assertNull($payment->getCharge('thirdCharge'));

        $this->assertSame($charge1, $payment->getChargeByIndex(0, true));
        $this->assertSame($charge2, $payment->getChargeByIndex(1, true));
        $this->assertNull($payment->getChargeByIndex(2));
    }

    /**
     * Verify getPayout should try to fetch resource if lazy loading is off and the authorization is not null.
     *
     * @test
     *
     * @throws ReflectionException
     * @throws HeidelpayApiException A HeidelpayApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException      A RuntimeException is thrown when there is an error while using the SDK.
     */
    public function getPayoutShouldFetchPayoutIfNotLazyAndPayoutIsNotNull()
    {
        $payment = (new Payment())->setId('myPaymentId');
        $payout = new Payout();
        $payment->setPayout($payout);

        $resourceServiceMock = $this->getMockBuilder(ResourceService::class)->disableOriginalConstructor()->setMethods(['getResource'])->getMock();
        $resourceServiceMock->expects($this->once())->method('getResource')->with($payout);

        /** @var ResourceServiceInterface $resourceServiceMock */
        $heidelpayObj = (new Heidelpay('s-priv-123'))->setResourceService($resourceServiceMock);
        $payment->setParentResource($heidelpayObj);

        $payment->getPayout();
    }

    /**
     * Verify getPayout should try to fetch resource if lazy loading is off and the payout is not null.
     *
     * @test
     *
     * @throws ReflectionException
     * @throws HeidelpayApiException A HeidelpayApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException      A RuntimeException is thrown when there is an error while using the SDK.
     */
    public function getPayoutShouldNotFetchPayoutIfNotLazyAndPayoutIsNull()
    {
        $payment = (new Payment())->setId('myPaymentId');

        $resourceServiceMock = $this->getMockBuilder(ResourceService::class)->disableOriginalConstructor()->setMethods(['getResource'])->getMock();
        $resourceServiceMock->expects($this->never())->method('getResource');

        /** @var ResourceServiceInterface $resourceServiceMock */
        $heidelpayObj = (new Heidelpay('s-priv-123'))->setResourceService($resourceServiceMock);
        $payment->setParentResource($heidelpayObj);

        $payment->getPayout();
    }

    /**
     * Verify setCustomer does nothing if the passed customer is empty.
     *
     * @test
     *
     * @throws HeidelpayApiException A HeidelpayApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException      A RuntimeException is thrown when there is an error while using the SDK.
     */
    public function setCustomerShouldDoNothingIfTheCustomerIsEmpty()
    {
        $heidelpayObj = new Heidelpay('s-priv-123');
        $payment = (new Payment())->setParentResource($heidelpayObj);
        $customer = CustomerFactory::createCustomer('Max', 'Mustermann')->setId('myCustomer');
        $payment->setCustomer($customer);

        $this->assertSame($customer, $payment->getCustomer());

        $payment->setCustomer(0);
        $this->assertSame($customer, $payment->getCustomer());

        $payment->setCustomer(null);
        $this->assertSame($customer, $payment->getCustomer());
    }

    /**
     * Verify setCustomer will try to fetch the customer if it is passed as string (i. e. id).
     *
     * @test
     *
     * @throws ReflectionException
     * @throws HeidelpayApiException A HeidelpayApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException      A RuntimeException is thrown when there is an error while using the SDK.
     */
    public function setCustomerShouldFetchCustomerIfItIsPassedAsIdString()
    {
        $payment = (new Payment())->setId('myPaymentId');

        $resourceServiceMock = $this->getMockBuilder(ResourceService::class)
            ->disableOriginalConstructor()->setMethods(['fetchCustomer'])->getMock();
        $resourceServiceMock->expects($this->once())->method('fetchCustomer')->with('MyCustomerId');

        /** @var ResourceServiceInterface $resourceServiceMock */
        $heidelpayObj = (new Heidelpay('s-priv-123'))->setResourceService($resourceServiceMock);
        $payment->setParentResource($heidelpayObj);

        $payment->setCustomer('MyCustomerId');
    }

    /**
     * Verify setCustomer will create the resource if it is passed as object without id.
     *
     * @test
     *
     * @throws ReflectionException
     * @throws HeidelpayApiException A HeidelpayApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException      A RuntimeException is thrown when there is an error while using the SDK.
     */
    public function setCustomerShouldCreateCustomerIfItIsPassedAsObjectWithoutId()
    {
        $payment = (new Payment())->setId('myPaymentId');
        $customer = new Customer();

        $resourceServiceMock = $this->getMockBuilder(ResourceService::class)
            ->disableOriginalConstructor()->setMethods(['createCustomer'])->getMock();
        $resourceServiceMock->expects($this->once())->method('createCustomer')->with($customer);

        /** @var ResourceServiceInterface $resourceServiceMock */
        $heidelpayObj = (new Heidelpay('s-priv-123'))->setResourceService($resourceServiceMock);
        $payment->setParentResource($heidelpayObj);

        $payment->setCustomer($customer);
    }

    /**
     * Verify setPaymentType will do nothing if the paymentType is empty.
     *
     * @test
     *
     * @throws HeidelpayApiException A HeidelpayApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException      A RuntimeException is thrown when there is an error while using the SDK.
     */
    public function setPaymentTypeShouldDoNothingIfThePaymentTypeIsEmpty()
    {
        $heidelpayObj = new Heidelpay('s-priv-123');
        $payment = (new Payment())->setParentResource($heidelpayObj);
        $paymentType = (new Sofort())->setId('123');

        $payment->setPaymentType($paymentType);
        $this->assertSame($paymentType, $payment->getPaymentType());

        $payment->setPaymentType(0);
        $this->assertSame($paymentType, $payment->getPaymentType());

        $payment->setPaymentType(null);
        $this->assertSame($paymentType, $payment->getPaymentType());
    }

    /**
     * Verify setPaymentType will try to fetch the payment type if it is passed as string (i. e. id).
     *
     * @test
     *
     * @throws ReflectionException
     * @throws HeidelpayApiException A HeidelpayApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException      A RuntimeException is thrown when there is an error while using the SDK.
     */
    public function setPaymentTypeShouldFetchResourceIfItIsPassedAsIdString()
    {
        $payment = (new Payment())->setId('myPaymentId');

        $resourceServiceMock = $this->getMockBuilder(ResourceService::class)
            ->disableOriginalConstructor()->setMethods(['fetchPaymentType'])->getMock();
        $resourceServiceMock->expects($this->once())->method('fetchPaymentType')->with('MyPaymentId');

        /** @var ResourceServiceInterface $resourceServiceMock */
        $heidelpayObj = (new Heidelpay('s-priv-123'))->setResourceService($resourceServiceMock);
        $payment->setParentResource($heidelpayObj);

        $payment->setPaymentType('MyPaymentId');
    }

    /**
     * Verify setCustomer will create the resource if it is passed as object without id.
     *
     * @test
     *
     * @throws ReflectionException
     * @throws HeidelpayApiException A HeidelpayApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException      A RuntimeException is thrown when there is an error while using the SDK.
     */
    public function setPaymentTypeShouldCreateResourceIfItIsPassedAsObjectWithoutId()
    {
        $payment = (new Payment())->setId('myPaymentId');
        $paymentType = new Sofort();

        $resourceServiceMock = $this->getMockBuilder(ResourceService::class)
            ->disableOriginalConstructor()->setMethods(['createPaymentType'])->getMock();
        $resourceServiceMock->expects($this->once())->method('createPaymentType')->with($paymentType);

        /** @var ResourceServiceInterface $resourceServiceMock */
        $heidelpayObj = (new Heidelpay('s-priv-123'))->setResourceService($resourceServiceMock);
        $payment->setParentResource($heidelpayObj);

        $payment->setPaymentType($paymentType);
    }

    /**
     * Verify getCancellations will call getCancellations on all Charge and Authorization objects to fetch its refunds.
     *
     * @test
     *
     * @throws HeidelpayApiException
     * @throws ReflectionException
     * @throws RuntimeException
     */
    public function getCancellationsShouldCollectAllCancellationsOfCorrespondingTransactions()
    {
        $payment = new Payment();
        $cancellation1 = (new Cancellation())->setId('cancellation1');
        $cancellation2 = (new Cancellation())->setId('cancellation2');
        $cancellation3 = (new Cancellation())->setId('cancellation3');
        $cancellation4 = (new Cancellation())->setId('cancellation4');

        $expectedCancellations = [];

        $this->assertArraySubset($expectedCancellations, $payment->getCancellations());

        $authorize = $this->getMockBuilder(Authorization::class)->setMethods(['getCancellations'])->getMock();
        $authorize->expects($this->exactly(4))->method('getCancellations')->willReturn([$cancellation1]);

        /** @var Authorization $authorize */
        $payment->setAuthorization($authorize);
        $expectedCancellations[] = $cancellation1;
        $this->assertArraySubset($expectedCancellations, $payment->getCancellations());

        $charge1 = $this->getMockBuilder(Charge::class)->setMethods(['getCancellations'])->getMock();
        $charge1->expects($this->exactly(3))->method('getCancellations')->willReturn([$cancellation2]);

        /** @var Charge $charge1 */
        $payment->addCharge($charge1);
        $expectedCancellations[] = $cancellation2;
        $this->assertArraySubset($expectedCancellations, $payment->getCancellations());

        $charge2 = $this->getMockBuilder(Charge::class)->setMethods(['getCancellations'])->getMock();
        $charge2->expects($this->exactly(2))->method('getCancellations')->willReturn([$cancellation3, $cancellation4]);

        /** @var Charge $charge2 */
        $payment->addCharge($charge2);
        $expectedCancellations[] = $cancellation3;
        $expectedCancellations[] = $cancellation4;
        $this->assertArraySubset($expectedCancellations, $payment->getCancellations());

        $charge3 = $this->getMockBuilder(Charge::class)->setMethods(['getCancellations'])->getMock();
        $charge3->expects($this->once())->method('getCancellations')->willReturn([]);

        /** @var Charge $charge3 */
        $payment->addCharge($charge3);
        $this->assertArraySubset($expectedCancellations, $payment->getCancellations());
    }

    /**
     * Verify getCancellation calls getCancellations and returns null if cancellation does not exist.
     *
     * @test
     *
     * @throws HeidelpayApiException
     * @throws ReflectionException
     * @throws RuntimeException
     */
    public function getCancellationShouldCallGetCancellationsAndReturnNullIfNoCancellationExists()
    {
        $paymentMock = $this->getMockBuilder(Payment::class)->setMethods(['getCancellations'])->getMock();
        $paymentMock->expects($this->once())->method('getCancellations')->willReturn([]);

        /** @var Payment $paymentMock */
        $this->assertNull($paymentMock->getCancellation('123'));
    }

    /**
     * Verify getCancellation returns cancellation if it exists.
     *
     * @test
     *
     * @throws HeidelpayApiException
     * @throws ReflectionException
     * @throws RuntimeException
     */
    public function getCancellationShouldReturnCancellationIfItExists()
    {
        $cancellation1 = (new Cancellation())->setId('cancellation1');
        $cancellation2 = (new Cancellation())->setId('cancellation2');
        $cancellation3 = (new Cancellation())->setId('cancellation3');
        $cancellations = [$cancellation1, $cancellation2, $cancellation3];

        $paymentMock = $this->getMockBuilder(Payment::class)->setMethods(['getCancellations'])->getMock();
        $paymentMock->expects($this->once())->method('getCancellations')->willReturn($cancellations);

        /** @var Payment $paymentMock */
        $this->assertSame($cancellation2, $paymentMock->getCancellation('cancellation2', true));
    }

    /**
     * Verify getCancellation fetches cancellation if it exists and lazy loading is false.
     *
     * @test
     *
     * @throws HeidelpayApiException
     * @throws ReflectionException
     * @throws RuntimeException
     */
    public function getCancellationShouldReturnCancellationIfItExistsAndFetchItIfNotLazy()
    {
        $cancellation = (new Cancellation())->setId('cancellation123');

        $paymentMock = $this->getMockBuilder(Payment::class)->setMethods(['getCancellations'])->getMock();
        $paymentMock->expects($this->exactly(2))->method('getCancellations')->willReturn([$cancellation]);

        $resourceServiceMock = $this->getMockBuilder(ResourceService::class)
            ->disableOriginalConstructor()->setMethods(['getResource'])->getMock();
        $resourceServiceMock->expects($this->once())->method('getResource')->with($cancellation);

        /** @var ResourceServiceInterface $resourceServiceMock */
        $heidelpayObj = (new Heidelpay('s-priv-123'))->setResourceService($resourceServiceMock);

        /** @var Payment $paymentMock */
        $paymentMock->setParentResource($heidelpayObj);

        $this->assertSame($cancellation, $paymentMock->getCancellation('cancellation123'));
        $this->assertNull($paymentMock->getCancellation('cancellation1234'));
    }

    /**
     * Verify Shipments are handled properly.
     *
     * @test
     *
     * @throws HeidelpayApiException A HeidelpayApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException      A RuntimeException is thrown when there is an error while using the SDK.
     */
    public function shipmentsShouldBeHandledProperly()
    {
        $payment = new Payment();
        $this->assertIsEmptyArray($payment->getShipments());

        $shipment1 = (new Shipment())->setId('firstShipment');
        $shipment2 = (new Shipment())->setId('secondShipment');

        $subset[] = $shipment1;
        $payment->addShipment($shipment1);
        $this->assertArraySubset($subset, $payment->getShipments());

        $subset[] = $shipment2;
        $payment->addShipment($shipment2);
        $this->assertArraySubset($subset, $payment->getShipments());

        $this->assertSame($shipment2, $payment->getShipment('secondShipment', true));
        $this->assertSame($shipment1, $payment->getShipment('firstShipment', true));
    }

    /**
     * Verify getCancellation fetches cancellation if it exists and lazy loading is false.
     *
     * @test
     *
     * @throws HeidelpayApiException
     * @throws ReflectionException
     * @throws RuntimeException
     */
    public function getShipmentByIdShouldReturnShipmentIfItExistsAndFetchItIfNotLazy()
    {
        $shipment = (new Shipment())->setId('shipment123');

        $paymentMock = $this->getMockBuilder(Payment::class)->setMethods(['getShipments'])->getMock();
        $paymentMock->expects($this->exactly(2))->method('getShipments')->willReturn([$shipment]);

        $resourceServiceMock = $this->getMockBuilder(ResourceService::class)
            ->disableOriginalConstructor()->setMethods(['getResource'])->getMock();
        $resourceServiceMock->expects($this->once())->method('getResource')->with($shipment);

        /** @var ResourceServiceInterface $resourceServiceMock */
        $heidelpayObj = (new Heidelpay('s-priv-123'))->setResourceService($resourceServiceMock);

        /** @var Payment $paymentMock */
        $paymentMock->setParentResource($heidelpayObj);

        $this->assertSame($shipment, $paymentMock->getShipment('shipment123'));
        $this->assertNull($paymentMock->getShipment('shipment1234'));
    }

    /**
     * Verify the currency is fetched from the amount object.
     *
     * @test
     *
     * @throws Exception
     * @throws RuntimeException
     * @throws ReflectionException
     * @throws HeidelpayApiException
     */
    public function getAndSetCurrencyShouldPropagateToTheAmountObject()
    {
        /** @var Amount|MockObject $amountMock */
        $amountMock = $this->getMockBuilder(Amount::class)->setMethods(['getCurrency', 'setCurrency'])->getMock();
        $amountMock->expects($this->once())->method('getCurrency')->willReturn('MyTestGetCurrency');
        $amountMock->expects($this->once())->method('setCurrency')->with('MyTestSetCurrency');

        $payment = (new Payment())->setAmount($amountMock);
        $payment->handleResponse((object) ['currency' => 'MyTestSetCurrency']);
        $this->assertEquals('MyTestGetCurrency', $payment->getCurrency());
    }

    //<editor-fold desc="Handle Response Tests">

    /**
     * Verify handleResponse will update stateId.
     *
     * @test
     * @dataProvider stateDataProvider
     *
     * @param integer $state
     *
     * @throws HeidelpayApiException A HeidelpayApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException      A RuntimeException is thrown when there is an error while using the SDK.
     */
    public function handleResponseShouldUpdateStateId($state)
    {
        $payment = new Payment();
        $this->assertEquals(PaymentState::STATE_PENDING, $payment->getState());

        $response = new stdClass();
        $response->state = new stdClass();
        $response->state->id = $state;
        $payment->handleResponse($response);
        $this->assertEquals($state, $payment->getState());
    }

    /**
     * Verify handleResponse updates payment id.
     *
     * @test
     *
     * @throws HeidelpayApiException A HeidelpayApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException      A RuntimeException is thrown when there is an error while using the SDK.
     */
    public function handleResponseShouldUpdatePaymentId()
    {
        $payment = (new Payment())->setId('MyPaymentId');
        $this->assertEquals('MyPaymentId', $payment->getId());

        $response = new stdClass();
        $response->resources = new stdClass();
        $response->resources->paymentId = 'MyNewPaymentId';
        $payment->handleResponse($response);
        $this->assertEquals('MyNewPaymentId', $payment->getId());
    }

    /**
     * Verify handleResponse fetches Customer if it is not set.
     *
     * @test
     *
     * @throws HeidelpayApiException A HeidelpayApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException      A RuntimeException is thrown when there is an error while using the SDK.
     * @throws ReflectionException
     */
    public function handleResponseShouldFetchCustomerIfItIsNotSet()
    {
        $payment = (new Payment())->setId('myPaymentId');

        $resourceServiceMock = $this->getMockBuilder(ResourceService::class)
            ->disableOriginalConstructor()->setMethods(['fetchCustomer'])->getMock();
        $resourceServiceMock->expects($this->once())->method('fetchCustomer')->with('MyNewCustomerId');

        /** @var ResourceServiceInterface $resourceServiceMock */
        $heidelpayObj = (new Heidelpay('s-priv-123'))->setResourceService($resourceServiceMock);
        $payment->setParentResource($heidelpayObj);

        $this->assertNull($payment->getCustomer());

        $response = new stdClass();
        $response->resources = new stdClass();
        $response->resources->customerId = 'MyNewCustomerId';
        $payment->handleResponse($response);
    }

    /**
     * Verify handleResponse updates customer if it set.
     *
     * @test
     *
     * @throws HeidelpayApiException A HeidelpayApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException      A RuntimeException is thrown when there is an error while using the SDK.
     * @throws ReflectionException
     */
    public function handleResponseShouldFetchAndUpdateCustomerIfItIsAlreadySet()
    {
        $payment = (new Payment())->setId('myPaymentId');
        $customer = (new Customer())->setId('customerId');

        $resourceServiceMock = $this->getMockBuilder(ResourceService::class)
            ->disableOriginalConstructor()->setMethods(['getResource'])->getMock();
        $resourceServiceMock->expects($this->once())->method('getResource')->with($customer);

        /** @var ResourceServiceInterface $resourceServiceMock */
        $heidelpayObj = (new Heidelpay('s-priv-123'))->setResourceService($resourceServiceMock);
        $payment->setParentResource($heidelpayObj);
        $payment->setCustomer($customer);

        $response = new stdClass();
        $response->resources = new stdClass();
        $response->resources->customerId = 'customerId';
        $payment->handleResponse($response);
    }

    /**
     * Verify handleResponse updates paymentType.
     *
     * @test
     *
     * @throws HeidelpayApiException A HeidelpayApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException      A RuntimeException is thrown when there is an error while using the SDK.
     * @throws ReflectionException
     */
    public function handleResponseShouldFetchAndUpdatePaymentTypeIfTheIdIsSet()
    {
        $payment = (new Payment())->setId('myPaymentId');

        $resourceServiceMock = $this->getMockBuilder(ResourceService::class)
            ->disableOriginalConstructor()->setMethods(['fetchPaymentType'])->getMock();
        $resourceServiceMock->expects($this->once())->method('fetchPaymentType')->with('PaymentTypeId');

        /** @var ResourceServiceInterface $resourceServiceMock */
        $heidelpayObj = (new Heidelpay('s-priv-123'))->setResourceService($resourceServiceMock);
        $payment->setParentResource($heidelpayObj);

        $response = new stdClass();
        $response->resources = new stdClass();
        $response->resources->typeId = 'PaymentTypeId';
        $payment->handleResponse($response);
    }

    /**
     * Verify handleResponse updates metadata.
     *
     * @test
     *
     * @throws HeidelpayApiException A HeidelpayApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException      A RuntimeException is thrown when there is an error while using the SDK.
     * @throws ReflectionException
     */
    public function handleResponseShouldFetchAndUpdateMetadataIfTheIdIsSet()
    {
        $resourceServiceMock = $this->getMockBuilder(ResourceService::class)->disableOriginalConstructor()->setMethods(['fetchMetadata'])->getMock();
        $resourceServiceMock->expects($this->once())->method('fetchMetadata')->with('MetadataId');
        /** @var ResourceServiceInterface $resourceServiceMock */
        $heidelpayObj = (new Heidelpay('s-priv-123'))->setResourceService($resourceServiceMock);
        $payment = (new Payment())->setId('myPaymentId')->setParentResource($heidelpayObj);

        $response = new stdClass();
        $response->resources = new stdClass();
        $response->resources->metadataId = 'MetadataId';
        $payment->handleResponse($response);
    }

    /**
     * Verify handleResponse updates metadata.
     *
     * @test
     *
     * @throws HeidelpayApiException A HeidelpayApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException      A RuntimeException is thrown when there is an error while using the SDK.
     * @throws ReflectionException
     */
    public function handleResponseShouldGetMetadataIfUnfetchedMetadataObjectWithIdIsGiven()
    {
        $metadata = (new Metadata())->setId('MetadataId');
        $resourceServiceMock = $this->getMockBuilder(ResourceService::class)->disableOriginalConstructor()->setMethods(['getResource'])->getMock();
        $resourceServiceMock->expects($this->once())->method('getResource')->with($metadata);
        /** @var ResourceServiceInterface $resourceServiceMock */
        $heidelpayObj = (new Heidelpay('s-priv-123'))->setResourceService($resourceServiceMock);
        $payment = (new Payment())->setId('myPaymentId')->setParentResource($heidelpayObj)->setMetadata($metadata);

        $response = new stdClass();
        $response->resources = new stdClass();
        $response->resources->metadataId = 'MetadataId';
        $payment->handleResponse($response);
    }

    /**
     * Verify handleResponse does nothing if transactions is empty.
     *
     * @test
     *
     * @throws HeidelpayApiException A HeidelpayApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException      A RuntimeException is thrown when there is an error while using the SDK.
     */
    public function handleResponseShouldUpdateChargeTransactions()
    {
        $payment = (new Payment())->setId('MyPaymentId');
        $this->assertIsEmptyArray($payment->getCharges());
        $this->assertIsEmptyArray($payment->getShipments());
        $this->assertIsEmptyArray($payment->getCancellations());
        $this->assertNull($payment->getAuthorization());

        $response = new stdClass();
        $response->transactions = [];
        $payment->handleResponse($response);

        $this->assertIsEmptyArray($payment->getCharges());
        $this->assertIsEmptyArray($payment->getShipments());
        $this->assertIsEmptyArray($payment->getCancellations());
        $this->assertNull($payment->getAuthorization());
    }

    /**
     * Verify handleResponse updates existing authorization from response.
     *
     * @test
     *
     * @throws HeidelpayApiException A HeidelpayApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException      A RuntimeException is thrown when there is an error while using the SDK.
     */
    public function handleResponseShouldUpdateAuthorizationFromResponse()
    {
        $heidelpay = new Heidelpay('s-priv-123');
        $payment = (new Payment())->setParentResource($heidelpay)->setId('MyPaymentId');

        $authorization = (new Authorization(11.98, 'EUR'))->setId('s-aut-1');
        $this->assertEquals(11.98, $authorization->getAmount());

        $payment->setAuthorization($authorization);

        $authorizationData = new stdClass();
        $authorizationData->url = 'https://api-url.test/payments/MyPaymentId/authorize/s-aut-1';
        $authorizationData->amount = '10.321';
        $authorizationData->type = 'authorize';

        $response = new stdClass();
        $response->transactions = [$authorizationData];
        $payment->handleResponse($response);

        $authorization = $payment->getAuthorization(true);
        $this->assertInstanceOf(Authorization::class, $authorization);
        $this->assertEquals(10.321, $authorization->getAmount());
    }

    /**
     * Verify handleResponse adds authorization from response.
     *
     * @test
     *
     * @throws HeidelpayApiException A HeidelpayApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException      A RuntimeException is thrown when there is an error while using the SDK.
     */
    public function handleResponseShouldAddAuthorizationFromResponse()
    {
        $heidelpay = new Heidelpay('s-priv-123');
        $payment = (new Payment())->setParentResource($heidelpay)->setId('MyPaymentId');
        $this->assertNull($payment->getAuthorization());

        $authorizationData = new stdClass();
        $authorizationData->url = 'https://api-url.test/payments/MyPaymentId/authorize/s-aut-1';
        $authorizationData->amount = '10.123';
        $authorizationData->type = 'authorize';

        $response = new stdClass();
        $response->transactions = [$authorizationData];
        $payment->handleResponse($response);

        $authorization = $payment->getAuthorization(true);
        $this->assertInstanceOf(Authorization::class, $authorization);
        $this->assertEquals('s-aut-1', $authorization->getId());
        $this->assertEquals(10.123, $authorization->getAmount());
        $this->assertSame($payment, $authorization->getPayment());
        $this->assertSame($payment, $authorization->getParentResource());
    }

    /**
     * Verify handleResponse updates existing charge from response.
     *
     * @test
     *
     * @throws HeidelpayApiException A HeidelpayApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException      A RuntimeException is thrown when there is an error while using the SDK.
     */
    public function handleResponseShouldUpdateChargeFromResponseIfItExists()
    {
        $heidelpay = new Heidelpay('s-priv-123');
        $payment = (new Payment())->setParentResource($heidelpay)->setId('MyPaymentId');

        $charge1 = (new Charge(11.98, 'EUR'))->setId('s-chg-1');
        $charge2 = (new Charge(22.98, 'EUR'))->setId('s-chg-2');
        $this->assertEquals(22.98, $charge2->getAmount());

        $payment->addCharge($charge1)->addCharge($charge2);

        $chargeData = new stdClass();
        $chargeData->url = 'https://api-url.test/payments/MyPaymentId/charge/s-chg-2';
        $chargeData->amount = '11.111';
        $chargeData->type = 'charge';

        $response = new stdClass();
        $response->transactions = [$chargeData];
        $payment->handleResponse($response);

        $charge = $payment->getCharge('s-chg-2', true);
        $this->assertInstanceOf(Charge::class, $charge);
        $this->assertSame($charge2, $charge);
        $this->assertEquals(11.111, $charge->getAmount());
    }

    /**
     * Verify handleResponse adds non existing charge from response.
     *
     * @test
     *
     * @throws HeidelpayApiException A HeidelpayApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException      A RuntimeException is thrown when there is an error while using the SDK.
     */
    public function handleResponseShouldAddChargeFromResponseIfItDoesNotExists()
    {
        $heidelpay = new Heidelpay('s-priv-123');
        $payment = (new Payment())->setParentResource($heidelpay)->setId('MyPaymentId');

        $charge1 = (new Charge(11.98, 'EUR'))->setId('s-chg-1');
        $payment->addCharge($charge1);
        $this->assertCount(1, $payment->getCharges());
        $this->assertNull($payment->getCharge('s-chg-2'));

        $chargeData = new stdClass();
        $chargeData->url = 'https://api-url.test/payments/MyPaymentId/charge/s-chg-2';
        $chargeData->amount = '11.111';
        $chargeData->type = 'charge';

        $response = new stdClass();
        $response->transactions = [$chargeData];
        $payment->handleResponse($response);

        $charge = $payment->getCharge('s-chg-2', true);
        $this->assertInstanceOf(Charge::class, $charge);
        $this->assertCount(2, $payment->getCharges());
        $this->assertEquals(11.111, $charge->getAmount());
    }

    /**
     * Verify handleResponse updates existing reversals from response.
     *
     * @test
     *
     * @throws HeidelpayApiException A HeidelpayApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException      A RuntimeException is thrown when there is an error while using the SDK.
     */
    public function handleResponseShouldUpdateReversalFromResponseIfItExists()
    {
        $heidelpay = new Heidelpay('s-priv-123');
        $payment = (new Payment())->setParentResource($heidelpay)->setId('MyPaymentId');
        $authorize = (new Authorization(23.55, 'EUR'))->setId('s-aut-1');
        $payment->setAuthorization($authorize);
        $reversal1 = (new Cancellation(1.98))->setId('s-cnl-1');
        $reversal2 = (new Cancellation(2.98))->setId('s-cnl-2');
        $this->assertEquals(2.98, $reversal2->getAmount());
        $authorize->addCancellation($reversal1)->addCancellation($reversal2);

        $cancellation = new stdClass();
        $cancellation->url = 'https://api-url.test/payments/MyPaymentId/authorize/s-aut-1/cancel/s-cnl-2';
        $cancellation->amount = '11.111';
        $cancellation->type = 'cancel-authorize';

        $response = new stdClass();
        $response->transactions = [$cancellation];
        $payment->handleResponse($response);

        $authorization = $payment->getAuthorization(true);
        $cancellation = $authorization->getCancellation('s-cnl-2', true);
        $this->assertInstanceOf(Cancellation::class, $cancellation);
        $this->assertSame($reversal2, $cancellation);
        $this->assertEquals(11.111, $cancellation->getAmount());
    }

    /**
     * Verify handleResponse adds non existing reversal from response.
     *
     * @test
     *
     * @throws HeidelpayApiException A HeidelpayApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException      A RuntimeException is thrown when there is an error while using the SDK.
     */
    public function handleResponseShouldAddReversalFromResponseIfItDoesNotExists()
    {
        $heidelpay = new Heidelpay('s-priv-123');
        $payment = (new Payment())->setParentResource($heidelpay)->setId('MyPaymentId');
        $authorize = (new Authorization(23.55, 'EUR'))->setId('s-aut-1');
        $payment->setAuthorization($authorize);
        $reversal1 = (new Cancellation(1.98))->setId('s-cnl-1');
        $authorize->addCancellation($reversal1);
        $this->assertNull($authorize->getCancellation('s-cnl-2'));
        $this->assertCount(1, $authorize->getCancellations());


        $cancellation = new stdClass();
        $cancellation->url = 'https://api-url.test/payments/MyPaymentId/authorize/s-aut-1/cancel/s-cnl-2';
        $cancellation->amount = '11.111';
        $cancellation->type = 'cancel-authorize';

        $response = new stdClass();
        $response->transactions = [$cancellation];
        $payment->handleResponse($response);

        $authorization = $payment->getAuthorization(true);
        $cancellation = $authorization->getCancellation('s-cnl-2', true);
        $this->assertInstanceOf(Cancellation::class, $cancellation);
        $this->assertEquals(11.111, $cancellation->getAmount());
        $this->assertCount(2, $authorize->getCancellations());
    }

    /**
     * Verify that handleResponse will throw an exception if the authorization to a reversal does not exist.
     *
     * @test
     *
     * @throws HeidelpayApiException A HeidelpayApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException      A RuntimeException is thrown when there is an error while using the SDK.
     */
    public function handleResponseShouldThrowExceptionIfAnAuthorizeToAReversalDoesNotExist()
    {
        $heidelpay = new Heidelpay('s-priv-123');
        $payment = (new Payment())->setParentResource($heidelpay)->setId('MyPaymentId');

        $cancellation = new stdClass();
        $cancellation->url = 'https://api-url.test/payments/MyPaymentId/authorize/s-aut-1/cancel/s-cnl-2';
        $cancellation->amount = '11.111';
        $cancellation->type = 'cancel-authorize';

        $response = new stdClass();
        $response->transactions = [$cancellation];

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The Authorization object can not be found.');
        $payment->handleResponse($response);
    }

    /**
     * Verify handleResponse updates existing refunds from response.
     *
     * @test
     *
     * @throws HeidelpayApiException A HeidelpayApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException      A RuntimeException is thrown when there is an error while using the SDK.
     */
    public function handleResponseShouldUpdateRefundsFromResponseIfItExists()
    {
        $heidelpay = new Heidelpay('s-priv-123');
        $payment = (new Payment())->setParentResource($heidelpay)->setId('MyPaymentId');
        $charge = (new Charge(23.55, 'EUR'))->setId('s-chg-1');
        $payment->addCharge($charge);
        $refund1 = (new Cancellation(1.98))->setId('s-cnl-1');
        $refund2 = (new Cancellation(2.98))->setId('s-cnl-2');
        $this->assertEquals(2.98, $refund2->getAmount());
        $charge->addCancellation($refund1)->addCancellation($refund2);

        $cancellation = new stdClass();
        $cancellation->url = 'https://api-url.test/payments/MyPaymentId/charge/s-chg-1/cancel/s-cnl-2';
        $cancellation->amount = '11.111';
        $cancellation->type = 'cancel-charge';

        $response = new stdClass();
        $response->transactions = [$cancellation];
        $payment->handleResponse($response);

        $fetchedCharge = $payment->getCharge('s-chg-1', true);
        $cancellation = $fetchedCharge->getCancellation('s-cnl-2', true);
        $this->assertInstanceOf(Cancellation::class, $cancellation);
        $this->assertSame($refund2, $cancellation);
        $this->assertEquals(11.111, $cancellation->getAmount());
    }

    /**
     * Verify handleResponse adds non existing refund from response.
     *
     * @test
     *
     * @throws HeidelpayApiException A HeidelpayApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException      A RuntimeException is thrown when there is an error while using the SDK.
     */
    public function handleResponseShouldAddRefundFromResponseIfItDoesNotExists()
    {
        $heidelpay = new Heidelpay('s-priv-123');
        $payment = (new Payment())->setParentResource($heidelpay)->setId('MyPaymentId');
        $charge = (new Charge(23.55, 'EUR'))->setId('s-chg-1');
        $payment->addCharge($charge);
        $reversal1 = (new Cancellation(1.98))->setId('s-cnl-1');
        $charge->addCancellation($reversal1);
        $this->assertNull($charge->getCancellation('s-cnl-2'));
        $this->assertCount(1, $charge->getCancellations());


        $cancellation = new stdClass();
        $cancellation->url = 'https://api-url.test/payments/MyPaymentId/charge/s-chg-1/cancel/s-cnl-2';
        $cancellation->amount = '11.111';
        $cancellation->type = 'cancel-charge';

        $response = new stdClass();
        $response->transactions = [$cancellation];
        $payment->handleResponse($response);

        $fetchedCharge = $payment->getCharge('s-chg-1', true);
        $cancellation = $fetchedCharge->getCancellation('s-cnl-2', true);
        $this->assertInstanceOf(Cancellation::class, $cancellation);
        $this->assertEquals(11.111, $cancellation->getAmount());
        $this->assertCount(2, $charge->getCancellations());
    }

    /**
     * Verify that handleResponse will throw an exception if the charge to a refund does not exist.
     *
     * @test
     *
     * @throws HeidelpayApiException A HeidelpayApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException      A RuntimeException is thrown when there is an error while using the SDK.
     */
    public function handleResponseShouldThrowExceptionIfAChargeToARefundDoesNotExist()
    {
        $heidelpay = new Heidelpay('s-priv-123');
        $payment = (new Payment())->setParentResource($heidelpay)->setId('MyPaymentId');

        $cancellation = new stdClass();
        $cancellation->url = 'https://api-url.test/payments/MyPaymentId/charge/s-chg-1/cancel/s-cnl-2';
        $cancellation->amount = '11.111';
        $cancellation->type = 'cancel-charge';

        $response = new stdClass();
        $response->transactions = [$cancellation];

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The Charge object can not be found.');
        $payment->handleResponse($response);
    }

    /**
     * Verify handleResponse updates existing shipment from response.
     *
     * @test
     *
     * @throws HeidelpayApiException A HeidelpayApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException      A RuntimeException is thrown when there is an error while using the SDK.
     */
    public function handleResponseShouldUpdateShipmentFromResponseIfItExists()
    {
        $heidelpay = new Heidelpay('s-priv-123');
        $payment = (new Payment())->setParentResource($heidelpay)->setId('MyPaymentId');
        $shipment = (new Shipment())->setAmount('1.23')->setId('s-shp-1');
        $this->assertEquals('1.23', $shipment->getAmount());
        $payment->addShipment($shipment);

        $shipmentResponse = new stdClass();
        $shipmentResponse->url = 'https://api-url.test/payments/MyPaymentId/shipment/s-shp-1';
        $shipmentResponse->amount = '11.111';
        $shipmentResponse->type = 'shipment';

        $response = new stdClass();
        $response->transactions = [$shipmentResponse];
        $payment->handleResponse($response);

        $fetchedShipment = $payment->getShipment('s-shp-1', true);
        $this->assertInstanceOf(Shipment::class, $fetchedShipment);
        $this->assertSame($shipment, $fetchedShipment);
        $this->assertEquals(11.111, $fetchedShipment->getAmount());
    }

    /**
     * Verify handleResponse adds non existing shipment from response.
     *
     * @test
     *
     * @throws HeidelpayApiException A HeidelpayApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException      A RuntimeException is thrown when there is an error while using the SDK.
     */
    public function handleResponseShouldAddShipmentFromResponseIfItDoesNotExists()
    {
        $heidelpay = new Heidelpay('s-priv-123');
        $payment = (new Payment())->setParentResource($heidelpay)->setId('MyPaymentId');
        $this->assertNull($payment->getShipment('s-shp-1'));
        $this->assertCount(0, $payment->getShipments());

        $shipment = new stdClass();
        $shipment->url = 'https://api-url.test/payments/MyPaymentId/shipment/s-shp-1';
        $shipment->amount = '11.111';
        $shipment->type = 'shipment';

        $response = new stdClass();
        $response->transactions = [$shipment];
        $payment->handleResponse($response);

        $fetchedShipment = $payment->getShipment('s-shp-1', true);
        $this->assertInstanceOf(Shipment::class, $fetchedShipment);
        $this->assertEquals(11.111, $fetchedShipment->getAmount());
        $this->assertCount(1, $payment->getShipments());
    }

    /**
     * Verify handleResponse updates existing payout from response.
     *
     * @test
     *
     * @throws HeidelpayApiException A HeidelpayApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException      A RuntimeException is thrown when there is an error while using the SDK.
     */
    public function handleResponseShouldUpdatePayoutFromResponseIfItExists()
    {
        $heidelpay = new Heidelpay('s-priv-123');
        $payment = (new Payment())->setParentResource($heidelpay)->setId('MyPaymentId');
        $payout = (new Payout())->setAmount('1.23')->setId('s-out-1');
        $this->assertEquals('1.23', $payout->getAmount());
        $payment->setPayout($payout);

        $payoutResponse = new stdClass();
        $payoutResponse->url = 'https://api-url.test/payments/MyPaymentId/payouts/s-out-1';
        $payoutResponse->amount = '11.111';
        $payoutResponse->type = 'payout';

        $response = new stdClass();
        $response->transactions = [$payoutResponse];
        $payment->handleResponse($response);

        $fetchedPayout = $payment->getPayout(true);
        $this->assertInstanceOf(Payout::class, $fetchedPayout);
        $this->assertSame($payout, $fetchedPayout);
        $this->assertEquals(11.111, $fetchedPayout->getAmount());
    }

    /**
     * Verify handleResponse adds non existing refund from response.
     *
     * @test
     *
     * @throws HeidelpayApiException A HeidelpayApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException      A RuntimeException is thrown when there is an error while using the SDK.
     */
    public function handleResponseShouldAddPayoutFromResponseIfItDoesNotExists()
    {
        $heidelpay = new Heidelpay('s-priv-123');
        $payment = (new Payment())->setParentResource($heidelpay)->setId('MyPaymentId');
        $this->assertNull($payment->getPayout('s-out-1'));

        $payoutResponse = new stdClass();
        $payoutResponse->url = 'https://api-url.test/payments/MyPaymentId/payouts/s-out-1';
        $payoutResponse->amount = '11.111';
        $payoutResponse->type = 'payout';

        $response = new stdClass();
        $response->transactions = [$payoutResponse];
        $payment->handleResponse($response);

        $fetchedPayout = $payment->getPayout(true);
        $this->assertInstanceOf(Payout::class, $fetchedPayout);
        $this->assertEquals(11.111, $fetchedPayout->getAmount());
    }

    //</editor-fold>

    /**
     * Verify charge will call chargePayment on heidelpay object.
     *
     * @test
     *
     * @throws HeidelpayApiException
     * @throws ReflectionException
     * @throws RuntimeException
     */
    public function chargeMethodShouldPropagateToHeidelpayChargePaymentMethod()
    {
        $payment = new Payment();

        /** @var Heidelpay|MockObject $heidelpayMock */
        $heidelpayMock = $this->getMockBuilder(Heidelpay::class)->disableOriginalConstructor()->setMethods(['chargePayment'])->getMock();
        $heidelpayMock->expects($this->exactly(3))->method('chargePayment')
            ->withConsecutive(
                [$payment, null, null],
                [$payment, 1.1, null],
                [$payment, 2.2, 'MyCurrency']
            )->willReturn(new Charge());
        $payment->setParentResource($heidelpayMock);

        $payment->charge();
        $payment->charge(1.1);
        $payment->charge(2.2, 'MyCurrency');
    }

    /**
     * Verify ship will call ship method on heidelpay object.
     *
     * @test
     *
     * @throws HeidelpayApiException
     * @throws ReflectionException
     * @throws RuntimeException
     */
    public function shipMethodShouldPropagateToHeidelpayChargePaymentMethod()
    {
        $payment = new Payment();

        /** @var Heidelpay|MockObject $heidelpayMock */
        $heidelpayMock = $this->getMockBuilder(Heidelpay::class)->disableOriginalConstructor()->setMethods(['ship'])->getMock();
        $heidelpayMock->expects($this->once())->method('ship')->willReturn(new Shipment());

        $payment->setParentResource($heidelpayMock);
        $payment->ship();
    }

    /**
     * Verify setMetadata will set parent resource and call create with metadata object.
     *
     * @test
     *
     * @throws HeidelpayApiException A HeidelpayApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException      A RuntimeException is thrown when there is an error while using the SDK.
     * @throws ReflectionException
     */
    public function setMetaDataShouldSetParentResourceAndCreateMetaDataObject()
    {
        $metadata = (new Metadata())->addMetadata('myData', 'myValue');

        /** @var ResourceService|MockObject $resourceSrvMock */
        $resourceSrvMock = $this->getMockBuilder(ResourceService::class)->setMethods(['createResource'])->disableOriginalConstructor()->getMock();
        $resourceSrvMock->expects($this->once())->method('createResource')->with($metadata);

        $heidelpay = (new Heidelpay('s-priv-1234'))->setResourceService($resourceSrvMock);
        $payment = new Payment($heidelpay);

        try {
            $metadata->getParentResource();
            $this->assertTrue(false, 'This exception should have been thrown!');
        } catch (RuntimeException $e) {
            $this->assertInstanceOf(RuntimeException::class, $e);
            $this->assertEquals('Parent resource reference is not set!', $e->getMessage());
        }

        $payment->setMetadata($metadata);
        $this->assertSame($heidelpay, $metadata->getParentResource());
    }

    /**
     * Verify setMetadata will not set the metadata property if it is not of type metadata.
     *
     * @test
     *
     * @throws HeidelpayApiException A HeidelpayApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException      A RuntimeException is thrown when there is an error while using the SDK.
     * @throws ReflectionException
     */
    public function metadataMustBeOfTypeMetadata()
    {
        $metadata = new Metadata();

        /** @var ResourceService|MockObject $resourceSrvMock */
        $resourceSrvMock = $this->getMockBuilder(ResourceService::class)->setMethods(['createResource'])->disableOriginalConstructor()->getMock();
        $resourceSrvMock->expects($this->once())->method('createResource')->with($metadata);
        $heidelpay = (new Heidelpay('s-priv-1234'))->setResourceService($resourceSrvMock);

        // when
        $payment = new Payment($heidelpay);

        // then
        $this->assertNull($payment->getMetadata());

        // when
        /** @noinspection PhpParamsInspection */
        $payment->setMetadata('test');

        // then
        $this->assertNull($payment->getMetadata());

        // when
        $payment->setMetadata($metadata);

        // then
        $this->assertSame($metadata, $payment->getMetadata());
    }

    /**
     * Verify set Basket will call create if the given basket object does not exist yet.
     *
     * @test
     *
     * @throws HeidelpayApiException A HeidelpayApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException      A RuntimeException is thrown when there is an error while using the SDK.
     * @throws ReflectionException
     */
    public function setBasketShouldCallCreateIfTheGivenBasketObjectDoesNotExistYet()
    {
        $heidelpay = new Heidelpay('s-priv-123');

        /** @var ResourceService|MockObject $resourceSrvMock */
        $resourceSrvMock = $this->getMockBuilder(ResourceService::class)->setConstructorArgs([$heidelpay])->setMethods(['createResource'])->getMock();
        $heidelpay->setResourceService($resourceSrvMock);

        $basket = new Basket();
        $resourceSrvMock->expects($this->once())->method('createResource')->with(
            $this->callback(
                static function ($object) use ($basket, $heidelpay) {
                    /** @var Basket $object */
                    return $object === $basket && $object->getParentResource() === $heidelpay;
                })
        );

        $payment = new Payment($heidelpay);
        $payment->setBasket($basket);
    }

    /**
     * Verify setBasket won't call resource service when the basket is null.
     *
     * @test
     *
     * @throws HeidelpayApiException A HeidelpayApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException      A RuntimeException is thrown when there is an error while using the SDK.
     * @throws ReflectionException
     */
    public function setBasketWontCallResourceServiceWhenBasketIsNull()
    {
        $heidelpay = new Heidelpay('s-priv-123');

        /** @var ResourceService|MockObject $resourceSrvMock */
        $resourceSrvMock = $this->getMockBuilder(ResourceService::class)->setConstructorArgs([$heidelpay])->setMethods(['createResource'])->getMock();
        $resourceSrvMock->expects($this->once())->method('createResource');
        $heidelpay->setResourceService($resourceSrvMock);

        // set basket first to prove the setter works both times
        $basket = new Basket();
        $payment = new Payment($heidelpay);
        $payment->setBasket($basket);
        $this->assertSame($basket, $payment->getBasket());

        $payment->setBasket(null);
        $this->assertNull($payment->getBasket());
    }

    /**
     * Verify updateResponseResources will fetch the basketId in response if it is set.
     *
     * @test
     *
     * @throws HeidelpayApiException A HeidelpayApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException      A RuntimeException is thrown when there is an error while using the SDK.
     * @throws ReflectionException
     */
    public function updateResponseResourcesShouldFetchBasketIdIfItIsSetInResponse()
    {
        /** @var Heidelpay|MockObject $heidelpayMock */
        $heidelpayMock = $this->getMockBuilder(Heidelpay::class)->disableOriginalConstructor()->setMethods(['fetchBasket'])->getMock();

        $basket = new Basket();
        $heidelpayMock->expects($this->once())->method('fetchBasket')->with('myResourcesBasketId')->willReturn($basket);

        $payment  = new Payment($heidelpayMock);
        $response = new stdClass();
        $payment->handleResponse($response);
        $this->assertNull($payment->getBasket());

        $response->resources = new stdClass();
        $response->resources->basketId = 'myResourcesBasketId';
        $payment->handleResponse($response);
    }

    /**
     * Verify a payment is fetched by orderId if the id is not set.
     *
     * @test
     *
     * @throws RuntimeException
     */
    public function paymentShouldBeFetchedByOrderIdIfIdIsNotSet()
    {
        $orderId     = str_replace(' ', '', microtime());
        $payment     = (new Payment())->setOrderId($orderId)->setParentResource(new Heidelpay('s-priv-123'));
        $lastElement = explode('/', rtrim($payment->getUri(), '/'));
        $this->assertEquals($orderId, end($lastElement));
    }

    //<editor-fold desc="Data Providers">

    /**
     * Provides the different payment states.
     *
     * @return array
     */
    public function stateDataProvider(): array
    {
        return [
            PaymentState::STATE_NAME_PENDING        => [PaymentState::STATE_PENDING],
            PaymentState::STATE_NAME_COMPLETED      => [PaymentState::STATE_COMPLETED],
            PaymentState::STATE_NAME_CANCELED       => [PaymentState::STATE_CANCELED],
            PaymentState::STATE_NAME_PARTLY         => [PaymentState::STATE_PARTLY],
            PaymentState::STATE_NAME_PAYMENT_REVIEW => [PaymentState::STATE_PAYMENT_REVIEW],
            PaymentState::STATE_NAME_CHARGEBACK     => [PaymentState::STATE_CHARGEBACK]
        ];
    }

    //</editor-fold>
}
