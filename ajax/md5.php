<?php
/**
 * Convert a string to md5
 *
 * @param string $str - String to convert
 * @return string
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_matomo_ajax_md5',
    function ($str) {
        return md5($str);
    },
    array('str'),
    'Permission::checkAdminUser'
);
