window.whenQuiLoaded().then(() => {
    'use strict';

    window.dataLayer = window.dataLayer || [];
    window._mtm = window._mtm || [];
    window._mtm = window._mtm.concat(window.dataLayer);

    require(['qui/QUI'], function(QUI) {
        QUI.addEvent('dataLayerPush', function(value) {
            window._mtm.push(value);
        });
    });
});
