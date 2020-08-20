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
    }

    public static function onQuiqqerFrontendUsersUserActivate(
        \QUI\Users\User $User,
        \QUI\FrontendUsers\RegistrarInterface $Registrar
    ) {
    }
}
