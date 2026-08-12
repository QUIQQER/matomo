<?php

namespace QUI\Matomo;

use MatomoTracker;
use QUI;
use QUI\Projects\Project;

use function mb_strpos;

/**
 * Matomo Helper
 *
 * @package QUI\Matomo
 *
 * @author PCSG (Jan Wennrich)
 */
class Matomo
{
    const LOCALE_KEY_SITE_IDS = 'matomo.siteID';
    const LOCALE_KEY_TAG_MANAGER_CODES = 'matomo.settings.tagmanager.code.languages.value';
    const CONFIG_KEY_GENERAL_TAG_MANAGER_CODE = 'matomo.settings.tagmanager.code.general';
    const CONFIG_KEY_TAG_MANAGER_ENABLED = 'matomo.settings.tagmanager.enabled';

    /**
     * Return the Matomo client
     *
     * @param Project $Project
     * @return MatomoTracker
     */
    public static function getMatomoClient(Project $Project): MatomoTracker
    {
        $matomoUrl = (string)$Project->getConfig('matomo.settings.url');
        $matomoSideId = (int)$Project->getConfig('matomo.settings.id');
        $token = (string)$Project->getConfig('matomo.settings.token');

        if (!$matomoUrl || !$matomoSideId) {
            throw new \QUI\Exception('Matomo host not configured');
        }

        if (\mb_strpos($matomoUrl, '://') === false) {
            $matomoUrl = 'https://' . $matomoUrl;
        }

        $Matomo = new MatomoTracker($matomoSideId, $matomoUrl);

        if ($token !== '') {
            $Matomo->setTokenAuth($token);
        }

        return $Matomo;
    }


    /**
     * Returns the site ID for a given project and language
     *
     * @param Project $Project
     * @param string|null $language
     * @return int|string
     */
    public static function getSiteId(Project $Project, null|string $language = null): int|string
    {
        $group = self::getLocaleGroup($Project);

        if (is_null($language)) {
            $language = $Project->getLang();
        }

        $siteId = QUI::getLocale()->getByLang(
            $language,
            $group,
            self::LOCALE_KEY_SITE_IDS
        );

        // No value set for this language, therefore return the general ID
        // TODO: replace with the code above, if the mentioned bug is fixed.
        if (
            $siteId === ''
            || $siteId === '[' . $group . '] ' . self::LOCALE_KEY_SITE_IDS
        ) {
            return (int)$Project->getConfig('matomo.settings.id');
        }

        return $siteId;
    }


    /**
     * Stores the given site IDs in the system (as locale variables).
     *
     * @param array<string, int|string> $siteIds e.g. ['de' => 40, 'en' => 41, 'fr' => 42]
     * @param Project $Project
     */
    public static function setSiteIds(array $siteIds, Project $Project): void
    {
        static::storeLocaleVariableBasedData(static::LOCALE_KEY_SITE_IDS, $siteIds, $Project);
    }


    /**
     * Stores the given Tag Manager Codes in the system (as locale variables).
     *
     * @param array<string, string> $tagManagerCodes
     * @param Project $Project
     */
    public static function setTagManagerCodes(array $tagManagerCodes, Project $Project): void
    {
        static::storeLocaleVariableBasedData(static::LOCALE_KEY_TAG_MANAGER_CODES, $tagManagerCodes, $Project);
    }


    public static function setGeneralTagManagerCode(string $code, Project $Project): bool
    {
        $encodedCode = json_encode(htmlentities($code));

        if ($encodedCode === false) {
            return false;
        }

        try {
            $ProjectsConfig = QUI\Projects\Manager::getConfig();
            $ProjectsConfig->setValue(
                $Project->getName(),
                static::CONFIG_KEY_GENERAL_TAG_MANAGER_CODE,
                $encodedCode
            );
            $ProjectsConfig->save();
        } catch (QUI\Exception) {
            return false;
        }

        return true;
    }


    public static function getGeneralTagManagerCode(Project $Project): string
    {
        $value = $Project->getConfig(static::CONFIG_KEY_GENERAL_TAG_MANAGER_CODE);
        return html_entity_decode(json_decode($value, true));
    }

    /**
     * Returns the Tag Manager Code for a given project and language
     *
     * @param Project $Project
     * @param string|null $language
     * @return string
     */
    public static function getTagManagerCode(Project $Project, ?string $language = null): string
    {
        $group = self::getLocaleGroup($Project);

        if (is_null($language)) {
            $language = $Project->getLang();
        }

        // If locale variable does not exist, return general tag manager code
        if (
            !QUI::getLocale()->exists(
                $group,
                self::LOCALE_KEY_TAG_MANAGER_CODES
            )
        ) {
            return static::getGeneralTagManagerCode($Project);
        }

        // Get tag manager code from locale variable
        $tagManagerCode = QUI::getLocale()->getByLang(
            $language,
            $group,
            self::LOCALE_KEY_TAG_MANAGER_CODES
        );

        // Value for this language is empty, return the general TagManager code
        if ($tagManagerCode === '') {
            return static::getGeneralTagManagerCode($Project);
        }

        return $tagManagerCode;
    }

    public static function isTagManagerEnabled(Project $Project): bool
    {
        return boolval($Project->getConfig(static::CONFIG_KEY_TAG_MANAGER_ENABLED));
    }

    /**
     * @param array<string, int|string> $values
     */
    protected static function storeLocaleVariableBasedData(string $localeKey, array $values, Project $Project): void
    {
        $localeGroup = self::getLocaleGroup($Project);

        try {
            QUI\Translator::add(
                $localeGroup,
                $localeKey,
                $localeGroup
            );
        } catch (QUI\Exception) {
            // Throws error if lang var already exists
        }

        try {
            QUI\Translator::edit(
                $localeGroup,
                $localeKey,
                $localeGroup,
                $values
            );
            QUI\Translator::publish($localeGroup);
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::writeException($Exception);
        }
    }

    /**
     * Returns the name of the locale group used to store the site IDs.
     *
     * @param Project $Project
     * @return string
     */
    private static function getLocaleGroup(Project $Project): string
    {
        return 'project/' . $Project->getName();
    }
}
