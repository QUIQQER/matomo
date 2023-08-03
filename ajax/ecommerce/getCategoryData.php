<?php

/**
 * Return the data from the watchlist
 *
 * @param integer $watchlistId
 * @return array
 */

QUI::$Ajax->registerFunction(
    'package_quiqqer_matomo_ajax_ecommerce_getCategoryData',
    function ($project, $siteId) {
        try {
            $Project = QUI::getProjectManager()->decode($project);
            $Site = $Project->get($siteId);
            $categoryId = $Site->getAttribute('quiqqer.products.settings.categoryId');

            $Category = QUI\ERP\Products\Handler\Categories::getCategory($categoryId);

            return $Category->getTitle();
        } catch (QUI\Exception $Exception) {
            return '';
        }
    },
    ['project', 'siteId']
);
