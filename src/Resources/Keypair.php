<?php
/**
 * This represents the key pair resource.
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
 * @package  heidelpayPHP\Resources
 */
namespace heidelpayPHP\Resources;

use heidelpayPHP\Adapter\HttpAdapterInterface;
use stdClass;

class Keypair extends AbstractHeidelpayResource
{
    /** @var string $publicKey */
    private $publicKey;

    /** @var string $privateKey */
    private $privateKey;

    /** @var bool $detailed */
    private $detailed = false;

    /** @var array $paymentTypes */
    private $paymentTypes = [];

    /** @var string $secureLevel */
    private $secureLevel;

    /** @var string $alias */
    private $alias;

    /** @var string $merchantName */
    private $merchantName;

    /** @var string $merchantAddress */
    private $merchantAddress;

    /**
     * Credentials on File / Card on File
     * If true the credentials are stored for future transactions.
     *
     * @var bool|null $cof
     */
    private $cof;

    /** @var bool $validateBasket */
    private $validateBasket;

    //<editor-fold desc="Getters/Setters">

    /**
     * @return string|null
     */
    public function getPublicKey(): ?string
    {
        return $this->publicKey;
    }

    /**
     * @param string $publicKey
     */
    protected function setPublicKey(string $publicKey): void
    {
        $this->publicKey = $publicKey;
    }

    /**
     * @return string|null
     */
    public function getPrivateKey(): ?string
    {
        return $this->privateKey;
    }

    /**
     * @param string $privateKey
     */
    protected function setPrivateKey(string $privateKey): void
    {
        $this->privateKey = $privateKey;
    }

    /**
     * @return array
     */
    public function getPaymentTypes(): array
    {
        return $this->paymentTypes;
    }

    /**
     * @param array $paymentTypes
     */
    protected function setPaymentTypes(array $paymentTypes): void
    {
        $this->paymentTypes = $paymentTypes;
    }

    /**
     * @return array
     */
    public function getAvailablePaymentTypes(): array
    {
        return $this->getPaymentTypes();
    }

    /**
     * @param array $paymentTypes
     */
    protected function setAvailablePaymentTypes(array $paymentTypes): void
    {
        $this->setPaymentTypes($paymentTypes);
    }

    /**
     * @return string
     */
    public function getSecureLevel(): string
    {
        return $this->secureLevel ?: '';
    }

    /**
     * @param string|null $secureLevel
     *
     * @return Keypair
     */
    protected function setSecureLevel($secureLevel): Keypair
    {
        $this->secureLevel = $secureLevel;
        return $this;
    }

    /**
     * @return string
     */
    public function getAlias(): string
    {
        return $this->alias ?: '';
    }

    /**
     * @param string|null $alias
     *
     * @return Keypair
     */
    protected function setAlias($alias): Keypair
    {
        $this->alias = $alias;
        return $this;
    }

    /**
     * @return string
     */
    public function getMerchantName(): string
    {
        return $this->merchantName ?: '';
    }

    /**
     * @param string|null $merchantName
     *
     * @return Keypair
     */
    protected function setMerchantName($merchantName): Keypair
    {
        $this->merchantName = $merchantName;
        return $this;
    }

    /**
     * @return string
     */
    public function getMerchantAddress(): string
    {
        return $this->merchantAddress ?: '';
    }

    /**
     * @param string|null $merchantAddress
     *
     * @return Keypair
     */
    protected function setMerchantAddress($merchantAddress): Keypair
    {
        $this->merchantAddress = $merchantAddress;
        return $this;
    }

    /**
     * @return bool
     */
    public function isDetailed(): bool
    {
        return $this->detailed;
    }

    /**
     * @param bool $detailed
     *
     * @return Keypair
     */
    public function setDetailed(bool $detailed): Keypair
    {
        $this->detailed = $detailed;
        return $this;
    }

    /**
     * Returns true if Credentials are stored for later transactions.
     *
     * @return bool|null
     */
    public function isCof(): ?bool
    {
        return $this->cof;
    }

    /**
     * @param bool $cof
     *
     * @return Keypair
     */
    protected function setCof(bool $cof): Keypair
    {
        $this->cof = $cof;
        return $this;
    }

    /**
     * @return bool|null
     */
    public function isValidateBasket(): ?bool
    {
        return $this->validateBasket;
    }

    /**
     * @param bool|null $validateBasket
     *
     * @return Keypair
     */
    protected function setValidateBasket($validateBasket): Keypair
    {
        $this->validateBasket = $validateBasket;
        return $this;
    }

    //</editor-fold>

    //<editor-fold desc="Overridable Methods">

    /**
     * @inheritDoc
     */
    public function handleResponse(stdClass $response, $method = HttpAdapterInterface::REQUEST_GET): void
    {
        parent::handleResponse($response, $method);

        $paymentTypes = [];
        if (isset($response->paymentTypes)) {
            $paymentTypes = $response->paymentTypes;
        } elseif (isset($response->availablePaymentTypes)) {
            $paymentTypes = $response->availablePaymentTypes;
        }

        foreach ($paymentTypes as $paymentType) {
            $this->paymentTypes[] = $paymentType;
        }
    }

    /**
     * @inheritDoc
     */
    protected function getResourcePath(): string
    {
        return parent::getResourcePath() . ($this->isDetailed() ? '/types' : '');
    }

    //</editor-fold>
}
