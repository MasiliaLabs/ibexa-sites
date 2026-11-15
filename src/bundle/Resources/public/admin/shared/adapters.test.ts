import assert from 'node:assert/strict';
import { test } from 'node:test';

import { EMPTY_ID, fromId, tagKind, toId, toItems } from './adapters';
import { isOpen, sectionOf, sectionsWithProblems } from './sections';

const options = [{ value: 'imal', label: 'imal' }, { value: 'fossil_exit', label: 'fossil_exit' }];

test('a dropdown that may stay empty starts with an entry that says so', () => {
    assert.deepEqual(toItems(options, 'None').map((i) => i.id), [EMPTY_ID, 'imal', 'fossil_exit']);
    assert.equal(toItems(options, 'None')[0].label, 'None');
});

test('a required dropdown has no such entry', () => {
    assert.deepEqual(toItems(options).map((i) => i.id), ['imal', 'fossil_exit']);
});

test('the empty value is the empty entry when there is one, and nothing selected when there is not', () => {
    assert.equal(toId('', true), EMPTY_ID);
    assert.equal(toId('', false), '');
    assert.equal(toId('imal', true), 'imal');
    assert.equal(fromId(EMPTY_ID), '');
    assert.equal(fromId('imal'), 'imal');
});

test('a status has a tag of its own colour', () => {
    assert.equal(tagKind('active'), 'success');
    assert.equal(tagKind('draft'), 'warning');
    assert.equal(tagKind('disabled'), 'neutral');
});

test('a field belongs to the section it is shown in', () => {
    assert.equal(sectionOf('name'), 'identity');
    assert.equal(sectionOf('identifier'), 'identity');
    assert.equal(sectionOf('siteaccesses[1].host'), 'addresses');
    assert.equal(sectionOf('siteaccesses'), 'addresses');
    assert.equal(sectionOf('adminSiteaccesses[0].name'), 'admins');
    assert.equal(sectionOf('sessionCookieDomain'), 'advanced');
    assert.equal(sectionOf('varDir'), 'advanced');
    assert.equal(sectionOf('adminSiteaccess'), 'advanced');
    assert.equal(sectionOf('rootLocationId'), 'settings');
    assert.equal(sectionOf('design'), 'settings');
    assert.equal(sectionOf('layoutRemoteId'), 'settings');
});

test('the main sections are open, the optional ones open when they hold something or have a problem', () => {
    const none = new Set<never>();
    assert.equal(isOpen('identity', { admins: false, advanced: false }, none), true);
    assert.equal(isOpen('settings', { admins: false, advanced: false }, none), true);
    assert.equal(isOpen('admins', { admins: false, advanced: false }, none), false);
    assert.equal(isOpen('advanced', { admins: false, advanced: false }, none), false);
    assert.equal(isOpen('admins', { admins: true, advanced: false }, none), true);
    assert.equal(isOpen('advanced', { admins: false, advanced: false }, sectionsWithProblems(['sessionCookieDomain'])), true);
});
