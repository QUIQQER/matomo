<?php

/**
 * Return the data from the watchlist
 *
 * @param integer $watchlistId
 * @return array
 */

use QUI\ERP\Products\Category\Category;
use QUI\ERP\Products\Handler\Products;

QUI::getAjax()->registerFunction(
    'package_quiqqer_matomo_ajax_ecommerce_getTrackDataForOrderProcess',
    function ($orderHash) {
        if (
            !class_exists('QUI\ERP\Order\Handler')
            || !class_exists('QUI\ERP\Products\Handler\Products')
        ) {
            return [];
        }

        try {
            $Orders = QUI\ERP\Order\Handler::getInstance();
            $Order = $Orders->getOrderByHash($orderHash);
            $Articles = $Order->getArticles();
        } catch (QUI\Exception) {
            return [];
        }

        $Locale = QUI::getLocale();
        $list = $Articles->toArray();
        $articles = $list['articles'];

        // generate result
        $result = [];

        foreach ($articles as $article) {
            $category = '';
            $categoryId = '';
            $categories = '';

            try {
                $Product = Products::getProduct((int)$article['id']);

                // categories
                $Category = $Product->getCategory();
                $categories = $Product->getCategories();

                if ($Category) {
                    $category = $Category->getTitle($Locale);
                    $categoryId = $Category->getId();
                }

                $categories = array_map(function ($Category) use ($Locale) {
                    /* @var $Category Category */
                    return [
                        'id' => $Category->getId(),
                        'title' => $Category->getTitle($Locale)
                    ];
                }, $categories);
            } catch (QUI\Exception $Exception) {
                // nothing
                QUI\System\Log::addDebug($Exception->getMessage());
            }


            $result['products'][] = [
                'id' => $article['id'],
                'category' => $category,
                'categoryId' => $categoryId,
                'categories' => $categories,
                'title' => $article['title'],
                'productNo' => $article['articleNo'],
                'price' => $article['sum']
            ];
        }

        $result['sum'] = $list['calculations']['sum'];
        $result['subSum'] = $list['calculations']['subSum'];
        $result['nettoSum'] = $list['calculations']['nettoSum'];
        $result['nettoSubSum'] = $list['calculations']['nettoSubSum'];
        $result['vatArray'] = $list['calculations']['vatArray'];
        $result['vatText'] = $list['calculations']['vatText'];
        $result['isEuVat'] = $list['calculations']['isEuVat'];
        $result['isNetto'] = $list['calculations']['isNetto'];
        $result['currencyData'] = $list['calculations']['currencyData'];

        return $result;
    },
    ['orderHash']
);
