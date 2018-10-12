<?php
/**
 * This class defines integration tests to verify interface and
 * functionality of the payment method sepa direct debit.
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
 * @copyright Copyright © 2016-present heidelpay GmbH. All rights reserved.
 *
 * @link  http://dev.heidelpay.com/
 *
 * @author  Simon Gabriel <development@heidelpay.com>
 *
 * @package  heidelpay/mgw_sdk/tests/integration/payment_types
 */
namespace heidelpay\MgwPhpSdk\test\integration\PaymentTypes;

use heidelpay\MgwPhpSdk\Constants\ApiResponseCodes;
use heidelpay\MgwPhpSdk\Constants\Currency;
use heidelpay\MgwPhpSdk\Exceptions\HeidelpayApiException;
use heidelpay\MgwPhpSdk\Resources\PaymentTypes\SepaDirectDebit;
use heidelpay\MgwPhpSdk\test\BasePaymentTest;

class SepaDirectDebitTest extends BasePaymentTest
{
    /**
     * Verify sepa direct debit can be created.
     *
     * @test
     */
    public function sepaDirectDebitShouldBeCreatableWithMandatoryFieldsOnly()
    {
        /** @var SepaDirectDebit $directDebit */
        $directDebit = new SepaDirectDebit('DE89370400440532013000');
        $directDebit = $this->heidelpay->createPaymentType($directDebit);
        $this->assertInstanceOf(SepaDirectDebit::class, $directDebit);
        $this->assertNotNull($directDebit->getId());

        /** @var SepaDirectDebit $fetchedDirectDebit */
        $fetchedDirectDebit = $this->heidelpay->fetchPaymentType($directDebit->getId());
        $this->assertInstanceOf(SepaDirectDebit::class, $fetchedDirectDebit);
        $this->assertEquals($directDebit->getId(), $fetchedDirectDebit->getId());
        $this->assertEquals($this->maskNumber($directDebit->getIban()), $fetchedDirectDebit->getIban());
    }

    /**
     * Verify sepa direct debit can be created.
     *
     * @test
     *
     * @return SepaDirectDebit
     */
    public function sepaDirectDebitShouldBeCreatable(): SepaDirectDebit
    {
        /** @var SepaDirectDebit $directDebit */
        $directDebit = (new SepaDirectDebit('DE89370400440532013000'))
            ->setHolder('Max Mustermann')
            ->setBic('TEST1234');
        $directDebit = $this->heidelpay->createPaymentType($directDebit);
        $this->assertInstanceOf(SepaDirectDebit::class, $directDebit);
        $this->assertNotNull($directDebit->getId());

        /** @var SepaDirectDebit $fetchedDirectDebit */
        $fetchedDirectDebit = $this->heidelpay->fetchPaymentType($directDebit->getId());
        $this->assertInstanceOf(SepaDirectDebit::class, $fetchedDirectDebit);
        $this->assertEquals($directDebit->getId(), $fetchedDirectDebit->getId());
        $this->assertEquals($directDebit->getHolder(), $fetchedDirectDebit->getHolder());
        $this->assertEquals($directDebit->getBic(), $fetchedDirectDebit->getBic());
        $this->assertEquals($this->maskNumber($directDebit->getIban()), $fetchedDirectDebit->getIban());

        return $fetchedDirectDebit;
    }

    /**
     * Verify authorization is not allowed for sepa direct debit.
     *
     * @test
     *
     * @param SepaDirectDebit $directDebit
     * @depends sepaDirectDebitShouldBeCreatable
     */
    public function authorizeShouldThrowException(SepaDirectDebit $directDebit)
    {
        $this->expectException(HeidelpayApiException::class);
        $this->expectExceptionCode(ApiResponseCodes::API_ERROR_TRANSACTION_AUTHORIZE_NOT_ALLOWED);

        $directDebit->authorize(1.0, Currency::EURO, self::RETURN_URL);
    }

    /**
     * @test
     *
     * @param SepaDirectDebit $directDebit
     * @depends sepaDirectDebitShouldBeCreatable
     */
    public function directDebitShouldBeChargeable(SepaDirectDebit $directDebit)
    {
        $charge = $directDebit->charge(100.0, Currency::EURO, self::RETURN_URL, $this->getMaximumCustomer());
        $this->assertNotNull($charge);
        $this->assertNotNull($charge->getId());
    }
}