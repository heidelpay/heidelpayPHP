<?php
/**
 * This trait adds the value property to a class.
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
 * @package  heidelpay/mgw_sdk/traits
 */
namespace heidelpay\MgwPhpSdk\Traits;

use heidelpay\MgwPhpSdk\Constants\Calculation;

trait HasValueHelper
{
    /**
     * Returns true if amount1 is greater than or equal to amount2.
     *
     * @param float $amount1
     * @param float $amount2
     * @return bool
     */
    private function amountIsGreaterThanOrEqual($amount1, $amount2): bool
    {
        $diff = $amount1 - $amount2;
        return $diff > 0.0 || $this->equalsZero($diff);
    }

    /**
     * Returns true if the given amount is smaller than EPSILON.
     *
     * @param float $amount
     * @return bool
     */
    private function equalsZero(float $amount): bool
    {
        return (abs($amount) < Calculation::EPSILON);
    }
}
