import assert from 'node:assert/strict';
import { test } from 'node:test';

import { emptyRow, moveRow, newKey, removeRow, updateRow } from './rows';

test('every row has a key of its own, and the key stays when the row is changed or moved', () => {
    const a = emptyRow({ name: 'a' });
    const b = emptyRow({ name: 'b' });
    const c = emptyRow({ name: 'c' });
    assert.equal(new Set([a.key, b.key, c.key, newKey()]).size, 4);

    const changed = updateRow([a, b, c], b.key, { name: 'B', key: 'hacked' });
    assert.equal(changed[1].name, 'B');
    assert.equal(changed[1].key, b.key, 'a key does not change');

    const moved = moveRow(changed, c.key, -1);
    assert.deepEqual(moved.map((r) => r.name), ['a', 'c', 'B']);
});

test('removing a row from the middle leaves the others with their keys: the next one added cannot take a key in use', () => {
    const rows = [emptyRow({ name: 'a' }), emptyRow({ name: 'b' }), emptyRow({ name: 'c' })];

    const left = removeRow(rows, rows[1].key);
    const added = [...left, emptyRow({ name: 'd' })];

    assert.deepEqual(added.map((r) => r.name), ['a', 'c', 'd']);
    assert.equal(new Set(added.map((r) => r.key)).size, 3);
});

test('a row at an end does not move past it', () => {
    const rows = [emptyRow({ name: 'a' }), emptyRow({ name: 'b' })];

    assert.deepEqual(moveRow(rows, rows[0].key, -1).map((r) => r.name), ['a', 'b']);
    assert.deepEqual(moveRow(rows, rows[1].key, 1).map((r) => r.name), ['a', 'b']);
    assert.deepEqual(moveRow(rows, 'nope', 1).map((r) => r.name), ['a', 'b']);
});
