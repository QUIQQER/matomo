<?php

use QUI\Matomo\TagManagerSync;
use QUI\Projects\Manager;

QUI::getAjax()->registerFunction(
    'package_quiqqer_matomo_ajax_getDataLayerBridgeStatus',
    function (string $projectName): array {
        return (new TagManagerSync())->getBridgeStatus(
            Manager::getProject($projectName)
        );
    },
    ['projectName'],
    'Permission::checkAdminUser'
);
