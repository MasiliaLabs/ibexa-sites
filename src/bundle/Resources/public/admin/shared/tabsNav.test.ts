import assert from 'node:assert/strict';
import { test } from 'node:test';

import { tabAfterKey } from './tabsNav';

const ids = ['sites', 'default', 'other'];

test('the arrows move between tabs and wrap around, Home and End go to the ends', () => {
    assert.equal(tabAfterKey(ids, 'sites', 'ArrowRight'), 'default');
    assert.equal(tabAfterKey(ids, 'other', 'ArrowRight'), 'sites');
    assert.equal(tabAfterKey(ids, 'sites', 'ArrowLeft'), 'other');
    assert.equal(tabAfterKey(ids, 'default', 'Home'), 'sites');
    assert.equal(tabAfterKey(ids, 'default', 'End'), 'other');
});

test('any other key, or an unknown tab, moves nowhere', () => {
    assert.equal(tabAfterKey(ids, 'sites', 'Enter'), null);
    assert.equal(tabAfterKey(ids, 'nope', 'ArrowRight'), null);
    assert.equal(tabAfterKey([], 'sites', 'ArrowRight'), null);
});
