<?php

namespace heidelpayPHP\test\integration\PaymentTypes;

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
}
