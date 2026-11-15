import assert from 'node:assert/strict';
import { test } from 'node:test';

import { addressLines, addressOf, shortCode } from './addresses';
import { emptyRow } from './rows';

test('the short code of a language is the one the server uses', () => {
    assert.equal(shortCode('fre-FR'), 'fr');
    assert.equal(shortCode('eng-GB'), 'en');
    assert.equal(shortCode('ara-MA'), 'ar');
    assert.equal(shortCode('xxx-YY'), 'xxx', 'an unknown language keeps its three letters');
});

test('the address a visitor types: host, and prefix when there is one', () => {
    assert.equal(addressOf({ host: 'Demo.Example.org', uriPrefix: '' }), 'https://demo.example.org/');
    assert.equal(addressOf({ host: 'demo.example.org', uriPrefix: ' /FR/ ' }), 'https://demo.example.org/fr');
    assert.equal(addressOf({ host: 'demo.example.org', uriPrefix: 'fr' }, 'http'), 'http://demo.example.org/fr');
    assert.equal(addressOf({ host: '  ', uriPrefix: 'fr' }), '', 'no host, no address');
});

test('only the siteaccesses that have a host are listed', () => {
    const lines = addressLines([emptyRow({ name: 'a', host: 'a.example.org' }), emptyRow({ name: 'b', host: '' })]);

    assert.deepEqual(lines.map((l) => [l.name, l.url]), [['a', 'https://a.example.org/']]);
});
