<?php
/**
 * This represents the customer resource.
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
 * @package  heidelpayPHP\Resources
 */
namespace heidelpayPHP\Resources;

use heidelpayPHP\Adapter\HttpAdapterInterface;
use heidelpayPHP\Constants\Salutations;
use heidelpayPHP\Resources\EmbeddedResources\Address;
use heidelpayPHP\Resources\EmbeddedResources\CompanyInfo;
use heidelpayPHP\Traits\HasGeoLocation;
use stdClass;
use function in_array;

class Customer extends AbstractHeidelpayResource
{
    use HasGeoLocation;

    /** @var string $firstname */
    protected $firstname;

    /** @var string $lastname */
    protected $lastname;

    /** @var string $salutation */
    protected $salutation = Salutations::UNKNOWN;

    /** @var string $birthDate */
    protected $birthDate;

    /** @var string $company*/
    protected $company;

    /** @var string $email*/
    protected $email;

    /** @var string $phone*/
    protected $phone;

    /** @var string $mobile*/
    protected $mobile;

    /** @var Address $billingAddress */
    protected $billingAddress;

    /** @var Address $shippingAddress */
    protected $shippingAddress;

    /** @var string $customerId */
    protected $customerId;

    /** @var CompanyInfo $companyInfo */
    protected $companyInfo;

    /**
     * Customer constructor.
     *
     * @param string|null $firstname
     * @param string|null $lastname
     *
     * @deprecated since Version 1.1.5.0
     * @see CustomerFactory::createCustomer()
     * @see CustomerFactory::createNotRegisteredB2bCustomer()
     * @see CustomerFactory::createRegisteredB2bCustomer()
     */
    public function __construct(string $firstname = null, string $lastname = null)
    {
        $this->firstname = $firstname;
        $this->lastname = $lastname;
        $this->billingAddress = new Address();
        $this->shippingAddress = new Address();
    }

    //<editor-fold desc="Getters/Setters">

    /**
     * @return string|null
     */
    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    /**
     * @param string $firstname
     *
     * @return Customer
     */
    public function setFirstname($firstname): Customer
    {
        $this->firstname = $firstname;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    /**
     * @param string $lastname
     *
     * @return Customer
     */
    public function setLastname($lastname): Customer
    {
        $this->lastname = $lastname;
        return $this;
    }

    /**
     * @return string
     */
    public function getSalutation(): string
    {
        return $this->salutation;
    }

    /**
     * @param string $salutation
     *
     * @return Customer
     */
    public function setSalutation($salutation): Customer
    {
        $allowedSalutations = [Salutations::MR, Salutations::MRS, Salutations::UNKNOWN];
        $this->salutation = in_array($salutation, $allowedSalutations, true) ? $salutation : Salutations::UNKNOWN;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getBirthDate(): ?string
    {
        return $this->birthDate;
    }

    /**
     * @param string $birthday
     *
     * @return Customer
     */
    public function setBirthDate($birthday): Customer
    {
        $this->birthDate = $birthday;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getCompany(): ?string
    {
        return $this->company;
    }

    /**
     * @param string $company
     *
     * @return Customer
     */
    public function setCompany($company): Customer
    {
        $this->company = $company;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * @param string $email
     *
     * @return Customer
     */
    public function setEmail($email): Customer
    {
        $this->email = $email;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getPhone(): ?string
    {
        return $this->phone;
    }

    /**
     * @param string $phone
     *
     * @return Customer
     */
    public function setPhone($phone): Customer
    {
        $this->phone = $phone;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getMobile(): ?string
    {
        return $this->mobile;
    }

    /**
     * @param string $mobile
     *
     * @return Customer
     */
    public function setMobile($mobile): Customer
    {
        $this->mobile = $mobile;
        return $this;
    }

    /**
     * @return Address
     */
    public function getBillingAddress(): Address
    {
        return $this->billingAddress;
    }

    /**
     * @param Address $billingAddress
     *
     * @return Customer
     */
    public function setBillingAddress(Address $billingAddress): Customer
    {
        $this->billingAddress = $billingAddress;
        return $this;
    }

    /**
     * @return Address
     */
    public function getShippingAddress(): Address
    {
        return $this->shippingAddress;
    }

    /**
     * @param Address $shippingAddress
     *
     * @return Customer
     */
    public function setShippingAddress(Address $shippingAddress): Customer
    {
        $this->shippingAddress = $shippingAddress;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getCustomerId(): ?string
    {
        return $this->customerId;
    }

    /**
     * @param string $customerId
     *
     * @return Customer
     */
    public function setCustomerId($customerId): Customer
    {
        $this->customerId = $customerId;
        return $this;
    }

    /**
     * @return CompanyInfo|null
     */
    public function getCompanyInfo(): ?CompanyInfo
    {
        return $this->companyInfo;
    }

    /**
     * @param CompanyInfo $companyInfo
     *
     * @return Customer
     */
    public function setCompanyInfo(CompanyInfo $companyInfo): Customer
    {
        $this->companyInfo = $companyInfo;
        return $this;
    }

    //</editor-fold>

    //<editor-fold desc="Resource IF">

    /**
     * {@inheritDoc}
     */
    protected function getResourcePath(): string
    {
        return 'customers';
    }

    //</editor-fold>

    //<editor-fold desc="Overridable methods">

    /**
     * {@inheritDoc}
     */
    public function getExternalId(): ?string
    {
        return $this->getCustomerId();
    }

    /**
     * {@inheritDoc}
     */
    public function handleResponse(stdClass $response, $method = HttpAdapterInterface::REQUEST_GET): void
    {
        if (isset($response->companyInfo) && $this->companyInfo === null) {
            $this->companyInfo = new CompanyInfo();
        }

        parent::handleResponse($response, $method);
    }

    //</editor-fold>
}
