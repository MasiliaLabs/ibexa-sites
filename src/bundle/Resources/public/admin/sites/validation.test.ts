import assert from 'node:assert/strict';
import { test } from 'node:test';

import { createTranslator } from '../shared/i18n';
import { DEFAULTS } from './messages';
import { emptyRow } from './rows';
import type { SiteDraft, SiteDto } from './types';
import { hasProblems, mergeProblems, normalizedHost, normalizedPrefix, validateSite } from './validation';
import { emptyDraft } from './payload';

const t = createTranslator(DEFAULTS, 'test', { trans: (id) => id });
const options = { languages: ['eng-GB', 'fre-FR', 'ara-MA'], designs: ['imal', 'fossil_exit'], baseGroups: ['front_group'], adminSiteaccesses: ['admin'] };

function valid(overrides: Partial<SiteDraft> = {}): SiteDraft {
    return {
        ...emptyDraft(),
        identifier: 'demo_group',
        name: 'Demo',
        rootLocationId: 500,
        design: 'imal',
        siteaccesses: [emptyRow({ name: 'demo', languages: ['eng-GB'], host: 'demo.example.org' }), emptyRow({ name: 'demo_fr', languages: ['fre-FR', 'eng-GB'], host: 'demo.example.org', uriPrefix: 'fr' })],
        ...overrides,
    };
}

const other: SiteDto = {
    identifier: 'other_group', name: 'Other', status: 'active', rootLocationId: 600, design: 'imal', base: null, sessionCookieDomain: null, adminSiteaccess: null, varDir: null, layoutRemoteId: null,
    siteaccesses: [{ name: 'other', languages: ['eng-GB'], host: 'other.example.org', uriPrefix: null }], adminSiteaccesses: [{ name: 'admin_other', languages: ['eng-GB'], host: 'admin.other.example.org', uriPrefix: null }],
};

function paths(draft: SiteDraft, isNew = true, others: SiteDto[] = [other]): string[] {
    return validateSite(draft, { isNew, others, options }, t).map((p) => p.path);
}

test('a valid site has no problem', () => {
    assert.deepEqual(validateSite(valid(), { isNew: true, others: [other], options }, t), []);
});

test('each field says what is wrong with it, with the path the server uses', () => {
    const bad = valid({
        identifier: 'Bad Id', name: '  ', rootLocationId: null, design: 'nope', base: 'x_group', varDir: '../x', layoutRemoteId: 'a b',
        siteaccesses: [emptyRow({ name: 'X', languages: [], host: 'Bad Host', uriPrefix: 'a/b c' })],
    });

    const got = paths(bad);

    for (const expected of ['identifier', 'name', 'rootLocationId', 'design', 'base', 'varDir', 'layoutRemoteId', 'siteaccesses[0].name', 'siteaccesses[0].host', 'siteaccesses[0].uriPrefix', 'siteaccesses[0].languages']) {
        assert.ok(got.includes(expected), expected);
    }
});

test('a site without a siteaccess is told so', () => {
    assert.ok(paths(valid({ siteaccesses: [] })).includes('siteaccesses'));
});

test('what the server cleans is cleaned before it is checked (host in capitals, prefix with slashes)', () => {
    const draft = valid({ siteaccesses: [emptyRow({ name: 'demo', languages: ['eng-GB'], host: ' Demo.Example.ORG ' }), emptyRow({ name: 'demo_fr', languages: ['fre-FR'], host: 'demo.example.org', uriPrefix: ' /FR/ ' })] });

    assert.deepEqual(paths(draft), []);
    assert.equal(normalizedHost(' A.B '), 'a.b');
    assert.equal(normalizedPrefix(' /Fr/ '), 'fr');
});

