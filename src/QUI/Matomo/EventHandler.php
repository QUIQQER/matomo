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
     * @param string $project
     * @param array<string, mixed> $config
     * @param array<string, mixed> $params
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
        if (!is_string($languageDataJSON) || $languageDataJSON === '') {
            return;
        }

        $languageData = json_decode($languageDataJSON, true);
        if (!is_array($languageData) || $languageData === []) {
            return;
        }

        // Remove all URLs
        foreach ($languageData as $language => $data) {
            unset($languageData[$language]['url']);
        }

        $encodedLanguageData = json_encode($languageData);

        if ($encodedLanguageData === false) {
            return;
        }

        // Set the new config value
        $ProjectsConfig->setValue($projectName, $settingKey, $encodedLanguageData);
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
