<?php
/** @noinspection MissingOrEmptyGroupStatementInspection */
/** @noinspection PhpStatementHasEmptyBodyInspection */
/**
 * This is the return controller for example implementations.
 * It is called when the client is redirected back to the shop from the external page or when the payment
 * transaction has been sent.
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
 * @package  heidelpayPHP\examples
 */

/** Require the constants of this example */
require_once __DIR__ . '/Constants.php';

/** @noinspection PhpIncludeInspection */
/** Require the composer autoloader file */
require_once __DIR__ . '/../../../autoload.php';

use heidelpayPHP\examples\ExampleDebugHandler;
use heidelpayPHP\Exceptions\HeidelpayApiException;
use heidelpayPHP\Heidelpay;
use heidelpayPHP\Resources\PaymentTypes\Prepayment;
use heidelpayPHP\Resources\TransactionTypes\AbstractTransactionType;
use heidelpayPHP\Resources\TransactionTypes\Authorization;

$clientMessage = 'Something went wrong. Please try again later.';
$merchantMessage = 'Something went wrong. Please try again later.';

function redirect($url, $merchantMessage = '', $clientMessage = '')
{
    $_SESSION['merchantMessage'] = $merchantMessage;
    $_SESSION['clientMessage']   = $clientMessage;
    header('Location: ' . $url);
    die();
}

session_start();

// Retrieve the paymentId you remembered within the Controller
if (!isset($_SESSION['PaymentId'])) {
    redirect(FAILURE_URL, 'The payment id is missing.', $clientMessage);
}
$paymentId = $_SESSION['PaymentId'];

// Catch API errors, write the message to your log and show the ClientMessage to the client.
try {
    // Create a heidelpay object using your private key and register a debug handler if you want to.
    $heidelpay = new Heidelpay(HEIDELPAY_PHP_PAYMENT_API_PRIVATE_KEY);
    $heidelpay->setDebugMode(true)->setDebugHandler(new ExampleDebugHandler());

    // Redirect to success if the payment has been successfully completed.
    $payment   = $heidelpay->fetchPayment($paymentId);
    $transaction = $payment->getInitialTransaction();

    if ($payment->isCompleted()) {
        // The payment process has been successful.
        // You show the success page.
        // Goods can be shipped.
        redirect(SUCCESS_URL);
    } elseif ($payment->isPending()) {
        if ($transaction->isSuccess()) {
            if ($transaction instanceof Authorization) {
                // Payment is ready to be captured.
                // Goods can be shipped later AFTER charge.
            } else {
                // Payment is not done yet (e.g. Prepayment)
                // Goods can be shipped later after incoming payment (event).
            }

            // In any case:
            // * You can show the success page.
            // * You can set order status to pending payment
            redirect(SUCCESS_URL);
        } elseif ($transaction->isPending()) {

            // The initial transaction of invoice types will not change to success but stay pending.
            $paymentType = $payment->getPaymentType();
            if ($paymentType instanceof Prepayment || $paymentType->isInvoiceType()) {
                // Awaiting payment by the customer.
                // Goods can be shipped immediately except for Prepayment type.
                redirect(SUCCESS_URL);
            }

            // In cases of a redirect to an external service (e.g. 3D secure, PayPal, etc) it sometimes takes time for
            // the payment to update it's status after redirect into shop.
            // In this case the payment and the transaction are pending at first and change to cancel or success later.

            // Use the webhooks feature to stay informed about changes of payment and transaction (e.g. cancel, success)
            // then you can handle the states as shown above in transaction->isSuccess() branch.
            redirect(PENDING_URL);
        }
    }
    // If the payment is neither success nor pending something went wrong.
    // In this case do not create the order or cancel it if you already did.
    // Redirect to an error page in your shop and show a message if you want.

    // Check the result message of the initial transaction to find out what went wrong.
    if ($transaction instanceof AbstractTransactionType) {
        // For better debugging log the error message in your error log
        $merchantMessage = $transaction->getMessage()->getMerchant();
        $clientMessage = $transaction->getMessage()->getCustomer();
    }
} catch (HeidelpayApiException $e) {
    $merchantMessage = $e->getMerchantMessage();
    $clientMessage = $e->getClientMessage();
} catch (RuntimeException $e) {
    $merchantMessage = $e->getMessage();
}
// Write the merchant message to your log.
// Show the client message to the customer (it is localized).
redirect(FAILURE_URL, $merchantMessage, $clientMessage);
