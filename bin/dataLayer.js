window.whenQuiLoaded().then(() => {
    'use strict';

    const mapDataLayerToPaq = function(value) {
        if (value[0] !== 'event') {
            return false;
        }

        let category = 'QUIQQER Matomo';

        if (typeof value[2].category !== 'undefined') {
            category = value[2].category;
        }

        return ['trackEvent', category, value[1], '', value[2]];
    };


    window.dataLayer = window.dataLayer || [];
    window._mtm = window._mtm || [];
    window._mtm = window._mtm.concat(window.dataLayer);


    let i, len, convertedValue;

    if (window.MATOMO_TRACK_TO_PAQ) {
        window._paq = window._paq || [];

        for (i = 0, len = window.dataLayer.length; i < len; i++) {
            convertedValue = mapDataLayerToPaq(window.dataLayer[i]);
            
            if (convertedValue) {
                window._paq.push(convertedValue);
            }
        }
    }

    require(['qui/QUI'], function(QUI) {
        QUI.addEvent('dataLayerPush', function(value) {
            window._mtm.push(value);
            convertedValue = mapDataLayerToPaq(value);

            if (window.MATOMO_TRACK_TO_PAQ && mapDataLayerToPaq(value)) {
                window._paq.push(convertedValue);
            }
        });
    });
});