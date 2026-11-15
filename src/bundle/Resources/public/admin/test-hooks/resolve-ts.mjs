// Test-only resolver: lets extensionless relative imports (the webpack/TypeScript convention) load .ts files in Node.
export async function resolve(specifier, context, nextResolve) {
    try {
        return await nextResolve(specifier, context);
    } catch (error) {
        const hasExtension = /\.[cm]?[jt]sx?$/.test(specifier);
        if (error?.code === 'ERR_MODULE_NOT_FOUND' && specifier.startsWith('.') && !hasExtension) {
            return nextResolve(`${specifier}.ts`, context);
        }
        throw error;
    }
}
