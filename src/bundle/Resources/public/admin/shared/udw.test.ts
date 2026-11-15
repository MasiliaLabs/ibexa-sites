import assert from 'node:assert/strict';
import { test } from 'node:test';

import { openUdw, toPicked } from './udw';
import type { UdwHost, UdwLocation } from './udw';

const location: UdwLocation = { id: 1018, pathString: '/1/2/1000/1018/', ContentInfo: { Content: { _id: 3072, _remoteId: 'abc', TranslatedName: 'Home' } } };

function host() {
    const log: string[] = [];
    let rendered: { type: unknown; props: Record<string, unknown> } | null = null;
    const udw: UdwHost = {
        module: 'UDW',
        container: {} as Element,
        createRoot: () => ({ render: (node) => { rendered = node as typeof rendered; log.push('render'); }, unmount: () => log.push('unmount') }),
    };

    return { udw, log, props: () => rendered?.props ?? {} };
}

const createElement = (type: unknown, props: Record<string, unknown>) => ({ type, props });

test('a location becomes what a script keeps of it', () => {
    assert.deepEqual(toPicked(location), { id: 1018, pathString: '/1/2/1000/1018/', contentId: 3072, remoteId: 'abc', name: 'Home' });
});

test('the widget gets the page configuration, a single choice and the title', () => {
    const h = host();

    openUdw(h.udw, { config: { tabsConfig: {}, multiple: true, containersOnly: true }, title: 'Choose', startingLocationId: 5, onConfirm: () => undefined }, createElement);

    assert.equal(h.props().title, 'Choose');
    assert.equal(h.props().multiple, false);
    assert.equal(h.props().containersOnly, true);
    assert.equal(h.props().startingLocationId, 5);
    assert.deepEqual(h.props().tabsConfig, {});
});

test('confirming closes the widget and gives the first location', () => {
    const h = host();
    const picked: unknown[] = [];

    openUdw(h.udw, { config: {}, title: 't', onConfirm: (p) => picked.push(p) }, createElement);
    (h.props().onConfirm as (l: UdwLocation[]) => void)([location]);

    assert.deepEqual(h.log, ['render', 'unmount']);
    assert.equal(picked.length, 1);
});

test('cancelling closes it once, and what closes it can be called again safely', () => {
    const h = host();
    let cancelled = 0;

    const close = openUdw(h.udw, { config: {}, title: 't', onConfirm: () => undefined, onCancel: () => { cancelled += 1; } }, createElement);
    (h.props().onCancel as () => void)();
    close();

    assert.equal(cancelled, 1);
    assert.deepEqual(h.log, ['render', 'unmount']);
});
