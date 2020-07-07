<?php
/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDocMissingThrowsInspection */
/**
 * This class defines a dummy implementing traits with customer dependency and with implementing the parent
 * interface.
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
namespace heidelpayPHP\test\unit\Traits;

use heidelpayPHP\Heidelpay;
use heidelpayPHP\Interfaces\HeidelpayParentInterface;
use heidelpayPHP\Traits\CanAuthorizeWithCustomer;
use heidelpayPHP\Traits\CanDirectChargeWithCustomer;
use heidelpayPHP\Traits\CanPayoutWithCustomer;

class TraitDummyWithCustomerWithParentIF implements HeidelpayParentInterface
{
    use CanAuthorizeWithCustomer;
    use CanDirectChargeWithCustomer;
    use CanPayoutWithCustomer;

    /**
     * Returns the heidelpay root object.
     *
     * @return Heidelpay
     */
    public function getHeidelpayObject(): Heidelpay
    {
        return new Heidelpay('s-priv-123');
    }

    /**
     * Returns the url string for this resource.
     *
     * @param bool $appendId
     *
     * @return string
     */
    public function getUri($appendId = true): string
    {
        return 'test/uri/';
    }
}
