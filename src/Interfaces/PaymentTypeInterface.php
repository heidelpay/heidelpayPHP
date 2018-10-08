<?php
/**
 * This interface defines the methods for payment types.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * @license http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * @copyright Copyright © 2016-present heidelpay GmbH. All rights reserved.
 *
 * @link  http://dev.heidelpay.com/
 *
 * @author  Simon Gabriel <development@heidelpay.com>
 *
 * @package  heidelpay/mgw_sdk/interfaces
 */
namespace heidelpay\MgwPhpSdk\Interfaces;

use heidelpay\MgwPhpSdk\Resources\Customer;
use heidelpay\MgwPhpSdk\Exceptions\IllegalTransactionTypeException;
use heidelpay\MgwPhpSdk\Resources\TransactionTypes\Authorization;
use heidelpay\MgwPhpSdk\Resources\TransactionTypes\Charge;

interface PaymentTypeInterface extends HeidelpayResourceInterface
{
    /**
     * @param null $amount
     * @param null $currency
     * @param string $returnUrl
     * @param Customer|null $customer
     * @return Charge
     */
    public function charge($amount, $currency, $returnUrl, $customer = null): Charge;

    /**
     * @param float $amount
     * @param string $currency
     * @param string $returnUrl
     * @param null $customer
     * @return Authorization
     * @throws IllegalTransactionTypeException
     */
    public function authorize($amount, $currency, $returnUrl, $customer = null): Authorization;

    /**
     * @return bool
     */
    public function isChargeable(): bool;

    /**
     * @return bool
     */
    public function isAuthorizable(): bool;
}
