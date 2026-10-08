const rtf = new Intl.RelativeTimeFormat('es-CL', { numeric: 'auto' });

/** "hace 3 días", "ayer", "hace 5 min"… */
export function timeAgo(iso: string | null | undefined): string {
    if (!iso) return '';
    const diff = (new Date(iso).getTime() - Date.now()) / 1000;
    const units: [Intl.RelativeTimeFormatUnit, number][] = [
        ['year', 31536000],
        ['month', 2592000],
        ['week', 604800],
        ['day', 86400],
        ['hour', 3600],
        ['minute', 60],
    ];
    for (const [unit, seconds] of units) {
        if (Math.abs(diff) >= seconds) {
            return rtf.format(Math.round(diff / seconds), unit);
        }
    }
    return 'justo ahora';
}

export function formatDateTime(iso: string | null | undefined): string {
    if (!iso) return '—';
    return new Intl.DateTimeFormat('es-CL', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(iso));
}

export function formatDate(iso: string | null | undefined): string {
    if (!iso) return '—';
    return new Intl.DateTimeFormat('es-CL', { dateStyle: 'medium' }).format(
        new Date(iso),
    );
}

export function initials(name: string | null | undefined): string {
    if (!name) return '?';
    const parts = name.trim().split(/\s+/);
    return ((parts[0]?.[0] ?? '') + (parts.length > 1 ? parts[parts.length - 1][0] : '')).toUpperCase();
}
