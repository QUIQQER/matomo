/**
 * initialize the e-commerce tracking
 *
 * @author www.pcsg.de (Henning Leutz)
 * @module package/quiqqer/matomo/bin/eCommerceTracking
 */
define('package/quiqqer/matomo/bin/eCommerceTracking', [

    'qui/QUI',
    'Ajax',
    'matomoTracker'

], function (QUI, QUIAjax, matomoTracker) {
    "use strict";

    const DEBUG = false;
    let lastOrderTrack = null;

    function trackBasket(Basket) {
        if (!QUIQQER_USER.isLoggedIn) {
            return new Promise(function (resolve) {
                let products = [];
                let basketProducts = Basket.getProducts();

                for (let i = 0, len = basketProducts.length; i < len; i++) {
                    products.push(basketProducts[i].getAttributes());
                }

                QUIAjax.get('package_quiqqer_matomo_ajax_ecommerce_getTrackData', resolve, {
                    'package': 'quiqqer/matomo',
                    basketId : Basket.getId(),
                    products : JSON.encode(products)
                });
            });
        }

        return new Promise(function (resolve) {
            QUIAjax.get('package_quiqqer_matomo_ajax_ecommerce_getTrackData', resolve, {
                'package': 'quiqqer/matomo',
                basketId : Basket.getId()
            });
        });
    }

    /**
     * Return the tracking data for the basket
     *
     * @return {Promise}
     */
    function getTrackData(OrderProcess) {
        return new Promise(function (resolve) {
            if (typeof OrderProcess !== 'undefined') {
                if (typeOf(OrderProcess) === 'package/quiqqer/order/bin/frontend/classes/Basket') {
                    const Node = document.getElement('[data-qui="package/quiqqer/order/bin/frontend/controls/OrderProcess"]');

                    if (!OrderProcess.isLoaded()) {
                        return;
                    }

                    if (!Node) {
                        trackBasket(OrderProcess).then(resolve);
                        return;
                    }

                    OrderProcess = QUI.Controls.getById(Node.get('data-quiid'));

                    if (!OrderProcess) {
                        trackBasket(OrderProcess).then(resolve);
                        return;
                    }
                }

                OrderProcess.getOrder().then(function (orderHash) {
                    QUIAjax.get('package_quiqqer_matomo_ajax_ecommerce_getTrackDataForOrderProcess', resolve, {
                        'package': 'quiqqer/matomo',
                        orderHash: orderHash
                    });
                });

                return;
            }

            require(['package/quiqqer/order/bin/frontend/Basket'], function (Basket) {
                trackBasket(Basket).then(resolve);
            });
        });
    }

    /**
     * Track basket
     *
     * @return {Promise}
     */
    function track(OrderProcess) {
        if (DEBUG) {
            console.log('track basket');
        }

        return Promise.all([
            getTrackData(OrderProcess),
            matomoTracker
        ]).then(function (result) {
            let i, len, product;

            const data     = result[0],
                  Tracker  = result[1],
                  products = data.products;

            if (!products || !products.length) {
                return;
            }

            for (i = 0, len = products.length; i < len; i++) {
                product = products[i];

                Tracker.addEcommerceItem(
                    product.productNo,
                    product.title,
                    product.category,
                    product.price,
                    product.quantity
                );
            }

            Tracker.trackEcommerceCartUpdate(
                data.sum
            );
        });
    }

    /**
     * Tracks a category view
     *
     * @param siteId
     */
    function trackCategoryView(siteId) {
        if (DEBUG) {
            console.log('track category view');
        }

        matomoTracker.then(function (Tracker) {
            QUIAjax.get('package_quiqqer_matomo_ajax_ecommerce_getCategoryData', function (category) {
                try {
                    Tracker.setEcommerceView(false, false, category);
                    Tracker.trackPageView();
                } catch (e) {
                    console.error(e);
                }
            }, {
                'package': 'quiqqer/matomo',
                siteId   : siteId
            });
        });
    }

    /**
     * Tracks a product view
     *
     * @param productId
     */
    function trackProductView(productId) {
        if (DEBUG) {
            console.log('track product view');
        }

        matomoTracker.then(function (Tracker) {
            QUIAjax.get('package_quiqqer_matomo_ajax_ecommerce_getProductData', function (product) {
                const productNo = product.productNo,
                      title     = product.title,
                      category  = product.category,
                      price     = product.price;

                try {
                    Tracker.setEcommerceView(productNo, title, category, price);
                    Tracker.setCustomUrl(product.url);
                    Tracker.trackPageView();
                } catch (e) {
                    console.error(e);
                }
            }, {
                'package': 'quiqqer/matomo',
                productId: productId
            });
        });
    }

    /**
     * Return current product id
     *
     * @return {boolean|integer}
     */
    function getProductId() {
        if (typeof window.QUIQQER_PRODUCT_ID === 'undefined') {
            return false;
        }

        return window.QUIQQER_PRODUCT_ID;
    }

    /**
     * track the order
     *
     * @param orderHash
     */
    function trackOrder(orderHash) {
        if (lastOrderTrack && new window.Date() - lastOrderTrack < 500) {
            return;
        }

        if (DEBUG) {
            console.log('track order');
        }

        lastOrderTrack = new window.Date();

        matomoTracker.then(function (Tracker) {
            QUIAjax.get('package_quiqqer_matomo_ajax_ecommerce_getOrderData', function (order) {
                if (order === '') {
                    if (DEBUG) {
                        console.error('track order error');
                    }

                    return;
                }

                if (DEBUG) {
                    console.log(order);
                }

                Tracker.trackEcommerceOrder(
                    orderHash,
                    order.sum,
                    order.subSum,
                    order.vatSum,
                    false,
                    false
                );
            }, {
                'package': 'quiqqer/matomo',
                orderHash: orderHash
            });
        });
    }

    /**
     * tracks the start of a deletion process from an user
     */
    function trackUserDeleteStart() {
        matomoTracker.then(function (Tracker) {
            Tracker.trackPageView('/profile/delete/start');
        });
    }

    /**
     * tracks the success of a deletion from an user
     */
    function trackUserDelete() {
        matomoTracker.then(function (Tracker) {
            Tracker.trackPageView('/profile/delete/success');
        });
    }

    /**
     * TRACKING
     */

    // basket tracking
    require(['package/quiqqer/order/bin/frontend/Basket'], function (Basket) {
        Basket.addEvent('onAdd', track);
        Basket.addEvent('onRemove', track);

        Basket.addEvent('onClear', function () {
            if (DEBUG) {
                console.log('track clear');
            }

            matomoTracker.then(function (Tracker) {
                Tracker.clearEcommerceCart();
                Tracker.trackEcommerceCartUpdate(0);
            });
        });
    });

    // category / product tracking
    if (window.QUIQQER_SITE.type === 'quiqqer/products:types/category' && !getProductId()) {
        trackCategoryView(window.QUIQQER_SITE.id);
    }

    if (window.QUIQQER_SITE.type === 'quiqqer/products:types/category' && getProductId()) {
        trackProductView(getProductId());
    }

    QUI.addEvent('onQuiqqerProductsOpenProduct', function (Parent, productId) {
        trackProductView(productId);
    });

    QUI.addEvent('onQuiqqerProductsCloseProduct', function () {
        trackCategoryView(window.QUIQQER_SITE.id);
    });

    // order tracking
    // trackEcommerceOrder

    QUI.addEvent('onQuiqqerOrderProcessOpenStep', function (OrderProcess, step) {
        let url = '/' + step;

        if (QUIQQER_SITE.url !== '' && QUIQQER_SITE.url !== '/') {
            url = QUIQQER_SITE.url + url;
        }

        if (DEBUG) {
            console.log('track order process step', url);
        }

        matomoTracker.then(function (Tracker) {
            Tracker.trackPageView(url);
        });

        track().catch(function (err) {
            console.error(err);
        });
    });

    if (QUI.getAttribute('QUIQQER_ORDER_CHECKOUT_FINISH')) {
        trackOrder(
            QUI.getAttribute('QUIQQER_ORDER_CHECKOUT_FINISH')
        );
    }

    QUI.addEvent('onQuiqqerOrderProcessFinish', function (orderHash) {
        trackOrder(orderHash);
    });


    QUI.addEvent('onOrderSuccessful', function (Instance) {
        trackOrder(Instance.getAttribute('orderHash'));
    });


    QUI.addEvent('onQuiqqerOrderProcessLoad', function () {
        if (DEBUG) {
            console.log('track order process load ->');
        }

        track().catch(function (err) {
            console.error(err);
        });
    });

    QUI.addEvent('onQuiqqerOrderProductAdd', function (OrderProcess) {
        if (DEBUG) {
            console.log('track order process load ->');
        }

        track(OrderProcess).catch(function (err) {
            console.error(err);
        });
    });


    // registration tracking
    QUI.addEvent('onQuiqqerFrontendUsersRegisterStart', function () {
        matomoTracker.then(function (Tracker) {
            Tracker.trackPageView('/register/start');
        });
    });

    QUI.addEvent('onQuiqqerFrontendUsersRegisterSuccess', function () {
        matomoTracker.then(function (Tracker) {
            Tracker.trackPageView('/register/success');
        });
    });

    // deletion tracking
    if (QUI.getAttribute('QUIQQER_FRONTEND_USERS_ACCOUNT_DELETE_START')) {
        trackUserDeleteStart();
    }

    if (QUI.getAttribute('QUIQQER_VERIFIER_SUCCESS')) {
        const verifier = QUI.getAttribute('QUIQQER_VERIFIER_SUCCESS');

        if (verifier === 'QUIFrontendUsersUserDeleteConfirmVerification') {
            trackUserDelete();
        }
    }

    QUI.addEvent('quiqqerFrontendUsersAccountDeleteStart', trackUserDeleteStart);
    QUI.addEvent('quiqqerVerifierSuccess', function (verifier) {
        if (verifier === 'QUIFrontendUsersUserDeleteConfirmVerification') {
            trackUserDelete();
        }
    });
});
