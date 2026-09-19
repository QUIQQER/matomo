const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const test = require('node:test');
const vm = require('node:vm');

const source = fs.readFileSync(path.join(__dirname, '../../bin/dataLayer.js'), 'utf8');

// qTrack queues arguments objects rather than arrays.
function event() {
    return arguments;
}

async function loadBridge({buffer = [], trackToPaq = true, useTagManager = true} = {}) {
    const listeners = {};
    const window = {
        whenQuiLoaded: () => Promise.resolve(),
        dataLayer: buffer,
        MATOMO_TRACK_TO_PAQ: trackToPaq,
        MATOMO_USE_DATA_LAYER_BRIDGE: useTagManager,
        _paq: [['trackPageView']],
        _mtm: []
    };

    vm.runInNewContext(source, {
        Object,
        window,
        require: (dependencies, callback) => {
            callback({addEvent: (name, listener) => { listeners[name] = listener; }});
        }
    });
    await Promise.resolve();

    return {window, push: value => listeners.dataLayerPush(value)};
}

test('buffered page_view does not add an interaction, genuine events still do', async () => {
    const {window} = await loadBridge({buffer: [
        event('js', new Date()),
        event('event', 'page_view', {page_location: 'https://example.test/'}),
        event('event', 'sign_up')
    ]});

    assert.deepEqual(JSON.parse(JSON.stringify(window._paq)), [
        ['trackPageView'],
        ['trackEvent', 'QUIQQER Matomo', 'sign_up', '', {}]
    ]);
});

test('live page_view reaches the tag manager without adding a native interaction', async () => {
    const {window, push} = await loadBridge();
    const pageView = event('event', 'page_view', {page_location: 'https://example.test/'});
    push(pageView);

    assert.deepEqual(window._paq, [['trackPageView']]);
    assert.equal(window._mtm.length, 2);
    assert.equal(window._mtm[0], pageView);
    assert.deepEqual(JSON.parse(JSON.stringify(window._mtm[1])), {
        event: 'page_view', page_location: 'https://example.test/'
    });

    push(event('event', 'select_content', {category: 'Articles'}));
    assert.deepEqual(JSON.parse(JSON.stringify(window._paq[1])), [
        'trackEvent', 'Articles', 'select_content', '', {category: 'Articles'}
    ]);
    assert.equal(window._mtm[3].event, 'select_content');
});

test('native tracking also excludes page_view when the tag manager bridge is disabled', async () => {
    const {window, push} = await loadBridge({useTagManager: false});
    push(event('event', 'page_view'));
    push(event('event', 'sign_up'));

    assert.deepEqual(JSON.parse(JSON.stringify(window._paq)), [
        ['trackPageView'],
        ['trackEvent', 'QUIQQER Matomo', 'sign_up', '', {}]
    ]);
    assert.deepEqual(window._mtm, []);
});

test('disabling native event forwarding preserves tag manager events', async () => {
    const {window, push} = await loadBridge({trackToPaq: false, buffer: [event('event', 'sign_up')]});
    push(event('event', 'page_view'));
    push(event('event', 'sign_up'));

    assert.deepEqual(window._paq, [['trackPageView']]);
    assert.equal(window._mtm[1].event, 'page_view');
    assert.equal(window._mtm[3].event, 'sign_up');
});
