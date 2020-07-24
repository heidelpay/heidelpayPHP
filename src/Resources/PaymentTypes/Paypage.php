<?php
/**
 * This is the implementation of the Pay Page which allows for displaying a page containing all
 * payment types of the merchant.
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
 * @package  heidelpayPHP\PaymentTypes
 */
namespace heidelpayPHP\Resources\PaymentTypes;

use heidelpayPHP\Adapter\HttpAdapterInterface;
use heidelpayPHP\Constants\TransactionTypes;
use heidelpayPHP\Exceptions\HeidelpayApiException;
use heidelpayPHP\Resources\AbstractHeidelpayResource;
use heidelpayPHP\Resources\Basket;
use heidelpayPHP\Resources\Customer;
use heidelpayPHP\Resources\Metadata;
use heidelpayPHP\Resources\Payment;
use heidelpayPHP\Traits\CanAuthorize;
use heidelpayPHP\Traits\CanDirectCharge;
use heidelpayPHP\Traits\HasInvoiceId;
use heidelpayPHP\Traits\HasOrderId;
use RuntimeException;
use stdClass;
use function in_array;

class Paypage extends BasePaymentType
{
    use CanDirectCharge;
    use CanAuthorize;
    use HasInvoiceId;
    use HasOrderId;

    /** @var float $amount */
    protected $amount;

    /** @var string $currency*/
    protected $currency;

    /** @var string $returnUrl*/
    protected $returnUrl;

    /** @var string $logoImage */
    protected $logoImage;

    /** @var string $fullPageImage */
    protected $fullPageImage;

    /** @var string $shopName */
    protected $shopName;

    /** @var string $shopDescription */
    protected $shopDescription;

    /** @var string $tagline */
    protected $tagline;

    /** @var string $termsAndConditionUrl */
    protected $termsAndConditionUrl;

    /** @var string $privacyPolicyUrl */
    protected $privacyPolicyUrl;

    /** @var string $imprintUrl */
    protected $imprintUrl;

    /** @var string $helpUrl */
    protected $helpUrl;

    /** @var string $contactUrl */
    protected $contactUrl;

    /** @var String $action */
    private $action = TransactionTypes::CHARGE;

    /** @var Payment|null $payment */
    private $payment;

    /** @var string[] $excludeTypes */
    protected $excludeTypes = [];

    /** @var bool $card3ds */
    protected $card3ds;

    /** @var array $css */
    protected $css = [];

    /**
     * Paypage constructor.
     *
     * @param float  $amount
     * @param string $currency
     * @param string $returnUrl
     */
    public function __construct(float $amount, string $currency, string $returnUrl)
    {
        $this->setAmount($amount);
        $this->setCurrency($currency);
        $this->setReturnUrl($returnUrl);
    }

    //<editor-fold desc="Getters/Setters">

    /**
     * @return float
     */
    public function getAmount(): float
    {
        return $this->amount;
    }

    /**
     * @param float $amount
     *
     * @return Paypage
     */
    public function setAmount(float $amount): Paypage
    {
        $this->amount = $amount;
        return $this;
    }

    /**
     * @return string
     */
    public function getCurrency(): string
    {
        return $this->currency;
    }

    /**
     * @param string $currency
     *
     * @return Paypage
     */
    public function setCurrency(string $currency): Paypage
    {
        $this->currency = $currency;
        return $this;
    }

    /**
     * @return string
     */
    public function getReturnUrl(): string
    {
        return $this->returnUrl;
    }

