<?php
/**
 * Description
 *
 * LICENSE
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * @copyright Copyright © 2016-present heidelpay GmbH. All rights reserved.
 *
 * @link  http://dev.heidelpay.com/
 *
 * @author  Simon Gabriel <development@heidelpay.com>
 *
 * @package  heidelpay/${Package}
 */
namespace heidelpay\NmgPhpSdk\Resources;

use heidelpay\NmgPhpSdk\Constants\TransactionTypes;
use heidelpay\NmgPhpSdk\Exceptions\MissingResourceException;
use heidelpay\NmgPhpSdk\Interfaces\PaymentInterface;
use heidelpay\NmgPhpSdk\Interfaces\PaymentTypeInterface;
use heidelpay\NmgPhpSdk\Traits\HasAmountsTrait;
use heidelpay\NmgPhpSdk\Traits\HasStateTrait;
use heidelpay\NmgPhpSdk\Resources\TransactionTypes\Authorization;
use heidelpay\NmgPhpSdk\Resources\TransactionTypes\Charge;

class Payment extends AbstractHeidelpayResource implements PaymentInterface
{
    use HasAmountsTrait;
    use HasStateTrait;

    /** @var string $redirectUrl */
    private $redirectUrl = '';

    /** @var Authorization $authorize */
    private $authorize;

    /** @var array $charges */
    private $charges = [];

    /** @var Customer $customer */
    private $customer;

    /** @var PaymentTypeInterface $paymentType */
    private $paymentType;

    //<editor-fold desc="Overridable Methods">
    /**
     * {@inheritDoc}
     */
    public function getResourcePath()
    {
        return 'payments';
    }

    /**
     * {@inheritDoc}
     */
    public function handleResponse(\stdClass $response)
    {
        parent::handleResponse($response);

        if (isset($response->state->id)) {
            $this->setState($response->state->id);
        }

        if (isset($response->amount)) {
            $amount = $response->amount;

            if (isset($amount->total, $amount->charged, $amount->canceled, $amount->remaining)) {
                $this->setTotal($amount->total)
                    ->setCharged($amount->charged)
                    ->setCanceled($amount->canceled)
                    ->setRemaining($amount->remaining);
            }
        }

        if (isset($response->resources)) {
            $resources = $response->resources;

            if (isset($resources->paymentId)) {
                $this->setId($resources->paymentId);
            }

            if (isset($resources->customerId) && !empty($resources->customerId)) {
                if (!$this->customer instanceof Customer) {
                    $this->customer = $this->getHeidelpayObject()->fetchCustomerById($resources->customerId);
                } else {
                    $this->customer->fetch();
                }
            }

            if (isset($resources->typeId) && !empty($resources->typeId)) {
                if (!$this->paymentType instanceof PaymentTypeInterface) {
                    $this->paymentType = $this->getHeidelpayObject()->fetchPaymentType($resources->typeId);
                }
            }
        }
        if (isset($response->transactions) && !empty($response->transactions)) {
            foreach ($response->transactions as $transaction) {
                switch ($transaction->type) {
                    case TransactionTypes::AUTHORIZATION:
                        $transactionId = $this->getTransactionId($transaction, 'aut');
                        // todo: refactor
                        $authorization = $this->getAuthorization();
                        if (!$authorization instanceof Authorization) {
                            $authorization = (new Authorization())
                                ->setPayment($this)
                                ->setParentResource($this)
                                ->setId($transactionId);
                            $this->setAuthorization($authorization);
                        }
                        $authorization->setAmount($transaction->amount);
                        break;
                    case TransactionTypes::CHARGE:
                        // todo: like auth
                        break;
                    case TransactionTypes::CANCEL:
                        // todo: like auth
                        break;
                    default:
                        // skip
                        break;
                }
            }
        }

    }
    //</editor-fold>

    //<editor-fold desc="Setters/Getters">
    /**
     * @return string
     */
    public function getRedirectUrl(): string
    {
        return $this->redirectUrl;
    }

    /**
     * @param string $redirectUrl
     * @return Payment
     */
    public function setRedirectUrl(string $redirectUrl): Payment
    {
        $this->redirectUrl = $redirectUrl;
        return $this;
    }

