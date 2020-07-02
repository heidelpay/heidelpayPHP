<?php
/**
 * This represents the basket resource.
 *
 * Copyright (C) 2018 Heidelpay GmbH
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
 * @package  heidelpayPHP\Resources
 */
namespace heidelpayPHP\Resources;

use heidelpayPHP\Adapter\HttpAdapterInterface;
use heidelpayPHP\Resources\EmbeddedResources\BasketItem;
use stdClass;
use function count;

class Basket extends AbstractHeidelpayResource
{
    /** @var float $amountTotalGross */
    protected $amountTotalGross = 0.0;

    /** @var float $amountTotalDiscount */
    protected $amountTotalDiscount = 0.0;

    /** @var float $amountTotalVat */
    protected $amountTotalVat = 0.0;

    /** @var string $currencyCode */
    protected $currencyCode;

    /** @var string $orderId */
    protected $orderId = '';

    /** @var string $note */
    protected $note;

    /** @var array $basketItems */
    private $basketItems;

    /**
     * Basket constructor.
     *
     * @param float  $amountTotalGross
     * @param string $currencyCode
     * @param string $orderId
     * @param array  $basketItems
     */
    public function __construct(
        string $orderId = '',
        float $amountTotalGross = 0.0,
        string $currencyCode = 'EUR',
        array $basketItems = []
    ) {
        $this->currencyCode     = $currencyCode;
        $this->orderId          = $orderId;
        $this->setAmountTotalGross($amountTotalGross);
        $this->setBasketItems($basketItems);
    }

    //<editor-fold desc="Getters/Setters">

    /**
     * @return float
     */
    public function getAmountTotalGross(): float
    {
        return $this->amountTotalGross;
    }

    /**
     * @param float $amountTotalGross
     *
     * @return Basket
     */
    public function setAmountTotalGross(float $amountTotalGross): Basket
    {
        $this->amountTotalGross = $amountTotalGross;
        return $this;
    }

    /**
     * @return float
     *
     * @deprecated since 1.2.0.0
     * @see Basket::getAmountTotalGross()
     */
    public function getAmountTotal(): float
    {
        return $this->getAmountTotalGross();
    }

    /**
     * @param float $amountTotal
     *
     * @return Basket
     *
     * @deprecated since 1.2.0.0
     * @see Basket::setAmountTotalGross()
     */
    public function setAmountTotal(float $amountTotal): Basket
    {
        return $this->setAmountTotalGross($amountTotal);
    }

    /**
     * @return float
     */
    public function getAmountTotalDiscount(): float
    {
        return $this->amountTotalDiscount;
    }

    /**
     * @param float $amountTotalDiscount
     *
     * @return Basket
     */
    public function setAmountTotalDiscount(float $amountTotalDiscount): Basket
    {
        $this->amountTotalDiscount = $amountTotalDiscount;
        return $this;
    }

    /**
     * @return float
     */
    public function getAmountTotalVat(): float
    {
        return $this->amountTotalVat;
    }

    /**
     * @param float $amountTotalVat
     *
     * @return Basket
     */
    public function setAmountTotalVat(float $amountTotalVat): Basket
    {
        $this->amountTotalVat = $amountTotalVat;
        return $this;
    }

    /**
     * @return string
     */
    public function getCurrencyCode(): string
    {
        return $this->currencyCode;
    }

    /**
     * @param string $currencyCode
     *
     * @return Basket
     */
    public function setCurrencyCode(string $currencyCode): Basket
    {
        $this->currencyCode = $currencyCode;
        return $this;
    }

    /**
     * @return int
     */
    public function getItemCount(): int
    {
        return count($this->basketItems);
    }

    /**
     * @return string|null
     */
    public function getNote(): ?string
    {
        return $this->note;
    }

    /**
     * @param string|null $note
     *
     * @return Basket
     */
    public function setNote($note): Basket
    {
        $this->note = $note;
        return $this;
    }

    /**
     * @return string
     */
    public function getOrderId(): string
    {
        return $this->orderId;
    }

    /**
     * @param string $orderId
     *
     * @return Basket
     */
    public function setOrderId(string $orderId): Basket
    {
        $this->orderId = $orderId;
        return $this;
    }

    /**
     * @return array
     */
    public function getBasketItems(): array
    {
        return $this->basketItems;
    }

    /**
     * @param array $basketItems
     *
     * @return Basket
     */
    public function setBasketItems(array $basketItems): Basket
    {
        $this->basketItems = [];

        foreach ($basketItems as $basketItem) {
            $this->addBasketItem($basketItem);
        }

        return $this;
    }

    /**
     * Adds the given BasketItem to the Basket.
     *
     * @param BasketItem $basketItem
     *
     * @return Basket
     */
    public function addBasketItem(BasketItem $basketItem): Basket
    {
        $this->basketItems[] = $basketItem;
        if ($basketItem->getBasketItemReferenceId() === null) {
            $basketItem->setBasketItemReferenceId((string)$this->getKeyOfLastBasketItemAdded());
        }
        return $this;
    }

    /**
     * @param int $index
     *
     * @return BasketItem|null
     */
    public function getBasketItemByIndex($index): ?BasketItem
    {
        return $this->basketItems[$index] ?? null;
    }

    //</editor-fold>

    //<editor-fold desc="Overridable Methods">

    /**
     * Add the dynamically set meta data.
     * {@inheritDoc}
     */
    public function expose(): array
    {
        $basketItemArray = [];

        /** @var BasketItem $basketItem */
        foreach ($this->getBasketItems() as $basketItem) {
            $basketItemArray[] = $basketItem->expose();
        }

        $returnArray = parent::expose();
        $returnArray['basketItems'] = $basketItemArray;

        return $returnArray;
    }

    /**
     * Returns the key of the last BasketItem in the Array.
     *
     * @return int|string|null
     */
    private function getKeyOfLastBasketItemAdded()
    {
        end($this->basketItems);
        return key($this->basketItems);
    }

    /**
     * {@inheritDoc}
     */
    protected function getResourcePath(): string
    {
        return 'baskets';
    }

    /**
     * {@inheritDoc}
     */
    public function handleResponse(stdClass $response, $method = HttpAdapterInterface::REQUEST_GET): void
    {
        parent::handleResponse($response, $method);

        if (isset($response->basketItems)) {
            $items = [];
            foreach ($response->basketItems as $basketItem) {
                $item = new BasketItem();
                $item->handleResponse($basketItem);
                $items[] = $item;
            }
            $this->setBasketItems($items);
        }
    }

    //</editor-fold>
}
