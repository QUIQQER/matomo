<?php

/**
 * @author PCSG (Jan Wennrich)
 */

namespace QUI\Matomo;

use QUI;
use QUI\Exception;
use QUI\System\Log;

/**
 * Class CookieUtils
 *
 * @package QUI\Matomo
 */
class CookieUtils
{
    /**
     * Returns the category that should be used for the Matomo cookies.
     *
     * @return string
     */
    public static function getCookieCategorySetting(): string
    {
        try {
            $category = (string)QUI::getRewrite()
                ->getProject()
                ?->getConfig('matomo.settings.cookiecategory');

            if ($category !== '') {
                return $category;
            }
        } catch (Exception $Exception) {
            Log::writeException($Exception);
        }

        return 'statistics';
    }

    /**
     * Returns the consent category for user ID tracking.
     */
    public static function getUserIdTrackingCookieCategory(): string
    {
        try {
            $category = (string)QUI::getRewrite()
                ->getProject()
                ?->getConfig('matomo.settings.userIdTracking.cookieCategory');

            if ($category !== '') {
                return $category;
            }
        } catch (Exception $Exception) {
            Log::writeException($Exception);
        }

        return 'marketing';
    }

    /**
     * Returns the consent category for user email tracking.
     */
    public static function getUserEmailTrackingCookieCategory(): string
    {
        try {
            $category = (string)QUI::getRewrite()
                ->getProject()
                ?->getConfig('matomo.settings.userEmailTracking.cookieCategory');

            if ($category !== '') {
                return $category;
            }
        } catch (Exception $Exception) {
            Log::writeException($Exception);
        }

        return 'marketing';
    }
}
