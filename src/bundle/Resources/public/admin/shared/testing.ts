/** A fetcher that answers from a list of canned responses, and remembers what it was asked. */
export interface Call {
    url: string;
    init: RequestInit | undefined;
}

export function fakeFetch(responses: Array<{ status: number; body?: unknown } | Error>): { fetcher: (url: string, init?: RequestInit) => Promise<Response>; calls: Call[] } {
    const calls: Call[] = [];
    const queue = [...responses];

    return {
        calls,
        fetcher: async (url, init) => {
            calls.push({ url, init });
            const next = queue.shift();
            if (next === undefined) {
                throw new Error('No more canned responses.');
            }
            if (next instanceof Error) {
                throw next;
            }

            return new Response(next.body === undefined ? null : (typeof next.body === 'string' ? next.body : JSON.stringify(next.body)), { status: next.status });
        },
    };
}
