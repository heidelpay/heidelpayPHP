<?php
/**
 * The interface for the ResourceService.
 *
 * Copyright (C) 2019 heidelpay GmbH
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
 * @package  heidelpayPHP\Interfaces
 */
namespace heidelpayPHP\Interfaces;

use heidelpayPHP\Exceptions\HeidelpayApiException;
use heidelpayPHP\Resources\AbstractHeidelpayResource;
use heidelpayPHP\Resources\Basket;
use heidelpayPHP\Resources\Customer;
use heidelpayPHP\Resources\Keypair;
use heidelpayPHP\Resources\Metadata;
use heidelpayPHP\Resources\Payment;
use heidelpayPHP\Resources\PaymentTypes\BasePaymentType;
use heidelpayPHP\Resources\Recurring;
use heidelpayPHP\Resources\TransactionTypes\Authorization;
use heidelpayPHP\Resources\TransactionTypes\Cancellation;
use heidelpayPHP\Resources\TransactionTypes\Charge;
use heidelpayPHP\Resources\TransactionTypes\Payout;
use heidelpayPHP\Resources\TransactionTypes\Shipment;
use RuntimeException;

interface ResourceServiceInterface
{
    /**
     * Retrieves an Payout resource via the API using the corresponding Payment or paymentId.
     * The Payout resource can not be fetched using its id since they are unique only within the Payment.
     * A Payment can have zero or one Payouts.
     *
     * @param Payment|string $payment The Payment object or the id of a Payment object whose Payout to fetch.
     *                                There can only be one payout object to a payment.
     *
     * @return Payout The Payout object of the given Payment.
     *
     * @throws HeidelpayApiException A HeidelpayApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException      A RuntimeException is thrown when there is an error while using the SDK.
     */
    public function fetchPayout($payment): Payout;

    /**
     * Activate recurring payment for the given payment type (if possible).
     *
     * @param string|BasePaymentType $paymentType The payment to activate recurring payment for.
     * @param string                 $returnUrl   The URL to which the customer gets redirected in case of a 3ds
     *                                            transaction
     *
     * @return Recurring The recurring object.
     *
     * @throws HeidelpayApiException A HeidelpayApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException      A RuntimeException is thrown when there is an error while using the SDK.
     */
    public function activateRecurringPayment($paymentType, $returnUrl): Recurring;

    /**
     * Fetch and return payment by given payment id or payment object.
     * If a payment object is given it will be updated as well, thus you do not rely on the returned object.
     *
     * @param Payment|string $payment The payment object or paymentId to fetch.
     *
     * @return Payment The fetched payment object.
     *
     * @throws HeidelpayApiException A HeidelpayApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException      A RuntimeException is thrown when there is an error while using the SDK.
     */
    public function fetchPayment($payment): Payment;

    /**
     * Fetch and return payment by given order id.
     *
     * @param string $orderId The external order id to fetch the payment by.
     *
     * @return Payment The fetched payment object.
     *
     * @throws HeidelpayApiException A HeidelpayApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException      A RuntimeException is thrown when there is an error while using the SDK.
     */
    public function fetchPaymentByOrderId($orderId): Payment;

    /**
     * Fetch public key and configured payment types from API.
     *
     * @param bool $detailed If this flag is set detailed information are fetched.
     *
     * @return Keypair
     *
     * @throws HeidelpayApiException A HeidelpayApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException      A RuntimeException is thrown when there is an error while using the SDK.
     */
    public function fetchKeypair($detailed = false): Keypair;

    /**
     * Create Metadata resource.
     *
     * @param Metadata $metadata The Metadata object to be created.
     *
     * @return Metadata The fetched Metadata resource.
     *
     * @throws HeidelpayApiException A HeidelpayApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException      A RuntimeException is thrown when there is an error while using the SDK.
     */
    public function createMetadata(Metadata $metadata): Metadata;

    /**
     * Fetch and return Metadata resource.
     *
     * @param Metadata|string $metadata
     *
     * @return Metadata
     *
     * @throws HeidelpayApiException A HeidelpayApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException      A RuntimeException is thrown when there is an error while using the SDK.
     */
    public function fetchMetadata($metadata): Metadata;

