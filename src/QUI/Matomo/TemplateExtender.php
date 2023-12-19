<?php

namespace QUI\Matomo;

use QUI;

/**
 * Class EventHandler
 *
 * @package QUI\Matomo
 *
 * @author PCSG (Jan Wennrich)
 */
class TemplateExtender
{
    public static function extendHeader(QUI\Template $Template, QUI\Interfaces\Projects\Site $Site)
    {
        $Project = $Site->getProject();
        $trackToPaq = $Project->getConfig('matomo.settings.trackToPaq') ? 1 : 0;

        $Template->extendHeader('<script data-no-cache="1">window.MATOMO_TRACK_TO_PAQ = ' . $trackToPaq . ';</script>');
        $Template->extendHeader('<script src="' . URL_OPT_DIR . 'quiqqer/matomo/bin/dataLayer.js"></script>');
    }

    public static function extendFooter(QUI\Template $Template, QUI\Interfaces\Projects\Site $Site)
    {
        $Project = $Site->getProject();

        $matomoUrl = $Project->getConfig('matomo.settings.url');
        $matomoSiteId = Matomo::getSiteId($Project);

        $langSettingsJSON = $Project->getConfig('matomo.settings.langdata');

        if (!empty($langSettingsJSON)) {
            $langSettings = json_decode($langSettingsJSON, true);
            $language = $Project->getLang();

            if (isset($langSettings[$language])) {
                if (!empty($langSettings[$language]['url']) && empty($matomoUrl)) {
                    $matomoUrl = $langSettings[$language]['url'];
                }
            }
        }

        if (empty($matomoUrl) || empty($matomoSiteId)) {
            return;
        }

        try {
            $Engine = QUI::getTemplateManager()->getEngine();
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::addDebug($Exception->getMessage());

            return;
        }

        $Engine->assign([
            'matomoUrl' => $matomoUrl,
            'matomoSideId' => $matomoSiteId,
            'eCommerce' => QUI::getPackageManager()->isInstalled('quiqqer/order') ? 1 : 0,
            'cookieCategory' => CookieUtils::getCookieCategorySetting()
        ]);

        $Template->extendFooter(
            $Engine->fetch(dirname(__FILE__) . '/matomo.html')
        );
    }
}
