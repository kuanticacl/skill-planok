export class HttpError extends Error {
    constructor(
        public status: number,
        public body: { message?: string; errors?: Record<string, string[]> } | null,
    ) {
        super(`HTTP ${status}`);
    }

    /** Primer mensaje de validación por campo. */
    get fieldErrors(): Record<string, string> {
        return Object.fromEntries(Object.entries(this.body?.errors ?? {}).map(([k, v]) => [k, v[0]]));
    }
}

/** Petición JSON con el token CSRF de Laravel (cookie XSRF-TOKEN), para acciones sin recargar la página. */
export async function sendJson<T = unknown>(
    method: 'GET' | 'POST' | 'PUT' | 'PATCH' | 'DELETE',
    url: string,
    body?: unknown,
): Promise<T> {
    const xsrf = document.cookie
        .split('; ')
        .find((c) => c.startsWith('XSRF-TOKEN='))
        ?.split('=')[1];

    const response = await fetch(url, {
        method,
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            ...(xsrf ? { 'X-XSRF-TOKEN': decodeURIComponent(xsrf) } : {}),
        },
        body: body === undefined ? undefined : JSON.stringify(body),
    });

    if (!response.ok) {
        throw new HttpError(response.status, await response.json().catch(() => null));
    }

    return (await response.json()) as T;
}
