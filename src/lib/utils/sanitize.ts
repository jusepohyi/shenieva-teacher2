/**
 * Sanitize text coming from the database for display by removing backslash-escapes
 * that were inserted when storing strings (for example: Hector\/\'s -> Hector's).
 * Returns null when input is null/undefined.
 */
export function sanitizeForDisplay(value?: string | null): string | null {
    if (value === undefined || value === null) return null;
    let s = String(value);
    // Remove backslashes that escape quotes, slashes or backslashes:
    // Examples:
    //   \'  -> '
    //   \"  -> "
    //   \/  -> /
    //   \\ -> \
    s = s.replace(/\\(["'\\/])/g, '$1');
    // Trim surrounding whitespace
    s = s.trim();
    return s.length ? s : null;
}

export default sanitizeForDisplay;
