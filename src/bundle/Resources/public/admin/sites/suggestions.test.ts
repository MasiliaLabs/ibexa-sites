import assert from 'node:assert/strict';
import { test } from 'node:test';

import { commonCookieDomain, initialLanguages, stem, suggestIdentifier, suggestSiteaccessName, suggestSiteaccesses } from './suggestions';

test('an identifier is made from a name', () => {
    assert.equal(suggestIdentifier('Fossil Exit'), 'fossil_exit_group');
    assert.equal(suggestIdentifier('  Énergies renouvelables !  '), 'energies_renouvelables_group');
    assert.equal(suggestIdentifier('2040 Pathway'), 'pathway_group', 'it starts with a letter');
    assert.equal(suggestIdentifier('!!!'), '');
    assert.ok(suggestIdentifier('A very long name that goes on and on and on').length <= 30);
});

test('the siteaccess names follow the identifier and the language', () => {
    assert.equal(stem('demo_group'), 'demo');
    assert.equal(suggestSiteaccessName('demo_group', 'eng-GB', true), 'demo');
    assert.equal(suggestSiteaccessName('demo_group', 'fre-FR', false), 'demo_fr');
});

test('several languages under one host: the first at the root, the others under their prefix with the first as fallback', () => {
    const rows = suggestSiteaccesses('demo_group', ['eng-GB', 'fre-FR', 'ara-MA'], 'demo.example.org');

    assert.deepEqual(rows.map((r) => [r.name, r.languages, r.host, r.uriPrefix]), [
        ['demo', ['eng-GB'], 'demo.example.org', ''],
        ['demo_fr', ['fre-FR', 'eng-GB'], 'demo.example.org', 'fr'],
        ['demo_ar', ['ara-MA', 'eng-GB'], 'demo.example.org', 'ar'],
    ]);
    assert.equal(new Set(rows.map((r) => r.key)).size, 3);
});

test('the cookie domain is what the hosts share, with its dot', () => {
    assert.equal(commonCookieDomain(['demo.example.org', 'admin.demo.example.org']), '.demo.example.org');
    assert.equal(commonCookieDomain(['demo.example.org']), '.demo.example.org');
    assert.equal(commonCookieDomain(['a.example.org', 'b.example.org']), '.example.org');
    assert.equal(commonCookieDomain(['a.one.org', 'b.two.net']), null);
    assert.equal(commonCookieDomain(['a.org', 'b.net']), null, 'one label is not enough');
    assert.equal(commonCookieDomain([' ', '']), null);
});

test('a new site starts with English when there is some, else with the first language', () => {
    assert.deepEqual(initialLanguages(['ara-MA', 'eng-GB', 'fre-FR']), ['eng-GB']);
    assert.deepEqual(initialLanguages(['ara-MA', 'fre-FR']), ['ara-MA']);
    assert.deepEqual(initialLanguages([]), []);
});
