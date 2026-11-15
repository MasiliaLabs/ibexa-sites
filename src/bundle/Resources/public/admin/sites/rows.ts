import type { SiteaccessDraft } from './types';

let counter = 0;

/** A key for a new row: unique for the life of the page, so that React keeps a row's fields when the ones above it change. */
export function newKey(): string {
    counter += 1;

    return `row-${counter}`;
}

export function emptyRow(overrides: Partial<SiteaccessDraft> = {}): SiteaccessDraft {
    return { key: newKey(), name: '', languages: [], host: '', uriPrefix: '', ...overrides };
}

export function updateRow(rows: readonly SiteaccessDraft[], key: string, changes: Partial<SiteaccessDraft>): SiteaccessDraft[] {
    return rows.map((row) => (row.key === key ? { ...row, ...changes, key: row.key } : row));
}

export function removeRow(rows: readonly SiteaccessDraft[], key: string): SiteaccessDraft[] {
    return rows.filter((row) => row.key !== key);
}

/** The rows with one moved by a step up (-1) or down (+1); unchanged when it cannot move. */
export function moveRow(rows: readonly SiteaccessDraft[], key: string, step: -1 | 1): SiteaccessDraft[] {
    const from = rows.findIndex((row) => row.key === key);
    const to = from + step;
    if (from < 0 || to < 0 || to >= rows.length) {
        return [...rows];
    }
    const next = [...rows];
    [next[from], next[to]] = [next[to], next[from]];

    return next;
}
