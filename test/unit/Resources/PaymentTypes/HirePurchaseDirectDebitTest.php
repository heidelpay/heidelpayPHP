<?php
/**
 * This class defines unit tests to verify functionality of FlexiPay Rate Direct Debit (Hire Purchase) payment type.
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
 * @package  heidelpayPHP/test/unit
 */
namespace heidelpayPHP\test\unit\Resources\PaymentTypes;

use heidelpayPHP\Resources\PaymentTypes\HirePurchaseDirectDebit;
use heidelpayPHP\test\BasePaymentTest;
use PHPUnit\Framework\Exception;

class HirePurchaseDirectDebitTest extends BasePaymentTest
{
    /**
     * Verify setter and getter work.
     *
     * @test
     *
     * @throws Exception
     * @throws \Exception
     */
    public function getterAndSetterWorkAsExpected()
    {
        $hdd = new HirePurchaseDirectDebit();
        $this->assertEmpty($hdd->getTransactionParams());
        $this->assertNull($hdd->getAccountHolder());
        $this->assertNull($hdd->getIban());
        $this->assertNull($hdd->getBic());
        $this->assertNull($hdd->getOrderDate());
        $this->assertNull($hdd->getNumberOfRates());
        $this->assertNull($hdd->getDayOfPurchase());
        $this->assertNull($hdd->getTotalPurchaseAmount());
        $this->assertNull($hdd->getTotalInterestAmount());
        $this->assertNull($hdd->getTotalAmount());
        $this->assertNull($hdd->getEffectiveInterestRate());
        $this->assertNull($hdd->getNominalInterestRate());
        $this->assertNull($hdd->getFeeFirstRate());
        $this->assertNull($hdd->getFeePerRate());
        $this->assertNull($hdd->getMonthlyRate());
        $this->assertNull($hdd->getLastRate());
        $this->assertEmpty($hdd->getInvoiceDate());
        $this->assertEmpty($hdd->getInvoiceDueDate());

        $hdd->setAccountHolder(null)
            ->setIban(null)
            ->setBic(null)
            ->setOrderDate(null)
            ->setNumberOfRates(null)
            ->setDayOfPurchase(null)
            ->setTotalPurchaseAmount(null)
            ->setTotalInterestAmount(null)
            ->setTotalAmount(null)
            ->setEffectiveInterestRate(null)
            ->setNominalInterestRate(null)
            ->setFeeFirstRate(null)
            ->setFeePerRate(null)
            ->setMonthlyRate(null)
            ->setLastRate(null)
            ->setInvoiceDate(null)
            ->setInvoiceDueDate(null);

        $this->assertEmpty($hdd->getTransactionParams());
        $this->assertNull($hdd->getAccountHolder());
        $this->assertNull($hdd->getIban());
        $this->assertNull($hdd->getBic());
        $this->assertNull($hdd->getOrderDate());
        $this->assertNull($hdd->getNumberOfRates());
        $this->assertNull($hdd->getDayOfPurchase());
        $this->assertNull($hdd->getTotalPurchaseAmount());
        $this->assertNull($hdd->getTotalInterestAmount());
        $this->assertNull($hdd->getTotalAmount());
        $this->assertNull($hdd->getEffectiveInterestRate());
        $this->assertNull($hdd->getNominalInterestRate());
        $this->assertNull($hdd->getFeeFirstRate());
        $this->assertNull($hdd->getFeePerRate());
        $this->assertNull($hdd->getMonthlyRate());
        $this->assertNull($hdd->getLastRate());
        $this->assertEmpty($hdd->getInvoiceDate());
        $this->assertEmpty($hdd->getInvoiceDueDate());

        $hdd->setAccountHolder('My Name')
            ->setIban('my IBAN')
            ->setBic('my BIC')
            ->setOrderDate($this->getYesterdaysTimestamp()->format('Y-m-d'))
            ->setNumberOfRates(15)
            ->setDayOfPurchase($this->getTodaysDateString())
            ->setTotalPurchaseAmount(119.0)
            ->setTotalInterestAmount(0.96)
            ->setTotalAmount(119.96)
            ->setEffectiveInterestRate(4.99)
            ->setNominalInterestRate(4.92)
            ->setFeeFirstRate(0)
            ->setFeePerRate(0)
            ->setMonthlyRate(39.99)
            ->setLastRate(39.98)
            ->setInvoiceDate($this->getTomorrowsTimestamp()->format('Y-m-d'))
            ->setInvoiceDueDate($this->getNextYearsTimestamp()->format('Y-m-d'));

        $this->assertEquals('My Name', $hdd->getAccountHolder());
        $this->assertEquals('my IBAN', $hdd->getIban());
        $this->assertEquals('my BIC', $hdd->getBic());
        $this->assertEquals($this->getYesterdaysTimestamp()->format('Y-m-d'), $hdd->getOrderDate());
        $this->assertEquals(15, $hdd->getNumberOfRates());
        $this->assertEquals($this->getTodaysDateString(), $hdd->getDayOfPurchase());
        $this->assertEquals(119.0, $hdd->getTotalPurchaseAmount());
        $this->assertEquals(0.96, $hdd->getTotalInterestAmount());
        $this->assertEquals(119.96, $hdd->getTotalAmount());
        $this->assertEquals(4.99, $hdd->getEffectiveInterestRate());
        $this->assertEquals(4.92, $hdd->getNominalInterestRate());
        $this->assertEquals(0, $hdd->getFeeFirstRate());
        $this->assertEquals(0, $hdd->getFeePerRate());
        $this->assertEquals(39.99, $hdd->getMonthlyRate());
        $this->assertEquals(39.98, $hdd->getLastRate());
        $this->assertEquals($this->getTomorrowsTimestamp()->format('Y-m-d'), $hdd->getInvoiceDate());
        $this->assertEquals($this->getNextYearsTimestamp()->format('Y-m-d'), $hdd->getInvoiceDueDate());
        $this->assertEquals(['effectiveInterestRate' => $hdd->getEffectiveInterestRate()], $hdd->getTransactionParams());

    }
}