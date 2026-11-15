/** The identifier of the tab that a key press moves to (arrows, Home, End), or null when the key is not one of those. */
export function tabAfterKey(ids: readonly string[], current: string, key: string): string | null {
    const index = ids.indexOf(current);
    if (index < 0 || ids.length === 0) {
        return null;
    }
    switch (key) {
        case 'ArrowRight': return ids[(index + 1) % ids.length];
        case 'ArrowLeft': return ids[(index - 1 + ids.length) % ids.length];
        case 'Home': return ids[0];
        case 'End': return ids[ids.length - 1];
        default: return null;
    }
}
