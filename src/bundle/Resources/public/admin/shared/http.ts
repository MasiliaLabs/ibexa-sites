import { adminHeaders } from './admin';

/** One reason a value is refused, with the field it is about ("siteaccesses[1].host"; empty when it is about the whole). */
export interface FieldProblem {
    path: string;
    message: string;
}

/** Outcome of a call: HTTP errors are values, not exceptions, so a screen always has a message to show. */
export type ApiResult<T> =
    | { ok: true; data: T }
    | { ok: false; status: number; message: string; problems: FieldProblem[] };

export type Fetcher = (input: string, init?: RequestInit) => Promise<Response>;

function isRecord(value: unknown): value is Record<string, unknown> {
    return typeof value === 'object' && value !== null && !Array.isArray(value);
}

/** The sentence of an error body ({"error": {"code", "message"}}); the fallback when there is none, never "[object Object]". */
export function messageFromBody(body: unknown, fallback: string): string {
    if (isRecord(body)) {
        const error = body.error;
        if (isRecord(error) && typeof error.message === 'string' && error.message !== '') {
            return error.message;
        }
        if (typeof error === 'string' && error !== '') {
            return error;
        }
        if (typeof body.message === 'string' && body.message !== '') {
            return body.message;
        }
    }

    return fallback;
}

/** The problems of a body ({"problems": [{"path", "message"}]}), the ones that are well formed. */
export function problemsFromBody(body: unknown): FieldProblem[] {
    if (!isRecord(body) || !Array.isArray(body.problems)) {
        return [];
    }

    return body.problems.flatMap((item): FieldProblem[] => (
        isRecord(item) && typeof item.message === 'string' ? [{ path: typeof item.path === 'string' ? item.path : '', message: item.message }] : []
    ));
}

/**
 * A call to the JSON API of a page. The body is sent as JSON with the token of the page; whatever happens (a refusal, a network
 * failure, an answer that is not JSON) comes back as a result, with a message to show.
 */
export async function request<T>(fetcher: Fetcher, method: string, url: string, body?: unknown, fallbackMessage = 'The request failed.'): Promise<ApiResult<T>> {
    let response: Response;
    try {
        response = await fetcher(url, {
            method,
            credentials: 'same-origin',
            headers: adminHeaders(),
            body: body === undefined ? undefined : JSON.stringify(body),
        });
    } catch {
        return { ok: false, status: 0, message: fallbackMessage, problems: [] };
    }

    let payload: unknown = null;
    try {
        payload = await response.json();
    } catch {
        payload = null;
    }
    if (response.ok) {
        return { ok: true, data: payload as T };
    }

    return { ok: false, status: response.status, message: messageFromBody(payload, fallbackMessage), problems: problemsFromBody(payload) };
}

/**
 * The problems of a field: the ones whose path is the field itself or something under it ("siteaccesses[1]" holds
 * "siteaccesses[1].host"), so that a group can show what is wrong inside it.
 */
export function problemsFor(problems: readonly FieldProblem[], path: string): FieldProblem[] {
    return problems.filter((p) => p.path === path || p.path.startsWith(`${path}.`) || p.path.startsWith(`${path}[`));
}

/** The first message for exactly this field, or undefined: what a field shows under itself. */
export function messageFor(problems: readonly FieldProblem[], path: string): string | undefined {
    return problems.find((p) => p.path === path)?.message;
}
