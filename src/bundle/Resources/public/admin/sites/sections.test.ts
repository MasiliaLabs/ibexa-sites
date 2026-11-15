import assert from 'node:assert/strict';
import { test } from 'node:test';

import { isOpen, sectionOf, sectionsWithProblems } from './sections';

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
