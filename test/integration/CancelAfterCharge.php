<?php
/**
 * This class defines integration tests to verify cancellation of charges.
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
 * @package  heidelpay/mgw_sdk/tests/integration
 */
namespace heidelpay\MgwPhpSdk\test\integration;

use heidelpay\MgwPhpSdk\Constants\Currency;
use heidelpay\MgwPhpSdk\test\BasePaymentTest;

class CancelAfterCharge extends BasePaymentTest
{
    /**
     * Verify charge can be fetched by id.
     *
     * @test
     */
    public function chargeShouldBeFetchable()
    {
        $card = $this->heidelpay->createPaymentType($this->createCard());
        $charge = $this->heidelpay->charge(100.0000, Currency::EUROPEAN_EURO, $card, self::RETURN_URL);
        $fetchedCharge = $this->heidelpay->fetchCharge($charge->getPayment()->getId(), $charge->getId());

        $this->assertEquals($charge->expose(), $fetchedCharge->expose());
    }
}