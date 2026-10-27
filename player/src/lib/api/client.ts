export type ApiError = { code: string; message: string; details?: Record<string, unknown> };
export type ApiResult<T> = { data: T };

export class ApiRequestError extends Error {
    code: string;
    status: number;
    details: Record<string, unknown>;
    constructor(error: ApiError, status: number) {
        super(error.message);
        this.name = 'ApiRequestError';
        this.code = error.code;
        this.status = status;
        this.details = error.details ?? {};
    }
}

let csrfToken: string | null = null;

async function request<T>(path: string, init: RequestInit = {}): Promise<T> {
    const method = (init.method ?? 'GET').toUpperCase();
    const headers = new Headers(init.headers);
    headers.set('Accept', 'application/json');
    if (init.body && !headers.has('Content-Type')) headers.set('Content-Type', 'application/json');
    if (!['GET', 'HEAD', 'OPTIONS'].includes(method)) {
        if (!csrfToken) csrfToken = await fetchCsrfToken();
        headers.set('X-CSRF-Token', csrfToken);
    }
    const response = await fetch(path, { ...init, headers, credentials: 'include', cache: 'no-store' });
    const payload = (await response.json().catch(() => null)) as ApiResult<T> | { error?: ApiError } | null;
    if (!response.ok || !payload || !('data' in payload)) {
        const error =
            payload && 'error' in payload && payload.error
                ? payload.error
                : { code: 'invalid_response', message: 'The API returned an invalid response.' };
        if (response.status === 419) csrfToken = null;
        throw new ApiRequestError(error, response.status);
    }
    return payload.data;
}

async function fetchCsrfToken(): Promise<string> {
    const response = await fetch('/api/v1/auth/csrf', { credentials: 'include', cache: 'no-store' });
    const payload = (await response.json()) as ApiResult<{ token: string }> | { error?: ApiError };
    if (!response.ok || !payload || !('data' in payload)) {
        const error =
            payload && 'error' in payload && payload.error
                ? payload.error
                : { code: 'csrf_unavailable', message: 'CSRF token unavailable.' };
        throw new ApiRequestError(error, response.status);
    }
    return payload.data.token;
}

export const api = {
    get: <T>(path: string) => request<T>(path),
    post: <T>(path: string, body: unknown) => request<T>(path, { method: 'POST', body: JSON.stringify(body) }),
    put: <T>(path: string, body: unknown) => request<T>(path, { method: 'PUT', body: JSON.stringify(body) }),
    patch: <T>(path: string, body: unknown) => request<T>(path, { method: 'PATCH', body: JSON.stringify(body) }),
    delete: <T>(path: string) => request<T>(path, { method: 'DELETE' }),
    invalidateCsrf: () => {
        csrfToken = null;
    }
};
