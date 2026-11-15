import { useEffect, useState } from 'react';

/** A value that follows another one after it has stopped changing for a moment: what a live check waits for before it asks the server. */
export function useDebounced<T>(value: T, delayMs: number): T {
    const [debounced, setDebounced] = useState(value);

    useEffect(() => {
        const timer = setTimeout(() => setDebounced(value), delayMs);

        return () => clearTimeout(timer);
    }, [value, delayMs]);

    return debounced;
}
