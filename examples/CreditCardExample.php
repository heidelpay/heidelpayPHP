<?php
/**
 * This file provides an example implementation of the credit card payment method.
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
 * @copyright Copyright © 2016-present heidelpay GmbH. All rights reserved.
 *
 * @link  http://dev.heidelpay.com/
 *
 * @author  Simon Gabriel <development@heidelpay.com>
 *
 * @package  heidelpay/mgw_sdk/examples
 */
 
 ?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width">
    <title>
        Heidelpay UI Examples
    </title>
    <script src="https://code.jquery.com/jquery-3.1.1.min.js" integrity="sha256-hVVnYaiADRTO2PzUGmuLJr8BLUSjGIZsDYGmIJLv2b8=" crossorigin="anonymous"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/semantic-ui/2.3.1/semantic.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/semantic-ui/2.3.1/semantic.min.css" />

    <link rel="stylesheet" href="https://static.heidelpay.com/v1/heidelpay.css" />
    <script type="text/javascript" src="https://static.heidelpay.com/v1/heidelpay.js"></script>
    <style>
        html, body {
            margin: 0;
            padding: 0;
            width: 100%;
            min-width: initial;
        }
    </style>
</head>

<body style="width: 100%; height: 330px">

    <style>
        .heidelpaySandboxNotify {
            display: none !important;
        }
    </style>
    <div class="ui segment">
        <div id="dimmer-holder" class="ui active dimmer" style="display: none;">
            <div class="ui loader"></div>
        </div>
        <form id="payment-form" class="heidelpayUI form" novalidate>
            <div class="field">
                <label for="card-number">Card Number</label>
                <div class="heidelpayUI left icon input">
                    <div id="heidelpay-i-card-number" class="heidelpayInput"></div>
                    <i id="card-icon" class="icon h-iconimg-card-default"></i>
                </div>
            </div>
            <div class="two fields unstackable">
                <div class="field">
                    <label for="card-expiry-date">Expiry Date</label>
                    <div class="heidelpayUI left icon input">
                        <div id="heidelpay-i-card-expiry" class="heidelpayInput"></div>
                        <i class="icon h-iconimg-card-expiry"></i>
                    </div>
                </div>
                <div class="field">
                    <label id="label-card-ccv" for="card-ccv">CVC</label>
                    <div class="heidelpayUI left icon input">
                        <div id="heidelpay-i-card-cvc" class="heidelpayInput"></div>
                        <i class="icon h-iconimg-card-cvc"></i>
                    </div>
                </div>
            </div>

            <p id="error-holder" style="color: #9f3a38"></p>
            <div class="field">
                <button class="ui primary button" type="submit">Pay</button>
            </div>
        </form>
    </div>

    <script>
        var heidelpayObj = new heidelpay('s-pub-2a10fcyD4qVbJGdp76QSoAXoOrO3WrLz');

        // Credit Card example
        var Card = heidelpayObj.Card();
        Card.create('number', {
            containerId: 'heidelpay-i-card-number',
        });
        Card.create('expiry', {
            containerId: 'heidelpay-i-card-expiry',
        });
        Card.create('cvc', {
            containerId: 'heidelpay-i-card-cvc',
        });

        Card.addEventListener('change', function (e) {
            if (e.cardType) {
                let $card = $('#card-icon');
                $card.removeClass();
                $card.addClass(`icon h-iconimg-${e.cardType.imgName}`)
            }

            // error handling
            var $inputElement = $(`#heidelpay-i-card-${e.type}`);
            var $icon = $inputElement.next();
            var $errorHolder = $('#error-holder');
            if (e.success === false && e.error) {
                $inputElement.closest('.heidelpayUI.input').addClass('error');
                $inputElement.closest('.field').addClass('error');
                $icon.addClass('h-iconimg-error');
                $errorHolder.html(e.error)
            } else if (e.success) {
                $inputElement.parent('.heidelpayUI.input').removeClass('error');
                $inputElement.closest('.field').removeClass('error');
                $icon.removeClass('h-iconimg-error');
                $errorHolder.html('')
            }
        });

        // // Handle card form submission.
        var form = document.getElementById('payment-form');
        form.addEventListener('submit', function (event) {
            event.preventDefault();

            document.getElementById('dimmer-holder').style.display = 'block';
            Card.createResource()
                .then(function (data) {
                    document.getElementById('dimmer-holder').innerHTML
                        = `<div style="color: #eee;top: 43%;position: relative;" class="ui">Resource Id: ${data.id}</div>`
                })
                .catch(function (error) {
                    document.getElementById('dimmer-holder').style.display = 'none';
                    document.getElementById('error-holder').innerHTML = error.customerMessage || error.message || 'Error'
                })
        });
    </script>
</body>

</html>