    /**
     * @return Authorization|null
     */
    public function getAuthorization()
    {
        return $this->authorize;
    }

    /**
     * @param Authorization $authorize
     * @return PaymentInterface
     */
    public function setAuthorization(Authorization $authorize): PaymentInterface
    {
        $authorize->setPayment($this);
        $authorize->setParentResource($this);
        $this->authorize = $authorize;
        return $this;
    }

    /**
     * @return array
     */
    public function getCharges(): array
    {
        return $this->charges;
    }

    /**
     * @param array $charges
     * @return Payment
     */
    public function setCharges(array $charges): Payment
    {
        $this->charges = $charges;
        return $this;
    }

    /**
     * @param Charge $charge
     */
    public function addCharge(Charge $charge)
    {
        $this->charges[$charge->getId()] = $charge;
    }

    /**
     * @param Customer $customer
     * @return Payment
     */
    public function setCustomer(Customer $customer): Payment
    {
        $customer->setParentResource($this->getHeidelpayObject());
        $this->customer = $customer;
        return $this;
    }

    /**
     * @return Customer|null
     */
    public function getCustomer()
    {
        return $this->customer;
    }

    /**
     * @return Customer
     */
    public function createCustomer(): Customer
    {
        $this->customer = new Customer($this);
        return $this->customer;
    }

    /**
     * {@inheritDoc}
     */
    public function getPaymentType(): PaymentTypeInterface
    {
        $paymentType = $this->paymentType;
        if (!$paymentType instanceof PaymentTypeInterface) {
            throw new MissingResourceException('The paymentType is not set.');
        }

        return $paymentType;
    }

    /**
     * @param PaymentTypeInterface $paymentType
     * @return Payment
     */
    public function setPaymentType(PaymentTypeInterface $paymentType): Payment
    {
        $this->paymentType = $paymentType;
        return $this;
    }
    //</editor-fold>

    //<editor-fold desc="TransactionTypes">

    /**
     * {@inheritDoc}
     *
     * todo: this should be handled by the api.
     */
    public function fullCharge(): Charge
    {
        // todo: authorization muss erst geholt werden
        if (!$this->getAuthorization() instanceof Authorization) {
            throw new MissingResourceException('Cannot perform full charge without authorization.');
        }

        // charge amount
        return $this->charge($this->getRemaining());
    }

    /**
     * Sets the given paymentType and performs an authorization.
     *
     * @param $amount
     * @param $currency
     * @param $returnUrl
     * @param PaymentTypeInterface $paymentType
     * @return Authorization
     */
    public function authorizeWithPaymentType($amount, $currency, $returnUrl, PaymentTypeInterface $paymentType): Authorization
    {
        return $this->setPaymentType($paymentType)->authorize($amount, $currency, $returnUrl);
    }

    /**
     * Perform a full cancel on the payment.
     * Returns the payment object itself.
     * Cancellation-Object is not returned since on cancelling might affect several charges thus creates several
     * Cancellation-Objects in one go.
     *
     * @return PaymentInterface
     */
    public function fullCancel(): PaymentInterface
    {
        if ($this->authorize instanceof Authorization && !$this->isCompleted()) {
            $this->authorize->cancel();
            return $this;
        }

        $this->cancelAllCharges();

        return $this;
    }

    /**
     * @param float $amount
     * @return PaymentInterface
     */
    public function cancel($amount = null): PaymentInterface
    {
        if (null === $amount) {
            return $this->fullCancel();
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function cancelAllCharges()
    {
        /** @var Charge $charge */
        foreach ($this->getCharges() as $charge) {
            $charge->cancel();
        }
    }

    /**
     * @param $transaction
     * @param $pattern
     * @return mixed
     */
    protected function getTransactionId($transaction, $pattern)
    {
        $matches = [];
        preg_match('~\/([s|p]{1}-' . $pattern . '-[\d]+)~', $transaction->url, $matches);

        if (count($matches) < 2) {
            throw new \RuntimeException('Id not found!');
        }

        return $matches[1];
    }
    //</editor-fold>
}