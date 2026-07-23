<?php

/**
 * @author PCSG (Jan Wennrich)
 */

namespace QUI\Matomo\Cookies;

use QUI;
use QUI\GDPR\CookieInterface;
use QUI\Matomo\CookieUtils;

/**
 * Class QuiqqerSessionCookie
 *
 * @package QUI\GDPR\Cookies
 */
class IgnoreCookie implements CookieInterface
{
    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return 'matomo_ignore';
    }

    /**
     * @inheritDoc
     */
    public function getOrigin(): string
    {
        try {
            $origin = (string)QUI::getRewrite()
                ->getProject()
                ?->getConfig('matomo.settings.url');

            if ($origin !== '') {
                return $origin;
            }
        } catch (QUI\Exception) {
            // Use the request host below.
        }

        return QUI::getRequest()->getHost();
    }

    /**
     * @inheritDoc
     */
    public function getPurpose(): string
    {
        return QUI::getLocale()->get('quiqqer/matomo', 'cookie.ignore.purpose');
    }

    /**
     * @inheritDoc
     */
    public function getLifetime(): string
    {
        return QUI::getLocale()->get('quiqqer/matomo', 'cookie.ignore.lifetime');
    }

    /**
     * @inheritDoc
     */
    public function getCategory(): string
    {
        return CookieUtils::getCookieCategorySetting();
    }
}
