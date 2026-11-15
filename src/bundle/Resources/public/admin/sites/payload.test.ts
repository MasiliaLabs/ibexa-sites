import assert from 'node:assert/strict';
import { test } from 'node:test';

import { draftFrom, emptyDraft, isChanged, toPayload } from './payload';
import type { SiteDto } from './types';

const site: SiteDto = {
    identifier: 'demo_group', name: 'Demo', status: 'active', rootLocationId: 500, design: 'imal', base: null, sessionCookieDomain: null, adminSiteaccess: null, varDir: 'var/demo', layoutRemoteId: 'abc', email: null, address: null,
    siteaccesses: [{ name: 'demo', languages: ['eng-GB'], host: 'demo.example.org', uriPrefix: null }, { name: 'demo_fr', languages: ['fre-FR', 'eng-GB'], host: 'demo.example.org', uriPrefix: 'fr' }],
    adminSiteaccesses: [{ name: 'admin_demo', languages: ['eng-GB'], host: 'admin.demo.example.org', uriPrefix: null }],
};

test('a site of the server becomes a form state: empty values as empty text, rows with keys', () => {
    const draft = draftFrom(site);

    assert.equal(draft.base, '');
    assert.equal(draft.siteaccesses[0].uriPrefix, '');
    assert.equal(draft.siteaccesses[1].uriPrefix, 'fr');
    assert.equal(draft.varDir, 'var/demo');
    assert.equal(new Set([...draft.siteaccesses, ...draft.adminSiteaccesses].map((r) => r.key)).size, 3);
});

test('the payload has the fields, no keys and no status', () => {
    const payload = toPayload(draftFrom(site));

    assert.equal('status' in payload, false);
    assert.deepEqual(payload.siteaccesses, [
        { name: 'demo', languages: ['eng-GB'], host: 'demo.example.org', uriPrefix: '' },
        { name: 'demo_fr', languages: ['fre-FR', 'eng-GB'], host: 'demo.example.org', uriPrefix: 'fr' },
    ]);
    assert.equal(payload.rootLocationId, 500);
    assert.equal(toPayload(emptyDraft()).rootLocationId, 0, 'no root yet is 0: the server says it is missing');
});

test('a form is changed when it differs from its site, and a new one when it is not empty', () => {
    const draft = draftFrom(site);

    assert.equal(isChanged(draft, site), false);
    assert.equal(isChanged({ ...draft, name: 'Other' }, site), true);
    assert.equal(isChanged(emptyDraft(), null), false);
    assert.equal(isChanged({ ...emptyDraft(), name: 'x' }, null), true);
});
