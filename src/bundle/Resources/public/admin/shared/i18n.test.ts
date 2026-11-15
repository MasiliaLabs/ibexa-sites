import assert from 'node:assert/strict';
import { test } from 'node:test';

import { createTranslator } from './i18n';

const defaults = { 'a.b': 'Hello %name%', plain: 'Plain' };

test('the translation of the admin is used when it has one', () => {
    const t = createTranslator(defaults, 'dom', { trans: (id, p, d) => `${d}:${id}:${p?.name}` });

    assert.equal(t('a.b', { name: 'Ada' }), 'dom:a.b:Ada');
});

test('a key the admin does not know gives the default, filled in', () => {
    const t = createTranslator(defaults, 'dom', { trans: (id) => id });

    assert.equal(t('a.b', { name: 'Ada' }), 'Hello Ada');
    assert.equal(t('plain'), 'Plain');
    assert.equal(t('unknown.key'), 'unknown.key', 'no default either: the key shows, never an empty text');
});

test('without a translator the defaults are used', () => {
    const t = createTranslator(defaults, 'dom');

    assert.equal(t('plain'), 'Plain');
});
