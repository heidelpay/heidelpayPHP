<?php
/**
 * Represents the address resource.
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
 * @package  heidelpayPHP\Resources\EmbeddedResources
 */
namespace heidelpayPHP\Resources\EmbeddedResources;

use heidelpayPHP\Resources\AbstractHeidelpayResource;

class Address extends AbstractHeidelpayResource
{
    /** @var string $name */
    protected $name;

    /** @var string $street */
    protected $street;

    /** @var string $state */
    protected $state;

    /** @var string $zip */
    protected $zip;

    /** @var string city */
    protected $city;

    /** @var string country */
    protected $country;

    //<editor-fold desc="Getters/Setters">

    /**
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return Address
     */
    public function setName($name): Address
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getStreet(): ?string
    {
        return $this->street;
    }

    /**
     * @param string $street
     *
     * @return Address
     */
    public function setStreet($street): Address
    {
        $this->street = $street;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getState(): ?string
    {
        return $this->state;
    }

    /**
     * @param string $state
     *
     * @return Address
     */
    public function setState($state): Address
    {
        $this->state = $state;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getZip(): ?string
    {
        return $this->zip;
    }

    /**
     * @param string $zip
     *
     * @return Address
     */
    public function setZip($zip): Address
    {
        $this->zip = $zip;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getCity(): ?string
    {
        return $this->city;
    }

    /**
     * @param string $city
     *
     * @return Address
     */
    public function setCity($city): Address
    {
        $this->city = $city;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getCountry(): ?string
    {
        return $this->country;
    }

    /**
     * @param string $country
     *
     * @return Address
     */
    public function setCountry($country): Address
    {
        $this->country = $country;
        return $this;
    }

    //</editor-fold>
}
