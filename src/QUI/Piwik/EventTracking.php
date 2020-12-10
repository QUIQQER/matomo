<?php

namespace QUI\Piwik;

use QUI;

/**
 * Class EventTracking
 */
class EventTracking
{
    /**
     * @param QUI\Users\User $User
     * @param QUI\FrontendUsers\RegistrarInterface $Registrar
     * @param string $registrationStatus
     */
    public static function onQuiqqerFrontendUsersUserRegister(
        \QUI\Users\User $User,
        \QUI\FrontendUsers\RegistrarInterface $Registrar,
        string $registrationStatus
    ) {
        try {
            $Tracker = Piwik::getPiwikClient(QUI::getRewrite()->getProject());
        } catch (QUI\Exception $Exception) {
            return;
        }

        try {
            $Tracker->doTrackEvent(
                'quiqqer/frontend-users',
                'registration',
                'register',
                $Registrar->getTitle()
            );
        } catch (\Exception $Exception) {
            QUI\System\Log::addDebug($Exception->getTraceAsString());
        }
    }

    /**
     * @param QUI\Users\User $User
     * @param QUI\FrontendUsers\RegistrarInterface $Registrar
     */
    public static function onQuiqqerFrontendUsersUserActivate(
        \QUI\Users\User $User,
        \QUI\FrontendUsers\RegistrarInterface $Registrar
    ) {
        try {
            $Tracker = Piwik::getPiwikClient(QUI::getRewrite()->getProject());
        } catch (QUI\Exception $Exception) {
            return;
        }

        try {
            $Tracker->doTrackEvent(
                'quiqqer/frontend-users',
                'registration',
                'activate',
                $Registrar->getTitle()
            );
        } catch (\Exception $Exception) {
            QUI\System\Log::addDebug($Exception->getTraceAsString());
        }
    }
}
