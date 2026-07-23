<?php

namespace QUI\Tests\Matomo\Integration;

use PHPUnit\Framework\TestCase;
use QUI\ERP\Order\Basket\BasketGuest;
use QUI\ERP\Order\Handler as OrderHandler;
use QUI\ERP\Products\Handler\Fields;
use QUI\ERP\Products\Handler\Products;
use QUI\ERP\Products\Utils\Package as ProductsPackage;
use ReflectionProperty;

class AjaxTrackDataIntegrationTest extends TestCase
{
    /**
     * @var list<string>
     */
    private const AJAX_FILES = [
        'ecommerce/getCategoryData.php',
        'ecommerce/getOrderData.php',
        'ecommerce/getProductData.php',
        'ecommerce/getTrackData.php',
        'ecommerce/getTrackDataForOrderProcess.php',
        'getDataLayerBridgeStatus.php',
        'getGeneralTagManagerCode.php',
        'md5.php'
    ];

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        foreach (self::AJAX_FILES as $ajaxFile) {
            require_once dirname(__DIR__, 4) . '/ajax/' . $ajaxFile;
        }
    }

    public function testAjaxFunctionsAreRegistered(): void
    {
        $callables = \QUI::getAjax()::getRegisteredCallables();

        foreach (
            [
                'package_quiqqer_matomo_ajax_ecommerce_getCategoryData',
                'package_quiqqer_matomo_ajax_ecommerce_getOrderData',
                'package_quiqqer_matomo_ajax_ecommerce_getProductData',
                'package_quiqqer_matomo_ajax_ecommerce_getTrackData',
                'package_quiqqer_matomo_ajax_ecommerce_getTrackDataForOrderProcess',
                'package_quiqqer_matomo_ajax_getDataLayerBridgeStatus',
                'package_quiqqer_matomo_ajax_getGeneralTagManagerCode',
                'package_quiqqer_matomo_ajax_md5'
            ] as $callableName
        ) {
            self::assertArrayHasKey($callableName, $callables);
        }
    }

    public function testEmptyGuestBasketReturnsNoTrackingData(): void
    {
        foreach (
            [
                BasketGuest::class,
                OrderHandler::class,
                Products::class,
                ProductsPackage::class,
                Fields::class
            ] as $requiredClass
        ) {
            if (!class_exists($requiredClass)) {
                self::markTestSkipped('Optional ERP class is unavailable: ' . $requiredClass);
            }
        }

        $Users = \QUI::getUsers();
        $SessionProperty = new ReflectionProperty($Users, 'Session');
        $previousSessionUser = $SessionProperty->getValue($Users);
        $SessionProperty->setValue($Users, $Users->getNobody());

        try {
            $callables = \QUI::getAjax()::getRegisteredCallables();
            $getTrackData = $callables[
                'package_quiqqer_matomo_ajax_ecommerce_getTrackData'
            ]['callable'];

            self::assertSame([], $getTrackData('', '[]'));
        } finally {
            $SessionProperty->setValue($Users, $previousSessionUser);
        }
    }
}
