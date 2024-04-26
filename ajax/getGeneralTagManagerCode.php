<?php

use QUI\Matomo\Matomo;
use QUI\Projects\Manager;

QUI::$Ajax->registerFunction(
    'package_quiqqer_matomo_ajax_getGeneralTagManagerCode',
    function ($projectName) {
        return Matomo::getGeneralTagManagerCode(Manager::getProject($projectName));
    },
    ['projectName']
);
