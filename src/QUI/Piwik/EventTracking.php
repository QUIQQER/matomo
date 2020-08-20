<?php

namespace QUI\Piwik;

use QUI;

class EventTracking
{
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

        $Tracker->doTrackEvent(
            'quiqqer/frontend-users',
            'registration',
            'register',
            $Registrar->getTitle()
        );
    }

    public static function onQuiqqerFrontendUsersUserActivate(
        \QUI\Users\User $User,
        \QUI\FrontendUsers\RegistrarInterface $Registrar
    ) {
        try {
            $Tracker = Piwik::getPiwikClient(QUI::getRewrite()->getProject());
        } catch (QUI\Exception $Exception) {
            return;
        }

        $Tracker->doTrackEvent(
            'quiqqer/frontend-users',
            'registration',
            'activate',
            $Registrar->getTitle()
        );
    }
}
