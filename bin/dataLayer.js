whenQuiLoaded().then(() => {
    'use strict';

    if (typeof Proxy === 'undefined') {
        return;
    }

    window.dataLayer = window.dataLayer || [];

    window._mtm = window._mtm || [];
    window._mtm = window._mtm.concat(window.dataLayer);

    // observe data layer and make the same changes in _mtm
    window.dataLayer = new Proxy(window.dataLayer, {
        set(target, property, value, receiver)
        {
            window._mtm[property] = value;

            // Set value in array
            return Reflect.set(target, property, value, receiver);
        }
    });
});
