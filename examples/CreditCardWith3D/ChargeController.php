<?php
/**
 * This is the controller for the 'Charge' transaction example for Card.
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
 * @package  heidelpay/mgw_sdk/examples
 */

/** Require the constants of this example */
require_once __DIR__ . '/Constants.php';

/** Require the composer autoloader file */
require_once __DIR__ . '/../../../../autoload.php';

use heidelpay\MgwPhpSdk\Constants\Currencies;
use heidelpay\MgwPhpSdk\Exceptions\HeidelpayApiException;
use heidelpay\MgwPhpSdk\Heidelpay;
use heidelpay\MgwPhpSdk\Resources\Customer;
use heidelpay\MgwPhpSdk\Resources\Payment;

include '../assets/partials/_controller_php.php';

//#######  1. Catch API and SDK errors, write the message to your log and show the ClientMessage to the client. ########
try {
    //#######  2. Create a heidelpay object using your private key #####################################################
    $heidelpay     = new Heidelpay(EXAMPLE_PRIVATE_KEY);

    //#######  3. Create a charge with a new customer. #################################################################
    $customer      = new Customer('Linda', 'Heideich');
    $charge = $heidelpay->charge(100.0, Currencies::EURO, $paymentTypeId, CHARGE_CONTROLLER_URL, $customer);
} catch (HeidelpayApiException $e) {
    //#######  4. In case of an error redirect to your failure page. ###################################################
    redirect(FAILURE_URL);
}

//#######  5. If everything is fine redirect to your success page. #####################################################
if ($charge->getPayment() instanceof Payment) {
    $_SESSION['paymentId'] = $charge->getPaymentId();

    if ($charge->getPayment()->isCompleted()) {
        redirect(SUCCESS_URL);
    }
}

redirect(FAILURE_URL);