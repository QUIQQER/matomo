window.whenQuiLoaded().then(() => {
    'use strict';

    const mapDataLayerEntryToPaq = function(value) {
        if (value[0] !== 'event') {
            return false;
        }

        if (typeof value[2] === 'undefined') {
            value[2] = {};
        }

        let category = 'QUIQQER Matomo';

        if (typeof value[2].category !== 'undefined') {
            category = value[2].category;
        }
        
        return ['trackEvent', category, value[1], '', value[2]];
    };

    const mapDataLayerEventToMtm = function (dataLayerData) {
        if (dataLayerData[0] !== 'event') {
            return false;
        }

        if (dataLayerData[1] === undefined) {
            return false;
        }

        const eventName = dataLayerData[1];

        let eventData = {};

        if (dataLayerData[2] instanceof Object) {
            eventData = dataLayerData[2];

        }

        const mtmEvent = {
            event: eventName,
            ...eventData
        };

        return mtmEvent;
    };

    window.dataLayer = window.dataLayer || [];

    if (window.MATOMO_USE_DATA_LAYER_BRIDGE) {
        window._mtm = window._mtm || [];
    }

    if (window.MATOMO_TRACK_TO_PAQ) {
        window._paq = window._paq || [];

        for (let i = 0; i < window.dataLayer.length; i++) {
            const paqData = mapDataLayerEntryToPaq(window.dataLayer[i]);

            if (!paqData) {
                continue;
            }

            window._paq.push(paqData);
        }
    }

    require(['qui/QUI'], function(QUI) {
        QUI.addEvent('dataLayerPush', function(value) {
            if (window.MATOMO_USE_DATA_LAYER_BRIDGE) {
                window._mtm.push(value);

                const mtmEvent = mapDataLayerEventToMtm(value);
                if (mtmEvent) {
                    window._mtm.push(mtmEvent);
                }
            }

            if (!window.MATOMO_TRACK_TO_PAQ) {
                return;
            }

            const paqData = mapDataLayerEntryToPaq(value);

            if (!paqData) {
                return;
            }

            window._paq.push(paqData);
        });
    });
});
