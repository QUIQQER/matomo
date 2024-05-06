<?php

/**
 * @author PCSG (Jan Wennrich)
 */

namespace QUI\Matomo\Cookies;

use QUI;
use QUI\GDPR\CookieInterface;
use QUI\Matomo\CookieUtils;

use function sprintf;

/**
 * Class QuiqqerSessionCookie
 *
 * @package QUI\GDPR\Cookies
 */
class HsrCookie implements CookieInterface
{
    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return '_pk_hsr.*';
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
        return QUI::getLocale()->get('quiqqer/matomo', 'cookie.hsr.purpose');
    }

    /**
     * @inheritDoc
     */
    public function getLifetime(): string
    {
        return sprintf(
            '%d %s',
            30,
            QUI::getLocale()->get('quiqqer/quiqqer', 'minutes')
        );
    }

    /**
     * @inheritDoc
     */
    public function getCategory(): string
    {
        return CookieUtils::getCookieCategorySetting();
    }
}
