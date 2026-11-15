/** What the choice components of the design system need, made from what the screens have: pure, so they are tested without a page. */

export interface Option {
    value: string;
    label: string;
}

export interface DropdownItem {
    id: string;
    label: string;
}

/** The id of the entry that stands for "nothing chosen": the design system's dropdown cannot select an empty id. */
export const EMPTY_ID = '__empty__';

/**
 * The items of a dropdown. When the person may choose nothing, a first entry says so (`emptyLabel`); when a value is required,
 * there is no such entry (an unchosen dropdown shows its placeholder instead).
 */
export function toItems(options: readonly Option[], emptyLabel?: string): DropdownItem[] {
    const items = options.map((option) => ({ id: option.value, label: option.label }));

    return emptyLabel === undefined ? items : [{ id: EMPTY_ID, label: emptyLabel }, ...items];
}

/** The id the dropdown is given for a value: the empty value is the "nothing" entry when there is one, nothing selected otherwise. */
export function toId(value: string, hasEmptyEntry: boolean): string {
    return value === '' ? (hasEmptyEntry ? EMPTY_ID : '') : value;
}

/** The value a screen gets back from the id the dropdown reports. */
export function fromId(id: string): string {
    return id === EMPTY_ID ? '' : id;
}

export type TagKind = 'warning' | 'success' | 'neutral' | 'error' | 'info';

/** The type of the design system's tag for a status. */
export function tagKind(status: 'draft' | 'active' | 'disabled'): TagKind {
    return status === 'active' ? 'success' : status === 'draft' ? 'warning' : 'neutral';
}
