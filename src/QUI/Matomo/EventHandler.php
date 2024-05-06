<?php

namespace QUI\Matomo;

use QUI;

use QUI\Exception;

use function is_array;

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
     * @param QUI\Interfaces\Projects\Site $Site
     */
    public static function onTemplateSiteFetch(QUI\Template $Template, QUI\Interfaces\Projects\Site $Site): void
    {
        TemplateExtender::extendHeader($Template, $Site);
        TemplateExtender::extendFooter($Template, $Site);
    }

    /**
     * Listens to project config save
     *
     * @param $project
     * @param array $config
     * @param array $params
     */
    public static function onProjectConfigSave($project, array $config, array $params): void
    {
        try {
            $Project = QUI::getProject($project);
        } catch (QUI\Exception) {
            return;
        }

        if (isset($params['matomo.siteIds'])) {
            $siteIds = json_decode($params['matomo.siteIds'], true);
            Matomo::setSiteIds($siteIds, $Project);
        }

        if (isset($params[Matomo::CONFIG_KEY_GENERAL_TAG_MANAGER_CODE])) {
            Matomo::setGeneralTagManagerCode($params[Matomo::CONFIG_KEY_GENERAL_TAG_MANAGER_CODE], $Project);
        }

        if (isset($params['matomo.settings.tagmanager.code.languages'])) {
            $tagManagerCodes = json_decode($params['matomo.settings.tagmanager.code.languages'], true);

            if (is_array($tagManagerCodes)) {
                Matomo::setTagManagerCodes($tagManagerCodes, $Project);
            }
        }

        // region Remove language specific URLs if general URL is set
        if (!isset($params['matomo.settings.url'])) {
            return;
        }

        try {
            $ProjectsConfig = QUI\Projects\Manager::getConfig();
        } catch (QUI\Exception) {
            return;
        }

        $projectName = $Project->getName();
        $settingKey = 'matomo.settings.langdata';

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
        } catch (QUI\Exception) {
            return;
        }
        // endregion
    }

    /**
     * Fired after the package has been installed.
     *
     * @param QUI\Package\Package $Package
     * @throws Exception
     */
    public static function onPackageInstallAfter(QUI\Package\Package $Package): void
    {
        if ($Package->getName() !== 'quiqqer/matomo') {
            return;
        }

        Patches::migratePiwikSettings();
    }
}
