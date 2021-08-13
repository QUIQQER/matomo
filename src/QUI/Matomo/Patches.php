<?php

namespace QUI\Matomo;

use QUI\Exception;
use QUI\Projects\Project;
use QUI\System\Log;
use QUI\Translator;

/**
 * Class Patches
 *
 * @package QUI\Matomo
 *
 * @author PCSG (Jan Wennrich)
 */
class Patches
{
    const SITE_IDS_TO_LOCALE_VARIABLES = 'movedSiteIdsToLocaleVariables';

    /**
     * Moves the previously used settings values to locale variables.
     * We need to do this in order to use the InputMultiLang control.
     */
    public static function moveSiteIdsToLocaleVariables()
    {
        try {
            $Package = \QUI::getPackage('quiqqer/matomo');
            $Config  = $Package->getConfig();

            $isPatchExecutedAlready = $Config->get('patches', self::SITE_IDS_TO_LOCALE_VARIABLES) == 1;

            if (!$isPatchExecutedAlready) {
                $projectList = \QUI\Projects\Manager::getProjects(true);
                foreach ($projectList as $Project) {
                    /** @var Project $Project */
                    $langdataJSON = $Project->getConfig('matomo.settings.langdata');

                    if (empty($langdataJSON)) {
                        continue;
                    }

                    $languageData = json_decode($langdataJSON, true);

                    if (empty($languageData)) {
                        continue;
                    }

                    $localeVariableData     = [
                        'package'  => 'project/' . $Project->getName(),
                        'datatype' => 'php,js',
                        'html'     => 0
                    ];
                    $wasLocaleVariableFound = false;

                    foreach ($languageData as $key => $data) {
                        if (isset($data['id']) && !empty($data['id'])) {
                            $localeVariableData[$key] = $data['id'];
                            $wasLocaleVariableFound   = true;
                        }
                    }

                    if ($wasLocaleVariableFound) {
                        $group = 'project/' . $Project->getName();

                        Translator::addUserVar(
                            $group,
                            Matomo::LOCALE_KEY_SITE_IDS,
                            $localeVariableData
                        );
                        Translator::publish($group);
                    }
                }

                $Config->set('patches', self::SITE_IDS_TO_LOCALE_VARIABLES, 1);
                $Config->save();
            }
        } catch (Exception $Exception) {
            Log::addError($Exception->getMessage(), $Exception->getContext());
        }
    }

    /**
     * Migrate settings from previous quiqqer/piwik package into this package.
     */
    public static function migratePiwikSettings()
    {
        $ProjectManager = \QUI::getProjectManager();
        $projects       = $ProjectManager::getProjects(true);

        // Configs only differ in the prefix ("piwik" or "matomo")
        $configKeySuffixes = [
            '.settings.url',
            '.settings.id',
            '.settings.token',
            '.settings.langdata',
            '.settings.cookiecategory',
        ];

        // Migrate settings for every project
        foreach ($projects as $Project) {
            $migratedSettings = [];

            // Check for every config value if it has to be migrated
            foreach ($configKeySuffixes as $configKeySuffix) {
                /** @var Project $Project */
                $piwikValue = $Project->getConfig('piwik' . $configKeySuffix);
                $matomoValue = $Project->getConfig('matomo' . $configKeySuffix);

                $a = 1+1;

                // If Piwik value is set and no Matomo value exists...
                if ($piwikValue && !$matomoValue) {
                    // Add Piwik value to the settings that should be migrated
                    $migratedSettings['matomo' . $configKeySuffix] = $piwikValue;
                }
            }

            // TODO: Migrate locale variables for language specific site ids (see EventHandler:onProjectConfigSave)

            if (!empty($migratedSettings)) {
                // Store the migrated settings for Matomo
                //$ProjectManager::setConfigForProject($Project->getName(), $migratedSettings);
            }
        }
    }
}
