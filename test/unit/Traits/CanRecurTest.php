<?php
/**
 * This class defines unit tests to verify functionality of the CanRecur trait.
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
 * @package  heidelpayPHP\test\unit
 */
namespace heidelpayPHP\test\unit\Traits;

use heidelpayPHP\Exceptions\HeidelpayApiException;
use heidelpayPHP\Heidelpay;
use heidelpayPHP\Resources\Recurring;
use heidelpayPHP\test\BasePaymentTest;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\Exception;
use PHPUnit\Framework\MockObject\RuntimeException as PHPUnitRuntimeException;
use ReflectionException;
use RuntimeException;
use stdClass;

class CanRecurTest extends BasePaymentTest
{
    /**
     * Verify setters and getters.
     *
     * @test
     *
     * @throws AssertionFailedError
     */
    public function gettersAndSettersShouldWorkProperly(): void
    {
        $dummy = new TraitDummyCanRecur();
        $this->assertFalse($dummy->isRecurring());
        $response = new stdClass();
        $response->recurring = true;
        $dummy->handleResponse($response);
        $this->assertTrue($dummy->isRecurring());
    }

    /**
     * Verify recurring activation on a resource which is not an abstract resource will throw an exception.
     *
     * @test
     *
     * @throws HeidelpayApiException
     * @throws RuntimeException
     */
    public function activateRecurringWillThrowExceptionIfTheObjectHasWrongType(): void
    {
        $dummy = new TraitDummyCanRecurNonResource();

        $this->expectException(RuntimeException::class);
        $dummy->activateRecurring('1234');
    }

    /**
     * Verify activation on object will call heidelpay.
     *
     * @test
     *
     * @throws Exception
     * @throws HeidelpayApiException
     * @throws ReflectionException
     * @throws RuntimeException
     * @throws PHPUnitRuntimeException
     */
    public function activateRecurringWillCallHeidelpayMethod(): void
    {
        $heidelpayMock = $this->getMockBuilder(Heidelpay::class)->disableOriginalConstructor()->setMethods(['activateRecurringPayment'])->getMock();

        /** @var Heidelpay $heidelpayMock */
        $dummy = (new TraitDummyCanRecur())->setParentResource($heidelpayMock);
        /** @noinspection PhpParamsInspection */
        $heidelpayMock->expects(self::once())->method('activateRecurringPayment')->with($dummy, 'return url')->willReturn(new Recurring('', ''));

        $dummy->activateRecurring('return url');
    }
}
