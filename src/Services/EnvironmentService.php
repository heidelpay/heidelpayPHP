<?php
/**
 * This service provides for functionalities concerning the mgw environment.
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
 * @package  heidelpayPHP\Services
 */
namespace heidelpayPHP\Services;

use function in_array;

class EnvironmentService
{
    const ENV_VAR_NAME_ENVIRONMENT = 'HEIDELPAY_MGW_ENV';
    const ENV_VAR_VALUE_STAGING_ENVIRONMENT = 'STG';
    const ENV_VAR_VALUE_DEVELOPMENT_ENVIRONMENT = 'DEV';
    const ENV_VAR_VALUE_PROD_ENVIRONMENT = 'PROD';

    /** @deprecated ENV_VAR_NAME_DISABLE_TEST_LOGGING since 1.2.7.3 replaced by ENV_VAR_NAME_VERBOSE_TEST_LOGGING */
    const ENV_VAR_NAME_DISABLE_TEST_LOGGING = 'HEIDELPAY_MGW_DISABLE_TEST_LOGGING';
    const ENV_VAR_NAME_VERBOSE_TEST_LOGGING = 'HEIDELPAY_MGW_VERBOSE_TEST_LOGGING';

    const ENV_VAR_TEST_PRIVATE_KEY = 'HEIDELPAY_MGW_TEST_PRIVATE_KEY';
    const ENV_VAR_TEST_PUBLIC_KEY = 'HEIDELPAY_MGW_TEST_PUBLIC_KEY';
    const DEFAULT_TEST_PRIVATE_KEY = 's-priv-2a102ZMq3gV4I3zJ888J7RR6u75oqK3n';
    const DEFAULT_TEST_PUBLIC_KEY  = 's-pub-2a10ifVINFAjpQJ9qW8jBe5OJPBx6Gxa';

    const ENV_VAR_NAME_TIMEOUT = 'HEIDELPAY_MGW_TIMEOUT';
    const DEFAULT_TIMEOUT = 60;

    const ENV_VAR_NAME_CURL_VERBOSE = 'HEIDELPAY_MGW_CURL_VERBOSE';

    /**
     * Returns the value of the given env var as bool.
     *
     * @param string $varName
     *
     * @return bool
     */
    protected static function getBoolEnvValue(string $varName): bool
    {
        /** @noinspection ProperNullCoalescingOperatorUsageInspection */
        $envVar = $_SERVER[$varName] ?? false;
        if (!is_bool($envVar)) {
            $envVar = in_array(strtolower($envVar), [true, 'true', '1'], true);
        }
        return $envVar;
    }

    /**
     * Returns the MGW environment set via environment variable or PROD es default.
     *
     * @return string
     */
    public function getMgwEnvironment(): string
    {
        return $_SERVER[self::ENV_VAR_NAME_ENVIRONMENT] ?? self::ENV_VAR_VALUE_PROD_ENVIRONMENT;
    }

    /**
     * Returns false if the logging in tests is deactivated by environment variable.
     *
     * @return bool
     */
    public static function isTestLoggingActive(): bool
    {
        if (isset($_SERVER[self::ENV_VAR_NAME_VERBOSE_TEST_LOGGING])) {
            $verboseLogging = self::getBoolEnvValue(self::ENV_VAR_NAME_VERBOSE_TEST_LOGGING);
        } else {
            $verboseLogging = !self::getBoolEnvValue(self::ENV_VAR_NAME_DISABLE_TEST_LOGGING);
        }
        return $verboseLogging;
    }

    /**
     * Returns the timeout set via environment variable or the default timeout.
     * ATTENTION: Setting this value to 0 will disable the limit.
     *
     * @return int
     */
    public static function getTimeout(): int
    {
        $timeout = $_SERVER[self::ENV_VAR_NAME_TIMEOUT] ?? '';
        return is_numeric($timeout) ? (int)$timeout : self::DEFAULT_TIMEOUT;
    }

    /**
     * Returns the curl verbose flag.
     *
     * @return bool
     */
    public static function getCurlVerbose(): bool
    {
        $curlVerbose = strtolower($_SERVER[self::ENV_VAR_NAME_CURL_VERBOSE] ?? 'false');
        return in_array($curlVerbose, ['true', '1'], true);
    }

    /**
     * Returns the private key string set via environment variable.
     * Returns the default key if the environment variable is not set.
     *
     * @param bool $non3ds
     *
     * @return string
     */
    public function getTestPrivateKey($non3ds = false): string
    {
        $variableName = self::ENV_VAR_TEST_PRIVATE_KEY . ($non3ds ? '_NON_3DS' : '');
        $key = $_SERVER[$variableName] ?? '';
        return empty($key) && !$non3ds ? self::DEFAULT_TEST_PRIVATE_KEY : $key;
    }

    /**
     * Returns the public key string set via environment variable.
     * Returns the default key if the environment variable is not set.
     *
     * @param bool $non3ds
     *
     * @return string
     */
    public function getTestPublicKey($non3ds = false): string
    {
        $variableName = self::ENV_VAR_TEST_PUBLIC_KEY . ($non3ds ? '_NON_3DS' : '');
        $key = $_SERVER[$variableName] ?? '';
        return empty($key) && !$non3ds ? self::DEFAULT_TEST_PUBLIC_KEY : $key;
    }
}
