<?php

QUI::$Ajax->registerFunction(
    'package_quiqqer_matomo_ajax_getGeneralTagManagerCode',
    function ($projectName) {
        return \QUI\Matomo\Matomo::getGeneralTagManagerCode(\QUI\Projects\Manager::getProject($projectName));
    },
    ['projectName']
);
