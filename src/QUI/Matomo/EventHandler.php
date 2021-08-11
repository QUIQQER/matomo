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
class EventHandler
{
    /**
     * @param QUI\Template $Template
     * @param QUI\Projects\Site $Site
     */
    public static function onTemplateSiteFetch($Template, $Site)
    {
        $Project = $Site->getProject();

        $matomoUrl    = $Project->getConfig('matomo.settings.url');
        $matomoSiteId = Matomo::getSiteId($Project);

        $langSettingsJSON = $Project->getConfig('matomo.settings.langdata');

        if (!empty($langSettingsJSON)) {
            $langSettings = json_decode($langSettingsJSON, true);
            $language     = $Project->getLang();

            if (isset($langSettings[$language])) {
                if (isset($langSettings[$language]['url'])
                    && !empty($langSettings[$language]['url'])
                    && empty($matomoUrl)
                ) {
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
            'matomoUrl'        => $matomoUrl,
            'matomoSideId'     => $matomoSiteId,
            'eCommerce'       => QUI::getPackageManager()->isInstalled('quiqqer/order') ? 1 : 0,
            'cookieCategory'  => CookieUtils::getCookieCategorySetting()
        ]);

        $Template->extendFooter(
            $Engine->fetch(dirname(__FILE__).'/matomo.html')
        );
    }

    /**
     * Listens to project config save
     *
     * @param $project
     * @param array $config
     * @param array $params
     */
    public static function onProjectConfigSave($project, array $config, array $params)
    {
        try {
            $Project = QUI::getProject($project);
        } catch (QUI\Exception $Exception) {
            return;
        }

        if (isset($params['matomo.siteIds'])) {
            $siteIds = json_decode($params['matomo.siteIds'], true);
            Matomo::setSiteIds($siteIds, $Project);
        }

        // region Remove language specific URLs if general URL is set
        if (!isset($params['matomo.settings.url'])) {
            return;
        }

        try {
            $ProjectsConfig = QUI\Projects\Manager::getConfig();
        } catch (QUI\Exception $Exception) {
            return;
        }

        $projectName = $Project->getName();
        $settingKey  = 'matomo.settings.langdata';

        // Get the language data
        $languageDataJSON = $ProjectsConfig->getValue($projectName, $settingKey);
        if (empty($languageDataJSON)) {
            return;
        }

        $languageData = json_decode($languageDataJSON, true);
        if (empty($languageData)) {
            return;
        }

        // Remove all URLs
        foreach ($languageData as $language => $data) {
            unset($languageData[$language]['url']);
        }

        // Set the new config value
        $ProjectsConfig->setValue($projectName, $settingKey, json_encode($languageData));

        try {
            $ProjectsConfig->save();
        } catch (QUI\Exception $Exception) {
            return;
        }
        // endregion
    }
}
