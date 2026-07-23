<?php

namespace QUI\Matomo;

use QUI;

/**
 * Provides access to the Matomo package configuration.
 */
class Settings
{
    /**
     * Return the Matomo package configuration.
     *
     * @throws QUI\Exception
     */
    public static function getConfig(): QUI\Config
    {
        $Config = QUI::getPackage('quiqqer/matomo')->getConfig();

        if ($Config === null) {
            throw new QUI\Exception('Matomo configuration is not available.');
        }

        return $Config;
    }
}