    /**
     * Creates and returns the given basket resource.
     *
     * @param Basket $basket The basket to be created.
     *
     * @return Basket The created Basket object.
     *
     * @throws HeidelpayApiException A HeidelpayApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException      A RuntimeException is thrown when there is an error while using the SDK.
     */
    public function createBasket(Basket $basket): Basket;

    /**
     * Fetches and returns the given Basket (by object or id).
     *
     * @param Basket|string $basket Basket object or id of basket to be fetched.
     *
     * @return Basket The fetched Basket object.
     *
     * @throws HeidelpayApiException A HeidelpayApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException      A RuntimeException is thrown when there is an error while using the SDK.
     */
    public function fetchBasket($basket): Basket;

    /**
     * Update the a basket resource with the given basket object (id must be set).
     *
     * @param Basket $basket
     *
     * @return Basket The updated Basket object.
     *
     * @throws HeidelpayApiException A HeidelpayApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException      A RuntimeException is thrown when there is an error while using the SDK.
     */
    public function updateBasket(Basket $basket): Basket;

    /**
     * Creates a PaymentType resource from the given PaymentType object.
     * This is used to create the payment object prior to any transaction.
     * Usually this will be done by the heidelpayUI components (https://docs.heidelpay.com/docs/heidelpay-ui-components)
     *
     * @param BasePaymentType $paymentType
     *
     * @return BasePaymentType|AbstractHeidelpayResource
     *
     * @throws HeidelpayApiException A HeidelpayApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException      A RuntimeException is thrown when there is an error while using the SDK.
     */
    public function createPaymentType(BasePaymentType $paymentType): BasePaymentType;

    /**
     * Updates the PaymentType resource with the given PaymentType object.
     *
     * @param BasePaymentType $paymentType The PaymentType object to be updated.
     *
     * @return BasePaymentType|AbstractHeidelpayResource The updated PaymentType object.
     *
     * @throws HeidelpayApiException A HeidelpayApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException      A RuntimeException is thrown when there is a error while using the SDK.
     */
    public function updatePaymentType(BasePaymentType $paymentType): BasePaymentType;

    /**
     * Fetch the payment type with the given Id from the API.
     *
     * @param string $typeId
     *
     * @return BasePaymentType|AbstractHeidelpayResource
     *
     * @throws HeidelpayApiException A HeidelpayApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException      A RuntimeException is thrown when there is an error while using the SDK.
     */
    public function fetchPaymentType($typeId): BasePaymentType;

    /**
     * Create an API resource for the given customer object.
     *
     * @param Customer $customer The customer object to create the resource for.
     *
     * @return Customer The updated customer object after creation (it should have an id now).
     *
     * @throws HeidelpayApiException A HeidelpayApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException      A RuntimeException is thrown when there is an error while using the SDK.
     */
    public function createCustomer(Customer $customer): Customer;

    /**
     * Create a resource for given customer or updates it if it already exists.
     *
     * @param Customer $customer The customer object to create/update the resource for.
     *
     * @return Customer The updated customer object after creation/update.
     *
     * @throws HeidelpayApiException A HeidelpayApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException      A RuntimeException is thrown when there is an error while using the SDK.
     */
    public function createOrUpdateCustomer(Customer $customer): Customer;

    /**
     * Fetch and return Customer object from API by the given id.
     *
     * @param Customer|string $customer The customer object or id to fetch the customer by.
     *
     * @return Customer The fetched customer object.
     *
     * @throws HeidelpayApiException A HeidelpayApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException      A RuntimeException is thrown when there is an error while using the SDK.
     */
    public function fetchCustomer($customer): Customer;

    /**
     * Fetch and return customer object from API by the given external customer id.
     *
     * @param string $customerId The external customerId to fetch the customer resource by.
     *
     * @return Customer The fetched customer object.
     *
     * @throws HeidelpayApiException A HeidelpayApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException      A RuntimeException is thrown when there is an error while using the SDK.
     */
    public function fetchCustomerByExtCustomerId($customerId): Customer;

    /**
     * Update and return a Customer object via API.
     *
     * @param Customer $customer The locally changed customer date to update the resource in API by.
     *
     * @return Customer The customer object after update.
     *
     * @throws HeidelpayApiException A HeidelpayApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException      A RuntimeException is thrown when there is an error while using the SDK.
     */
    public function updateCustomer(Customer $customer): Customer;

