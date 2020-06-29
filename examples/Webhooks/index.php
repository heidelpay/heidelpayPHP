<?php
/**
 * This is the index controller for the Webhook tests.
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
 * @package  heidelpayPHP\examples
 */

/** Require the constants of this example */
require_once __DIR__ . '/Constants.php';

/** @noinspection PhpIncludeInspection */
/** Require the composer autoloader file */
require_once __DIR__ . '/../../../../autoload.php';

use heidelpayPHP\Constants\WebhookEvents;
use heidelpayPHP\examples\ExampleDebugHandler;
use heidelpayPHP\Exceptions\HeidelpayApiException;
use heidelpayPHP\Heidelpay;
use heidelpayPHP\Resources\Webhook;

function printMessage($type, $title, $text)
{
    echo '<div class="ui ' . $type . ' message">'.
            '<div class="header">' . $title . '</div>'.
            '<p>' . nl2br($text) . '</p>'.
         '</div>';
}

function printError($text)
{
    printMessage('error', 'Error', $text);
}

function printSuccess($title, $text)
{
    printMessage('success', $title, $text);
}

function printInfo($title, $text)
{
    printMessage('info', $title, $text);
}

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

    <script src="https://cdnjs.cloudflare.com/ajax/libs/semantic-ui/2.3.1/semantic.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/semantic-ui/2.3.1/semantic.min.css" />
</head>

<body style="margin: 70px 70px 0;">
<div class="ui container segment">
    <h2 class="ui header">
        <i class="envelope outline icon"></i>
        <span class="content">
            Webhook registration
        </span>
    </h2>

    <?php
        try {
            $heidelpay = new Heidelpay(HEIDELPAY_PHP_PAYMENT_API_PRIVATE_KEY);
            $heidelpay->setDebugMode(true)->setDebugHandler(new ExampleDebugHandler());

            $webhooks = $heidelpay->registerMultipleWebhooks(CONTROLLER_URL, [WebhookEvents::ALL]);

            foreach ($webhooks as $webhook) {
                /** @var Webhook $webhook */
                printSuccess(
                    'Event registered',
                    '<strong>Event:</strong> ' . $webhook->getEvent() . '</br>' .
                    '<strong>URL:</strong> ' . $webhook->getUrl() . '</br>'
                );
            }

            printInfo('You are ready to trigger events', 'Now Perform payments <a href="..">>> HERE <<</a> to trigger events!');

        } catch (HeidelpayApiException $e) {
            printError($e->getMessage());
            $heidelpay->debugLog('Error: ' . $e->getMessage());
        } catch (RuntimeException $e) {
            printError($e->getMessage());
            $heidelpay->debugLog('Error: ' . $e->getMessage());
        }
    ?>
</div>
</body>
</html>
