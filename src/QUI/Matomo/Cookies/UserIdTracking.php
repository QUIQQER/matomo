<?php

/**
 * @author PCSG (Jan Wennrich)
 */

namespace QUI\Matomo\Cookies;

use QUI;
use QUI\GDPR\CookieInterface;
use QUI\Matomo\CookieUtils;

/**
 * Class UserIdTracking
 *
 * @package QUI\GDPR\Cookies
 */
class UserIdTracking implements CookieInterface
{
    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return '';
    }

    /**
     * @inheritDoc
     */
    public function getOrigin(): string
    {
        return QUI::getRequest()->getHost();
    }

    /**
     * @inheritDoc
     */
    public function getPurpose(): string
    {
        return QUI::getLocale()->get('quiqqer/matomo', 'cookie.userIdTracking.purpose');
    }

    /**
     * @inheritDoc
     */
    public function getLifetime(): string
    {
        return '';
    }

    /**
     * @inheritDoc
     */
    public function getCategory(): string
    {
        return CookieUtils::getUserIdTrackingCookieCategory();
    }
}
