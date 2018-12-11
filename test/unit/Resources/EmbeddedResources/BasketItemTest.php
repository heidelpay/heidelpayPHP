<?php
/**
 * This class defines unit tests to verify functionality of the embedded BasketItem resource.
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
 * @package  heidelpayPHP/test/unit
 */
namespace heidelpayPHP\test\unit\Resources;

use heidelpayPHP\Resources\EmbeddedResources\BasketItem;
use heidelpayPHP\test\BaseUnitTest;
use PHPUnit\Framework\Exception;
use PHPUnit\Framework\ExpectationFailedException;

class BasketItemTest extends BaseUnitTest
{
    /**
     * Verify setter and getter functionalities.
     *
     * @test
     *
     * @throws Exception
     * @throws ExpectationFailedException
     */
    public function settersAndGettersShouldWork()
    {
        $basketItem = new BasketItem();
        $this->assertEquals(0, $basketItem->getPosition());
        $this->assertEquals(0, $basketItem->getQuantity());
        $this->assertEquals(0, $basketItem->getAmountDiscount());
        $this->assertEquals(0, $basketItem->getAmountGross());
        $this->assertEquals(0, $basketItem->getAmountPerUnit());
        $this->assertEquals(0, $basketItem->getVat());
        $this->assertEquals('', $basketItem->getChannel());
        $this->assertEquals('', $basketItem->getBasketItemReferenceId());
        $this->assertEquals('', $basketItem->getUnit());
        $this->assertEquals('', $basketItem->getArticleId());
        $this->assertEquals('', $basketItem->getType());
        $this->assertEquals('', $basketItem->getTitle());
        $this->assertEquals('', $basketItem->getDescription());
        $this->assertEquals('', $basketItem->getImageUrl());
        $this->assertEquals('', $basketItem->getUsage());

        $basketItem->setPosition(1);
        $basketItem->setQuantity(2);
        $basketItem->setAmountDiscount(9876);
        $basketItem->setAmountGross(8765);
        $basketItem->setAmountPerUnit(7654);
        $basketItem->setVat(6543);
        $basketItem->setChannel('myChannel');
        $basketItem->setBasketItemReferenceId('myRefId');
        $basketItem->setUnit('myUnit');
        $basketItem->setArticleId('myArticleId');
        $basketItem->setType('myType');
        $basketItem->setTitle('myTitle');
        $basketItem->setDescription('myDescription');
        $basketItem->setImageUrl('my.image.url');
        $basketItem->setUsage('myUsage');

        $this->assertEquals(1, $basketItem->getPosition());
        $this->assertEquals(2, $basketItem->getQuantity());
        $this->assertEquals(9876, $basketItem->getAmountDiscount());
        $this->assertEquals(8765, $basketItem->getAmountGross());
        $this->assertEquals(7654, $basketItem->getAmountPerUnit());
        $this->assertEquals(6543, $basketItem->getVat());
        $this->assertEquals('myChannel', $basketItem->getChannel());
        $this->assertEquals('myRefId', $basketItem->getBasketItemReferenceId());
        $this->assertEquals('myUnit', $basketItem->getUnit());
        $this->assertEquals('myArticleId', $basketItem->getArticleId());
        $this->assertEquals('myType', $basketItem->getType());
        $this->assertEquals('myTitle', $basketItem->getTitle());
        $this->assertEquals('myDescription', $basketItem->getDescription());
        $this->assertEquals('my.image.url', $basketItem->getImageUrl());
        $this->assertEquals('myUsage', $basketItem->getUsage());
    }
}