    /**
     * @param string $returnUrl
     *
     * @return Paypage
     */
    public function setReturnUrl(string $returnUrl): Paypage
    {
        $this->returnUrl = $returnUrl;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getLogoImage(): ?string
    {
        return $this->logoImage;
    }

    /**
     * @param string|null $logoImage
     *
     * @return Paypage
     */
    public function setLogoImage($logoImage): Paypage
    {
        $this->logoImage = $logoImage;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getFullPageImage(): ?string
    {
        return $this->fullPageImage;
    }

    /**
     * @param string|null $fullPageImage
     *
     * @return Paypage
     */
    public function setFullPageImage($fullPageImage): Paypage
    {
        $this->fullPageImage = $fullPageImage;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getShopName(): ?string
    {
        return $this->shopName;
    }

    /**
     * @param string|null $shopName
     *
     * @return Paypage
     */
    public function setShopName($shopName): Paypage
    {
        $this->shopName = $shopName;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getShopDescription(): ?string
    {
        return $this->shopDescription;
    }

    /**
     * @param string|null $shopDescription
     *
     * @return Paypage
     */
    public function setShopDescription($shopDescription): Paypage
    {
        $this->shopDescription = $shopDescription;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getTagline(): ?string
    {
        return $this->tagline;
    }

    /**
     * @param string|null $tagline
     *
     * @return Paypage
     */
    public function setTagline($tagline): Paypage
    {
        $this->tagline = $tagline;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getTermsAndConditionUrl(): ?string
    {
        return $this->termsAndConditionUrl;
    }

    /**
     * @param string|null $termsAndConditionUrl
     *
     * @return Paypage
     */
    public function setTermsAndConditionUrl($termsAndConditionUrl): Paypage
    {
        $this->termsAndConditionUrl = $termsAndConditionUrl;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getPrivacyPolicyUrl(): ?string
    {
        return $this->privacyPolicyUrl;
    }

    /**
     * @param string|null $privacyPolicyUrl
     *
     * @return Paypage
     */
    public function setPrivacyPolicyUrl($privacyPolicyUrl): Paypage
    {
        $this->privacyPolicyUrl = $privacyPolicyUrl;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getImprintUrl(): ?string
    {
        return $this->imprintUrl;
    }

    /**
     * @param string|null $imprintUrl
     *
     * @return Paypage
     */
    public function setImprintUrl($imprintUrl): Paypage
    {
        $this->imprintUrl = $imprintUrl;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getHelpUrl(): ?string
    {
        return $this->helpUrl;
    }

    /**
     * @param string|null $helpUrl
     *
     * @return Paypage
     */
    public function setHelpUrl($helpUrl): Paypage
    {
        $this->helpUrl = $helpUrl;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getContactUrl(): ?string
    {
        return $this->contactUrl;
    }

    /**
     * @param string|null $contactUrl
     *
     * @return Paypage
     */
    public function setContactUrl($contactUrl): Paypage
    {
        $this->contactUrl = $contactUrl;
        return $this;
    }

    /**
     * @return string
     */
    public function getAction(): string
    {
        return $this->action;
    }

    /**
     * @param String $action
     *
     * @return Paypage
     */
    public function setAction(String $action): Paypage
    {
        if (in_array($action, [TransactionTypes::CHARGE, TransactionTypes::AUTHORIZATION], true)) {
            $this->action = $action;
        }

        return $this;
    }

    /**
     * @return Payment|null
     */
    public function getPayment(): ?Payment
    {
        return $this->payment;
    }

    /**
     * @param Payment $payment
     *
     * @return Paypage
     */
    public function setPayment(Payment $payment): Paypage
    {
        $this->payment = $payment;
        return $this;
    }

    /**
     * @return Basket|null
     */
    public function getBasket(): ?Basket
    {
        if (!$this->payment instanceof Payment) {
            return null;
        }
        return $this->payment->getBasket();
    }

    /**
     * @return Customer|null
     */
    public function getCustomer(): ?Customer
    {
        if (!$this->payment instanceof Payment) {
            return null;
        }
        return $this->payment->getCustomer();
    }

    /**
     * @return Metadata|null
     */
    public function getMetadata(): ?Metadata
    {
        if (!$this->payment instanceof Payment) {
            return null;
        }
        return $this->payment->getMetadata();
    }

    /**
     * @return string|null
     */
    public function getRedirectUrl(): ?string
    {
        $payment = $this->getPayment();
        if ($payment instanceof Payment) {
            return $payment->getRedirectUrl();
        }
        return null;
    }

    /**
     * @param string $redirectUrl
     *
     * @return Paypage
     *
     * @throws HeidelpayApiException A HeidelpayApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException      A RuntimeException is thrown when there is an error while using the SDK.
     */
    public function setRedirectUrl(string $redirectUrl): Paypage
    {
        $payment = $this->getPayment();
        if ($payment instanceof Payment) {
            $payment->handleResponse((object)['redirectUrl' => $redirectUrl]);
        }
        return $this;
    }

    /**
     * Return the Id of the referenced payment object.
     *
     * @return null|string The Id of the payment object or null if nothing is found.
     */
    public function getPaymentId(): ?string
    {
        if ($this->payment instanceof Payment) {
            return $this->payment->getId();
        }

        return null;
    }

    /**
     * Returns an array of payment types not shown on the paypage.
     *
     * @return string[]
     */
    public function getExcludeTypes(): array
    {
        return $this->excludeTypes;
    }

    /**
     * Sets array of payment types not shown on the paypage.
     *
     * @param string[] $excludeTypes
     *
     * @return Paypage
     */
    public function setExcludeTypes(array $excludeTypes): Paypage
    {
        $this->excludeTypes = $excludeTypes;
        return $this;
    }

    /**
     * Adds a payment type to the array of excluded payment types.
     *
     * @param string $excludeType The API name of the payment type resource that should not be shown on the paypage.
     *                            It can be retrieved by calling the static function `getResourceName` on the payment
     *                            type class e.g. Card::getResourceName().
     *
     * @return Paypage
     */
    public function addExcludeType(string $excludeType): Paypage
    {
        $this->excludeTypes[] = $excludeType;
        return $this;
    }

    /**
     * @return bool|null
     */
    public function isCard3ds(): ?bool
    {
        return $this->card3ds;
    }

    /**
     * @param bool|null $card3ds
     *
     * @return Paypage
     */
    public function setCard3ds($card3ds): Paypage
    {
        $this->card3ds = $card3ds;
        return $this;
    }

    /**
     * @return array|null
     */
    public function getCss(): ?array
    {
        return $this->css;
    }

    /**
     * @param array $styles
     * @return Paypage
     */
    public function setCss($styles): Paypage
    {
        foreach ($styles as $element => $css) {
            $this->css[$element] = $css;
        }
        return $this;
    }

    /**
     * @param float|null $effectiveInterestRate
     *
     * @return Paypage
     */
    public function setEffectiveInterestRate(float $effectiveInterestRate): Paypage
    {
        $this->setAdditionalAttribute('effectiveInterestRate', $effectiveInterestRate);
        return $this;
    }

    /**
     * @return float|null
     */
    public function getEffectiveInterestRate(): ?float
    {
        return $this->getAdditionalAttribute('effectiveInterestRate');
    }

    //</editor-fold>

    //<editor-fold desc="Overridable methods">

    /**
     * {@inheritDoc}
     * Change resource path.
     */
    protected function getResourcePath(): string
    {
        switch ($this->action) {
            case TransactionTypes::AUTHORIZATION:
                return 'paypage/authorize';
                break;
            case TransactionTypes::CHARGE:
                // intended Fall-Through
            default:
                return 'paypage/charge';
                break;
        }
    }

    /**
     * {@inheritDoc}
     * Map external name of property to internal name of property.
     *
     * @throws HeidelpayApiException A HeidelpayApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException      A RuntimeException is thrown when there is an error while using the SDK.
     */
    public function handleResponse(stdClass $response, $method = HttpAdapterInterface::REQUEST_GET): void
    {
        if (isset($response->impressumUrl)) {
            $response->imprintUrl = $response->impressumUrl;
            unset($response->impressumUrl);
        }

        parent::handleResponse($response, $method);

        /** @var Payment $payment */
        $payment = $this->getPayment();
        if (isset($response->resources->paymentId)) {
            $payment->setId($response->resources->paymentId);
        }

        if ($method !== HttpAdapterInterface::REQUEST_GET) {
            $this->fetchPayment();
        }
    }

    /**
     * {@inheritDoc}
     * Map external name of property to internal name of property.
     */
    public function expose()
    {
        $exposeArray = parent::expose();
        if (isset($exposeArray['imprintUrl'])) {
            $exposeArray['impressumUrl'] = $exposeArray['imprintUrl'];
            unset($exposeArray['imprintUrl']);
        }

        return $exposeArray;
    }

    /**
     * {@inheritDoc}
     */
    public function getLinkedResources(): array
    {
        return [
            'customer'=> $this->getCustomer(),
            'metadata' => $this->getMetadata(),
            'basket' => $this->getBasket(),
            'payment' => $this->getPayment()
        ];
    }

    //</editor-fold>

    /**
     * Updates the referenced payment object if it exists and if this is not the payment object itself.
     * This is called from the crud methods to update the payments state whenever anything happens.
     *
     * @throws HeidelpayApiException A HeidelpayApiException is thrown if there is an error returned on API-request.
     * @throws RuntimeException      A RuntimeException is thrown when there is an error while using the SDK.
     */
    private function fetchPayment(): void
    {
        $payment = $this->getPayment();
        if ($payment instanceof AbstractHeidelpayResource) {
            $this->fetchResource($payment);
        }
    }
}
