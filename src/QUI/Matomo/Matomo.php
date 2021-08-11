<?php

namespace QUI\Matomo;

use QUI;
use QUI\Projects\Project;

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

    /**
     * Return the Matomo client
     *
     * @param Project $Project
     * @return \PiwikTracker
     */
    public static function getMatomoClient(Project $Project)
    {
        $matomoUrl    = $Project->getConfig('matomo.settings.url');
        $matomoSideId = $Project->getConfig('matomo.settings.id');

        if (\mb_strpos($matomoUrl, '://') === false) {
            $matomoUrl = 'https://'.$matomoUrl;
        }

        $Matomo = new \MatomoTracker($matomoSideId, $matomoUrl);

        if ($Project->getConfig('matomo.settings.token')) {
            $Matomo->setTokenAuth($Project->getConfig('matomo.settings.token'));
        }

        return $Matomo;
    }


    /**
     * Returns the site ID for a given project and language
     *
     * @param Project $Project
     * @param $language
     * @return string
     */
    public static function getSiteId(Project $Project, $language = null)
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
        if (empty($siteId) || $siteId == '['.$group.'] '.self::LOCALE_KEY_SITE_IDS) {
            return (int)$Project->getConfig('matomo.settings.id');
        }

        return $siteId;
    }


    /**
     * Stores the given site IDs in the system (as locale variables).
     *
     * @param array $siteIds - e.g.: ['de' => 40, 'en' => 41, 'fr' => 42]
     * @param Project $Project
     */
    public static function setSiteIds($siteIds, Project $Project)
    {
        $localeKey   = self::LOCALE_KEY_SITE_IDS;
        $localeGroup = self::getLocaleGroup($Project);

        try {
            QUI\Translator::add(
                $localeGroup,
                $localeKey,
                $localeGroup
            );
        } catch (QUI\Exception $Exception) {
            // Throws error if lang var already exists
        }

        try {
            QUI\Translator::edit(
                $localeGroup,
                $localeKey,
                $localeGroup,
                $siteIds
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
    private static function getLocaleGroup(Project $Project)
    {
        return 'project/'.$Project->getName();
    }
}