    /**
     * Delete the given Customer resource.
     *
     * @param Customer|string $customer The customer to be deleted. Can be the customer object or its id.
     *
     * @throws HeidelpayApiException A HeidelpayApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException      A RuntimeException is thrown when there is an error while using the SDK.
     */
    public function deleteCustomer($customer): void;

    /**
     * Fetch an authorization object by its payment object or id.
     * Authorization ids are not unique to a merchant but to the payment.
     * A Payment object can have zero or one authorizations.
     *
     * @param Payment|string $payment The payment object or payment id of which to fetch the authorization.
     *
     * @return Authorization|AbstractHeidelpayResource The fetched authorization.
     *
     * @throws HeidelpayApiException A HeidelpayApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException      A RuntimeException is thrown when there is an error while using the SDK.
     */
    public function fetchAuthorization($payment): Authorization;

    /**
     * Fetch a charge object by combination of payment id and charge id.
     * Charge ids are not unique to a merchant but to the payment.
     *
     * @param Payment|string $payment  The payment object or payment id to fetch the authorization from.
     * @param string         $chargeId The id of the charge to fetch.
     *
     * @return Charge|AbstractHeidelpayResource The fetched charge.
     *
     * @throws HeidelpayApiException A HeidelpayApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException      A RuntimeException is thrown when there is an error while using the SDK.
     */
    public function fetchChargeById($payment, $chargeId): Charge;

    /**
     * Update local charge object.
     *
     * @param Charge $charge The charge object to be fetched.
     *
     * @return Charge|AbstractHeidelpayResource The fetched charge.
     *
     * @throws HeidelpayApiException A HeidelpayApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException      A RuntimeException is thrown when there is an error while using the SDK.
     */
    public function fetchCharge(Charge $charge): Charge;

    /**
     * Fetch a cancellation on an authorization (aka reversal).
     *
     * @param Authorization $authorization  The authorization object for which to fetch the cancellation.
     * @param string        $cancellationId The id of the cancellation to fetch.
     *
     * @return Cancellation The fetched cancellation (reversal).
     *
     * @throws HeidelpayApiException A HeidelpayApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException      A RuntimeException is thrown when there is an error while using the SDK.
     */
    public function fetchReversalByAuthorization($authorization, $cancellationId): Cancellation;

    /**
     * Fetches a cancellation resource on an authorization (aka reversal) via payment and cancellation id.
     *
     * @param Payment|string $payment        The payment object or id of the payment to fetch the cancellation for.
     * @param string         $cancellationId The id of the cancellation to fetch.
     *
     * @return Cancellation The fetched cancellation (reversal).
     *
     * @throws HeidelpayApiException A HeidelpayApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException      A RuntimeException is thrown when there is an error while using the SDK.
     */
    public function fetchReversal($payment, $cancellationId): Cancellation;

    /**
     * Fetch a cancellation resource on a charge (aka refund) via id.
     *
     * @param Payment|string $payment        The payment object or id of the payment to fetch the cancellation for.
     * @param string         $chargeId       The id of the charge to fetch the cancellation for.
     * @param string         $cancellationId The id of the cancellation to fetch.
     *
     * @return Cancellation The fetched cancellation (refund).
     *
     * @throws HeidelpayApiException A HeidelpayApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException      A RuntimeException is thrown when there is an error while using the SDK.
     */
    public function fetchRefundById($payment, $chargeId, $cancellationId): Cancellation;

    /**
     * Fetch a cancellation resource on a Charge (aka refund).
     *
     * @param Charge $charge         The charge object to fetch the cancellation for.
     * @param string $cancellationId The id of the cancellation to fetch.
     *
     * @return Cancellation The fetched cancellation (refund).
     *
     * @throws HeidelpayApiException A HeidelpayApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException      A RuntimeException is thrown when there is an error while using the SDK.
     */
    public function fetchRefund(Charge $charge, $cancellationId): Cancellation;

    /**
     * Fetch a shipment resource of the given payment by id.
     *
     * @param Payment|string $payment    The payment object or id of the payment to fetch the cancellation for.
     * @param string         $shipmentId The id of the shipment to be fetched.
     *
     * @return Shipment The fetched shipment object.
     *
     * @throws HeidelpayApiException A HeidelpayApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException      A RuntimeException is thrown when there is an error while using the SDK.
     */
    public function fetchShipment($payment, $shipmentId): Shipment;
}
