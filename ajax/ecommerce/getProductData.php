<?php

/**
 * Return the data from the watchlist
 *
 * @param integer $watchlistId
 * @return array
 */

use QUI\ERP\Products\Handler\Fields;
use QUI\ERP\Products\Handler\Products;

QUI::getAjax()->registerFunction(
    'package_quiqqer_matomo_ajax_ecommerce_getProductData',
    function ($productId) {
        if (!class_exists('QUI\ERP\Products\Handler\Products')) {
            return '';
        }

        if (!class_exists('QUI\ERP\Products\Handler\Fields')) {
            return '';
        }

        try {
            $productId = (int)$productId;
            $Product = Products::getProduct($productId);

            return [
                'productNo' => $Product->getField(Fields::FIELD_PRODUCT_NO)->getValue(),
                'title' => $Product->getTitle(),
                'category' => $Product->getCategory()?->getTitle() ?? '',
                'price' => $Product->getPrice()->getPrice(),
                'url' => $Product->getUrl()
            ];
        } catch (QUI\Exception) {
            return '';
        }
    },
    ['productId']
);
