<?php

/**
 * @category    Tun2U
 * @package     Tun2U_VivaPayments
 * @author      Tun2U Team <dev@tun2u.com>
 * @copyright   Copyright (c) 2024 Tun2U (https://www.tun2u.com)
 * @license     https://opensource.org/licenses/gpl-3.0.html  GNU General Public License (GPL 3.0)
 */

namespace Tun2U\VivaPayments\Stdlib\Cookie;

use Magento\Framework\Exception\InputException;
use Magento\Framework\Phrase;
use Magento\Framework\Stdlib\Cookie\CookieMetadata;
use Magento\Framework\Stdlib\Cookie\CookieReaderInterface;
use Magento\Framework\Stdlib\Cookie\CookieScopeInterface;
use Magento\Framework\Stdlib\Cookie\CookieSizeLimitReachedException;
use Magento\Framework\Stdlib\Cookie\FailureToSendException;
use Magento\Framework\Stdlib\Cookie\PhpCookieManager;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Stdlib\Cookie\PublicCookieMetadata;
use Magento\Framework\Stdlib\Cookie\SensitiveCookieMetadata;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Framework\HTTP\Header as HttpHeader;
use Psr\Log\LoggerInterface;
use Tun2U\VivaPayments\Validator\SameSite;

/**
 * CookieManager helps manage the setting, retrieving and deleting of cookies.
 *
 * To aid in security, the cookie manager will make it possible for the application to indicate if the cookie contains
 * sensitive data so that extra protection can be added to the contents of the cookie as well as how the browser
 * stores the cookie.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CookieManager implements CookieManagerInterface
{
    /**#@+
     * Constants for Cookie manager.
     * RFC 2109 - Page 15
     * http://www.ietf.org/rfc/rfc6265.txt
     */
    const MAX_NUM_COOKIES = 50;
    const MAX_COOKIE_SIZE = 4096;
    const EXPIRE_NOW_TIME = 1;
    const EXPIRE_AT_END_OF_SESSION_TIME = 0;
    /**#@-*/

    const KEY_EXPIRES = 'expires';
    const KEY_PATH = 'path';
    const KEY_DOMAIN = 'domain';
    const KEY_SECURE = 'secure';
    const KEY_HTTP_ONLY = 'httponly';
    const KEY_SAME_SITE = 'samesite';

    /**#@+
     * Constant for metadata array key
     */
    const KEY_EXPIRE_TIME = 'expiry';
    /**#@-*/

    /**#@-*/
    private $scope;

    /**
     * @var CookieReaderInterface
     */
    private $reader;

    /**
     * Logger for warning details.
     *
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Object that provides access to HTTP headers.
     *
     * @var HttpHeader
     */
    private $httpHeader;

    /**
     * @var SameSite
     */
    private $validator;

    /**
     * @param CookieScopeInterface $scope
     * @param CookieReaderInterface $reader
     * @param SameSite $validator
     * @param LoggerInterface $logger
     * @param HttpHeader $httpHeader
     */
    public function __construct(
        CookieScopeInterface $scope,
        CookieReaderInterface $reader,
        SameSite $validator,
        LoggerInterface $logger = null,
        HttpHeader $httpHeader = null
    ) {
        $this->scope = $scope;
        $this->reader = $reader;
        $this->logger = $logger ?: ObjectManager::getInstance()->get(LoggerInterface::class);
        $this->httpHeader = $httpHeader ?: ObjectManager::getInstance()->get(HttpHeader::class);
        $this->validator = $validator;
    }

    /**
     * Set a value in a private cookie with the given $name $value pairing.
     *
     * Sensitive cookies cannot be accessed by JS. HttpOnly will always be set to true for these cookies.
     *
     * @param string $name
     * @param string $value
     * @param SensitiveCookieMetadata $metadata
     * @return void
     * @throws FailureToSendException Cookie couldn't be sent to the browser.  If this exception isn't thrown,
     * there is still no guarantee that the browser received and accepted the cookie.
     * @throws CookieSizeLimitReachedException Thrown when the cookie is too big to store any additional data.
     * @throws InputException If the cookie name is empty or contains invalid characters.
     */
    public function setSensitiveCookie($name, $value, SensitiveCookieMetadata $metadata = null)
    {
        $metadataArray = $this->scope->getSensitiveCookieMetadata($metadata)->__toArray();
        $this->setCookie($name, $value, $metadataArray);
    }

    /**
     * Set a value in a public cookie with the given $name $value pairing.
     *
     * Public cookies can be accessed by JS. HttpOnly will be set to false by default for these cookies,
     * but can be changed to true.
     *
     * @param string $name
     * @param string $value
     * @param PublicCookieMetadata $metadata
     * @return void
     * @throws FailureToSendException If cookie couldn't be sent to the browser.
     * @throws CookieSizeLimitReachedException Thrown when the cookie is too big to store any additional data.
     * @throws InputException If the cookie name is empty or contains invalid characters.
     */
    public function setPublicCookie($name, $value, PublicCookieMetadata $metadata = null)
    {
        $metadataArray = $this->scope->getPublicCookieMetadata($metadata)->__toArray();
        $this->setCookie($name, $value, $metadataArray);
    }

    /**
     * Set a value in a cookie with the given $name $value pairing.
     *
     * @param string $name
     * @param string $value
     * @param array $metadataArray
     * @return void
     * @throws FailureToSendException If cookie couldn't be sent to the browser.
     * @throws CookieSizeLimitReachedException Thrown when the cookie is too big to store any additional data.
     * @throws InputException If the cookie name is empty or contains invalid characters.
     */
    protected function setCookie($name, $value, array $metadataArray)
    {
        $expire = $this->computeExpirationTime($metadataArray);

        $this->checkAbilityToSendCookie($name, $value);
        $userAgent = $this->httpHeader->getHttpUserAgent();
        $sameSite = $this->validator->shouldSendSameSiteNone($userAgent);

        $version = PHP_VERSION_ID;
        if ($version >= 70300) {
            $options = [
                self::KEY_EXPIRES => $expire,
                self::KEY_PATH => $this->extractValue(CookieMetadata::KEY_PATH, $metadataArray, ''),
                self::KEY_DOMAIN => $this->extractValue(CookieMetadata::KEY_DOMAIN, $metadataArray, ''),
                self::KEY_SECURE => $this->extractValue(CookieMetadata::KEY_SECURE, $metadataArray, true),
                self::KEY_HTTP_ONLY => $this->extractValue(CookieMetadata::KEY_HTTP_ONLY, $metadataArray, false)
            ];

            if ($sameSite) {
                $options = array_merge($options, [self::KEY_SAME_SITE => 'None']);
            }

            $phpSetcookieSuccess = setcookie(
                $name,
                $value,
                $options
            );
        } else {
            $path = $this->extractValue(CookieMetadata::KEY_PATH, $metadataArray, '');
            if ($sameSite) {
                $path .= '; SameSite=None';
            }

            $phpSetcookieSuccess = setcookie(
                $name,
                $value,
                $expire,
                $path,
                $this->extractValue(CookieMetadata::KEY_DOMAIN, $metadataArray, ''),
                $this->extractValue(CookieMetadata::KEY_SECURE, $metadataArray, true),
                $this->extractValue(CookieMetadata::KEY_HTTP_ONLY, $metadataArray, false)
            );
        }

        if (!$phpSetcookieSuccess) {
            $params['name'] = $name;
            if ($value == '') {
                throw new FailureToSendException(
                    new Phrase('The cookie with "%name" cookieName couldn\'t be deleted.', $params)
                );
            } else {
                throw new FailureToSendException(
                    new Phrase('The cookie with "%name" cookieName couldn\'t be sent. Please try again later.', $params)
                );
            }
        }
    }

    /**
     * Retrieve the size of a cookie.
     * The size of a cookie is determined by the length of 'name=value' portion of the cookie.
     *
     * @param string $name
     * @param string $value
     * @return int
     */
    private function sizeOfCookie($name, $value)
    {
        // The constant '1' is the length of the equal sign in 'name=value'.
        return strlen($name) + 1 + strlen($value);
    }

    /**
     * Determines whether or not it is possible to send the cookie, based on the number of cookies that already
     * exist and the size of the cookie.
     *
     * @param string $name
     * @param string|null $value
     * @return void if it is possible to send the cookie
     * @throws CookieSizeLimitReachedException Thrown when the cookie is too big to store any additional data.
     * @throws InputException If the cookie name is empty or contains invalid characters.
     */
    private function checkAbilityToSendCookie($name, $value)
    {
        if ($name == '' || preg_match("/[=,; \t\r\n\013\014]/", $name)) {
            throw new InputException(
                new Phrase(
                    'Cookie name cannot be empty and cannot contain these characters: =,; \\t\\r\\n\\013\\014'
                )
            );
        }

        $numCookies = count($_COOKIE);

        if (!isset($_COOKIE[$name])) {
            $numCookies++;
        }

        $sizeOfCookie = $this->sizeOfCookie($name, $value);

        if ($numCookies > static::MAX_NUM_COOKIES) {
            $this->logger->warning(
                new Phrase('Unable to send the cookie. Maximum number of cookies would be exceeded.'),
                array_merge($_COOKIE, ['user-agent' => $this->httpHeader->getHttpUserAgent()])
            );
        }

        if ($sizeOfCookie > static::MAX_COOKIE_SIZE) {
            throw new CookieSizeLimitReachedException(
                new Phrase(
                    'Unable to send the cookie. Size of \'%name\' is %size bytes.',
                    [
                        'name' => $name,
                        'size' => $sizeOfCookie,
                    ]
                )
            );
        }
    }

    /**
     * Determines the expiration time of a cookie.
     *
     * @param array $metadataArray
     * @return int in seconds since the Unix epoch.
     */
    private function computeExpirationTime(array $metadataArray)
    {
        if (
            isset($metadataArray['expiry'])
            && $metadataArray['expiry'] < time()
        ) {
            $expireTime = $metadataArray['expiry'];
        } else {
            if (isset($metadataArray[CookieMetadata::KEY_DURATION])) {
                $expireTime = $metadataArray[CookieMetadata::KEY_DURATION] + time();
            } else {
                $expireTime = 0;
            }
        }

        return $expireTime;
    }

    /**
     * Determines the value to be used as a $parameter.
     * If $metadataArray[$parameter] is not set, returns the $defaultValue.
     *
     * @param string $parameter
     * @param array $metadataArray
     * @param string|boolean|int|null $defaultValue
     * @return string|boolean|int|null
     */
    private function extractValue($parameter, array $metadataArray, $defaultValue)
    {
        if (array_key_exists($parameter, $metadataArray)) {
            return $metadataArray[$parameter];
        } else {
            return $defaultValue;
        }
    }

    /**
     * Retrieve a value from a cookie.
     *
     * @param string $name
     * @param string|null $default The default value to return if no value could be found for the given $name.
     * @return string|null
     */
    public function getCookie($name, $default = null)
    {
        return $this->reader->getCookie($name, $default);
    }

    /**
     * Deletes a cookie with the given name.
     *
     * @param string $name
     * @param CookieMetadata $metadata
     * @return void
     * @throws FailureToSendException If cookie couldn't be sent to the browser.
     *     If this exception isn't thrown, there is still no guarantee that the browser
     *     received and accepted the request to delete this cookie.
     * @throws InputException If the cookie name is empty or contains invalid characters.
     */
    public function deleteCookie($name, CookieMetadata $metadata = null)
    {
        $metadataArray = $this->scope->getCookieMetadata($metadata)->__toArray();

        // explicitly set an expiration time in the metadataArray.
        $metadataArray['expiry'] = 1;

        $this->checkAbilityToSendCookie($name, '');

        // cookie value set to empty string to delete from the remote client
        $this->setCookie($name, '', $metadataArray);

        // Remove the cookie
        unset($_COOKIE[$name]);
    }
}
