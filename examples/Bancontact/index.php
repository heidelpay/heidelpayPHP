<?php
/**
 * This file provides an example implementation of the Bancontact payment type.
 *
 * Copyright (C) 2020 heidelpay GmbH
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
 * @author  David Owusu <development@heidelpay.com>
 *
 * @package  heidelpayPHP\examples
 */

/** Require the constants of this example */
require_once __DIR__ . '/Constants.php';

/** @noinspection PhpIncludeInspection */
/** Require the composer autoloader file */
require_once __DIR__ . '/../../../../autoload.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>
        Heidelpay UI Examples
    </title>
    <script src="https://code.jquery.com/jquery-3.1.1.min.js"
            integrity="sha256-hVVnYaiADRTO2PzUGmuLJr8BLUSjGIZsDYGmIJLv2b8=" crossorigin="anonymous"></script>

    <link rel="stylesheet" href="https://static.heidelpay.com/v1/heidelpay.css" />
    <script type="text/javascript" src="https://static.heidelpay.com/v1/heidelpay.js"></script>
</head>

<body style="margin: 70px 70px 0;">

<p><a href="https://docs.heidelpay.com/docs/testdata" target="_blank">Click here to open our test data in new tab.</a></p>

<form id="payment-form" class="heidelpayUI form" novalidate>
    <div id="bancontact-holder" class="field">
        <!-- The Holder field UI Element will be inserted here -->
    </div>
    <div class="field" id="error-holder" style="color: #9f3a38"> </div>
    <button class="heidelpayUI primary button fluid" id="submit-button" type="submit">Pay</button>
</form>

<script>
    // Create a heidelpay instance with your public key
    let heidelpayInstance = new heidelpay('<?php echo HEIDELPAY_PHP_PAYMENT_API_PUBLIC_KEY; ?>');

    // Create an Bancontact instance
    let Bancontact = heidelpayInstance.Bancontact();

    Bancontact.create('holder', {
        containerId: 'bancontact-holder',
    });

    // Handle payment form submission
    let form = document.getElementById('payment-form');
    form.addEventListener('submit', function(event) {
        event.preventDefault();
        // Creating a Bancontact resource
        Bancontact.createResource()
            .then(function(result) {
                let hiddenInput = document.createElement('input');
                hiddenInput.setAttribute('type', 'hidden');
                hiddenInput.setAttribute('name', 'resourceId');
                hiddenInput.setAttribute('value', result.id);
                form.appendChild(hiddenInput);
                form.setAttribute('method', 'POST');
                form.setAttribute('action', '<?php echo CONTROLLER_URL; ?>');

                // Submitting the form
                form.submit();
            })
            .catch(function(error) {
                $('#error-holder').html(error.message)
            })
    });
</script>
</body>
</html>
