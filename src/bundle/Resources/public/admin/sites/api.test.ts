import assert from 'node:assert/strict';
import { test } from 'node:test';

import { fakeFetch } from '../shared/testing';
import { apiBase, createApi } from './api';
import { draftFrom } from './payload';
import type { SiteDto } from './types';

const site: SiteDto = {
    identifier: 'demo_group', name: 'Demo', status: 'draft', rootLocationId: 500, design: 'imal', base: null, sessionCookieDomain: null, adminSiteaccess: null, varDir: null, layoutRemoteId: null,
    siteaccesses: [{ name: 'demo', languages: ['eng-GB'], host: 'demo.example.org', uriPrefix: null }], adminSiteaccesses: [],
};

test('each call goes to its route with its method and body', async () => {
    const { fetcher, calls } = fakeFetch(Array.from({ length: 9 }, () => ({ status: 200, body: {} })));
    const api = createApi(fetcher, 'failed', '/api/masilia/sites');
    const draft = draftFrom(site);

    await api.data();
    await api.location(1018);
    await api.layout('a b');
    await api.validate(draft, true, true);
    await api.create(draft);
    await api.update('demo_group', draft);
    await api.setStatus('demo_group', 'active');
    await api.remove('demo_group');
    await api.setDefault('demo');

    assert.deepEqual(calls.map((c) => `${c.init?.method} ${c.url}`), [
        'GET /api/masilia/sites/data',
        'GET /api/masilia/sites/locations/1018',
        'GET /api/masilia/sites/layouts/a%20b',
        'POST /api/masilia/sites/validate',
        'POST /api/masilia/sites',
        'PUT /api/masilia/sites/demo_group',
        'PUT /api/masilia/sites/demo_group/status',
        'DELETE /api/masilia/sites/demo_group',
        'PUT /api/masilia/sites/settings/default',
    ]);
    assert.deepEqual(JSON.parse(String(calls[3].init?.body)).isNew, true);
    assert.equal(JSON.parse(String(calls[3].init?.body)).site.identifier, 'demo_group');
    assert.equal(calls[0].init?.body, undefined);
    assert.equal(JSON.parse(String(calls[6].init?.body)).status, 'active');
});

test('the base of the API follows the siteaccess prefix the page was given', () => {
    assert.equal(apiBase('/api/masilia/sites/data'), '/api/masilia/sites');
    assert.equal(apiBase('/admin/api/masilia/sites/data'), '/admin/api/masilia/sites');
});
