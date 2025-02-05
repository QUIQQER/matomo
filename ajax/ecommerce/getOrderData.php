<?php

/**
 * Return the data from the watchlist
 *
 * @param integer $watchlistId
 * @return array
 */

QUI::$Ajax->registerFunction(
    'package_quiqqer_matomo_ajax_ecommerce_getOrderData',
    function ($orderHash) {
        if (!class_exists('QUI\ERP\Order\Handler')) {
            return '';
        }

        try {
            $Order = QUI\ERP\Order\Handler::getInstance()->getOrderByHash($orderHash);
            $Products = $Order->getArticles();
            $Products->calc();

            $products = $Products->toArray();
            $calculations = $products['calculations'];

            // @todo shipping amount
            // @todo discount amount

            return [
                'sum' => $calculations['sum'],
                'subSum' => $calculations['subSum'],
                'nettoSum' => $calculations['nettoSum'],
                'nettoSubSum' => $calculations['nettoSubSum'],
                'vatSum' => $calculations['vatSum'],
                'currency' => $Order->getCurrency()->getCode()
            ];
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::writeDebugException($Exception);

            return '';
        }
    },
    ['orderHash']
);
