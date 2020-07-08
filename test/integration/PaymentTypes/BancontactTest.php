<?php

namespace heidelpayPHP\test\integration\PaymentTypes;

use heidelpayPHP\Constants\ApiResponseCodes;
use heidelpayPHP\Exceptions\HeidelpayApiException;
use heidelpayPHP\Resources\PaymentTypes\Bancontact;
use heidelpayPHP\test\BasePaymentTest;

class BancontactTest extends BasePaymentTest
{
    /**
     * Verify bancontact can be created and fetched.
     *
     * @test
     *
     * @throws \heidelpayPHP\Exceptions\HeidelpayApiException
     */
    public function bancontactShouldBeCreateableAndFetchable(): void
    {
        $bancontact = new Bancontact();
        $this->heidelpay->createPaymentType($bancontact);
        $this->assertNotNull($bancontact->getId());

        $this->heidelpay->fetchPaymentType($bancontact->getId());
        $this->assertInstanceOf(Bancontact::class, $bancontact);
    }

    /**
     * Verify that an exception is thrown when giropay authorize is called.
     *
     * @test
     */
    public function bancontactShouldThrowExceptionOnAuthorize()
    {
        $this->expectException(HeidelpayApiException::class);
        $this->expectExceptionCode(ApiResponseCodes::API_ERROR_TRANSACTION_AUTHORIZE_NOT_ALLOWED);

        $bancontact = $this->heidelpay->createPaymentType(new Bancontact());
        $this->heidelpay->authorize(100.0, 'EUR', $bancontact, self::RETURN_URL);
    }
    
    /**
     * Verify that Bancontact is chargable
     *
     * @test
     */
    public function bancontactShouldBeChargeable()
    {
        $bancontact = $this->heidelpay->createPaymentType(new Bancontact());
        $charge = $bancontact->charge(100.0, 'EUR', self::RETURN_URL);
        $this->assertNotNull($charge->getId());
        $this->assertNotEmpty($charge->getRedirectUrl());
    }
}
