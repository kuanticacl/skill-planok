/** Formatea un RUT chileno: 761234567 → 76.123.456-7 (sin validar). */
export function formatRut(value: string): string {
    const clean = value.replace(/[^0-9kK]/g, '').toUpperCase();
    if (clean.length < 2) return value.trim();
    const body = clean.slice(0, -1).replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    return `${body}-${clean.slice(-1)}`;
}