test('names, addresses and the root of other sites, and of the site itself, are told', () => {
    const clash = valid({
        rootLocationId: 600,
        siteaccesses: [emptyRow({ name: 'other', languages: ['eng-GB'], host: 'other.example.org' }), emptyRow({ name: 'other', languages: ['fre-FR'], host: 'second.example.org' })],
    });

    const got = paths(clash);

    assert.ok(got.includes('rootLocationId'));
    assert.ok(got.filter((p) => p === 'siteaccesses[0].name').length === 1 && got.includes('siteaccesses[1].name'), 'a name given twice, or taken');
    assert.ok(got.includes('siteaccesses[0].host'), 'the address of another site');
});

test('an identifier that exists is refused as a new site only', () => {
    const draft = valid({ identifier: 'other_group', rootLocationId: 700, siteaccesses: [emptyRow({ name: 'brand_new', languages: ['eng-GB'], host: 'new.example.org' })] });

    assert.ok(paths(draft, true).includes('identifier'));
    assert.ok(!paths(draft, false, [{ ...other, identifier: 'someone_else', siteaccesses: [], adminSiteaccesses: [] }]).includes('identifier'));
});

test('two siteaccesses cannot share an address, nor both be at the root of a host', () => {
    const same = valid({ siteaccesses: [emptyRow({ name: 'a_one', languages: ['eng-GB'], host: 'x.example.org' }), emptyRow({ name: 'a_two', languages: ['fre-FR'], host: 'X.example.org' })] });

    const got = validateSite(same, { isNew: true, others: [], options }, t);

    assert.ok(got.some((p) => p.path === 'siteaccesses[1].host'), 'the same address');
    assert.ok(got.some((p) => p.path === 'siteaccesses[1].uriPrefix'), 'a second root of the host');
    assert.ok(!got.some((p) => p.path === 'siteaccesses[0].uriPrefix'), 'the first keeps the root');
});

test('languages: none, twice, or one Ibexa does not have', () => {
    const rows = (languages: string[]) => valid({ siteaccesses: [emptyRow({ name: 'demo', languages, host: 'demo.example.org' })] });

    assert.ok(paths(rows([])).includes('siteaccesses[0].languages'));
    assert.ok(paths(rows(['eng-GB', 'eng-GB'])).includes('siteaccesses[0].languages'));
    assert.ok(paths(rows(['spa-ES'])).includes('siteaccesses[0].languages'));
});

test('the admin siteaccesses follow the same rules, under their own path, and share names with the fronts', () => {
    const draft = valid({ adminSiteaccesses: [emptyRow({ name: 'demo', languages: ['eng-GB'], host: 'Bad Host' })] });

    const got = paths(draft);

    assert.ok(got.includes('adminSiteaccesses[0].host'));
    assert.ok(got.includes('adminSiteaccesses[0].name') && got.includes('siteaccesses[0].name'), 'a name used by a front and an admin is given twice');
});

test('the cookie domain must be a domain that covers every host of the site', () => {
    const draft = (cookie: string) => valid({ sessionCookieDomain: cookie, adminSiteaccesses: [emptyRow({ name: 'admin_demo', languages: ['eng-GB'], host: 'admin.demo.example.org' })] });

    assert.deepEqual(paths(draft('.demo.example.org')), []);
    assert.deepEqual(paths(draft('demo.example.org')), [], 'the dot is optional');
    assert.ok(paths(draft('.org')).includes('sessionCookieDomain'));
    assert.ok(paths(draft('.Demo.example.org')).includes('sessionCookieDomain'));
    assert.ok(paths(draft('.admin.demo.example.org')).includes('sessionCookieDomain'), 'it does not cover the front host');
});

test('problems of the page and of the server are merged without repeats', () => {
    const merged = mergeProblems([{ path: 'name', message: 'A' }], [{ path: 'name', message: 'A' }, { path: 'name', message: 'B' }, { path: 'design', message: 'A' }]);

    assert.deepEqual(merged.map((p) => `${p.path}:${p.message}`), ['name:A', 'name:B', 'design:A']);
    assert.equal(hasProblems(merged), true);
    assert.equal(hasProblems([]), false);
});
