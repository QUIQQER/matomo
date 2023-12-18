window.whenQuiLoaded().then(() => {
    'use strict';

    window.dataLayer = window.dataLayer || [];
    window._mtm = window._mtm || [];
    window._mtm = window._mtm.concat(window.dataLayer);

    if (window.MATOMO_TRACK_TO_PAQ) {
        window._paq = window._paq || [];
        window._paq = window._paq.concat(window.dataLayer);
    }

    require(['qui/QUI'], function(QUI) {
        QUI.addEvent('dataLayerPush', function(value) {
            window._mtm.push(value);

            if (window.MATOMO_TRACK_TO_PAQ) {
                window._paq.push(value);
            }
        });
    });
});
