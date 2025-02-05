<?php

namespace QUI\Matomo;

use Exception;
use QUI;
use QUI\FrontendUsers\RegistrarInterface;
use QUI\Interfaces\Users\User;

/**
 * Class EventTracking
 */
class EventTracking
{
    /**
     * @param QUI\Users\User $User
     * @param RegistrarInterface $Registrar
     * @param string $registrationStatus
     */
    public static function onQuiqqerFrontendUsersUserRegister(
        User $User,
        RegistrarInterface $Registrar, // @phpstan-ignore-line
        string $registrationStatus
    ): void {
        try {
            $Tracker = Matomo::getMatomoClient(QUI::getRewrite()->getProject());
        } catch (QUI\Exception) {
            return;
        }

        try {
            $Tracker->doTrackEvent(
                'quiqqer/frontend-users',
                'registration',
                'register'
            );
        } catch (Exception $Exception) {
            QUI\System\Log::addError($Exception->getMessage());
            QUI\System\Log::addError($Exception->getTraceAsString());
        }
    }

    /**
     * @param QUI\Users\User $User
     * @param RegistrarInterface|null $Registrar
     */
    public static function onQuiqqerFrontendUsersUserActivate(
        User $User,
        null | RegistrarInterface $Registrar = null // @phpstan-ignore-line
    ): void
    {
        try {
            $Tracker = Matomo::getMatomoClient(QUI::getRewrite()->getProject());
        } catch (QUI\Exception) {
            return;
        }

        try {
            $Tracker->doTrackEvent(
                'quiqqer/frontend-users',
                'registration',
                'activate'
            );
        } catch (Exception $Exception) {
            QUI\System\Log::addError($Exception->getMessage());
            QUI\System\Log::addError($Exception->getTraceAsString());
        }
    }
}
