import assert from 'node:assert/strict';
import { test } from 'node:test';

import { fakeFetch } from './testing';
import { messageFor, messageFromBody, problemsFor, problemsFromBody, request } from './http';

test('a success gives the data and sends JSON with the token header', async () => {
    const { fetcher, calls } = fakeFetch([{ status: 201, body: { site: { identifier: 'demo_group' } } }]);

    const result = await request<{ site: { identifier: string } }>(fetcher, 'POST', '/api/x', { name: 'Demo' });

    assert.equal(result.ok, true);
    assert.equal(result.ok && result.data.site.identifier, 'demo_group');
    assert.equal(calls[0].url, '/api/x');
    assert.equal(calls[0].init?.method, 'POST');
    assert.equal(calls[0].init?.body, '{"name":"Demo"}');
    assert.equal((calls[0].init?.headers as Record<string, string>)['Content-Type'], 'application/json');
    assert.ok('X-CSRF-Token' in (calls[0].init?.headers as Record<string, string>));
});

test('a refusal is a value with its message and its problems by field', async () => {
    const { fetcher } = fakeFetch([{ status: 400, body: { error: { code: 'invalid', message: 'Two things are wrong.' }, problems: [{ path: 'name', message: 'Required.' }, { path: 'siteaccesses[0].host', message: 'Not a host.' }, { nope: 1 }] } }]);

    const result = await request(fetcher, 'POST', '/api/x', {});

    assert.equal(result.ok, false);
    assert.ok(!result.ok);
    assert.equal(result.status, 400);
    assert.equal(result.message, 'Two things are wrong.');
    assert.deepEqual(result.problems.map((p) => p.path), ['name', 'siteaccesses[0].host']);
});

test('a network failure or an answer that is not JSON still gives a message', async () => {
    const down = await request(fakeFetch([new Error('offline')]).fetcher, 'GET', '/x', undefined, 'It failed.');
    const html = await request(fakeFetch([{ status: 500, body: '<html>oops</html>' }]).fetcher, 'GET', '/x', undefined, 'Server error.');

    assert.ok(!down.ok && down.status === 0 && down.message === 'It failed.');
    assert.ok(!html.ok && html.status === 500 && html.message === 'Server error.');
});

test('the message of a body is never an object', () => {
    assert.equal(messageFromBody({ error: { message: 'A' } }, 'f'), 'A');
    assert.equal(messageFromBody({ error: 'B' }, 'f'), 'B');
    assert.equal(messageFromBody({ message: 'C' }, 'f'), 'C');
    assert.equal(messageFromBody({ error: { code: 1 } }, 'f'), 'f');
    assert.equal(messageFromBody(null, 'f'), 'f');
    assert.deepEqual(problemsFromBody({ problems: 'x' }), []);
});

test('the problems of a field are those of the field and of what is under it', () => {
    const problems = problemsFromBody({ problems: [
        { path: 'siteaccesses[1].host', message: 'H' },
        { path: 'siteaccesses[1]', message: 'S' },
        { path: 'siteaccesses[10].host', message: 'X' },
        { path: 'name', message: 'N' },
    ] });

    assert.deepEqual(problemsFor(problems, 'siteaccesses[1]').map((p) => p.message), ['H', 'S']);
    assert.equal(messageFor(problems, 'siteaccesses[1].host'), 'H');
    assert.equal(messageFor(problems, 'siteaccesses[1].name'), undefined);
});
