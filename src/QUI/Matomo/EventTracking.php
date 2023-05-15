<?php

namespace QUI\Matomo;

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
            $Tracker = Matomo::getMatomoClient(QUI::getRewrite()->getProject());
        } catch (QUI\Exception $Exception) {
            return;
        }

        try {
            $Tracker->doTrackEvent(
                'quiqqer/frontend-users',
                'registration',
                'register'
            );
        } catch (\Exception $Exception) {
            QUI\System\Log::addError($Exception->getMessage());
            QUI\System\Log::addError($Exception->getTraceAsString());
        }
    }

    /**
     * @param QUI\Users\User $User
     * @param QUI\FrontendUsers\RegistrarInterface|bool $Registrar
     */
    public static function onQuiqqerFrontendUsersUserActivate(
        \QUI\Users\User $User,
        $Registrar = null
    ) {
        try {
            $Tracker = Matomo::getMatomoClient(QUI::getRewrite()->getProject());
        } catch (QUI\Exception $Exception) {
            return;
        }

        try {
            $Tracker->doTrackEvent(
                'quiqqer/frontend-users',
                'registration',
                'activate'
            );
        } catch (\Exception $Exception) {
            QUI\System\Log::addError($Exception->getMessage());
            QUI\System\Log::addError($Exception->getTraceAsString());
        }
    }
}
