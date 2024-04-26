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
            return QUI::getRewrite()->getProject()->getConfig('matomo.settings.cookiecategory');
        } catch (Exception $Exception) {
            Log::writeException($Exception);
        }

        return 'statistics';
    }
}
