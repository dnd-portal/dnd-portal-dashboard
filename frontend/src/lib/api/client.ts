type ApiGetOptions = {
    fetch: typeof globalThis.fetch;
    mockPath: string;
};

export type ApiResult<T> = {
    status: number;
    data: T | null;
};

export class ApiError extends Error {
    constructor(
        public readonly status: number,
        public readonly endpoint: string
    ) {
        super(`API request failed: ${status} (${endpoint})`);
        this.name = 'ApiError';
    }
}

export async function apiGet<T>(
    endpoint: string,
    options: ApiGetOptions
): Promise<ApiResult<T>> {
    const useMock = import.meta.env.VITE_USE_MOCK_API === 'true';
    const baseUrl = import.meta.env.VITE_API_URL?.replace(/\/+$/, '');

    if (!useMock && !baseUrl) {
        throw new Error('VITE_API_URL is missing.');
    }

    const url = useMock
        ? options.mockPath
        : `${baseUrl}/${endpoint.replace(/^\/+/, '')}`;

    let response: Response;

    try {
        response = await options.fetch(url, {
            headers: {
                Accept: 'application/json'
            },
            cache: useMock ? 'no-store' : 'default'
        });
    } catch {
        // Network failures do not have an HTTP response status.
        // We represent an unreachable API as service unavailable.
        throw new ApiError(503, endpoint);
    }

    if (!response.ok) {
        throw new ApiError(response.status, endpoint);
    }

    if (response.status === 204) {
        return {
            status: response.status,
            data: null
        };
    }

    let data: T;

    try {
        data = await response.json();
    } catch {
        // This caller expects JSON, except for a 204 response.
        throw new ApiError(502, endpoint);
    }

    return {
        status: response.status,
        data
    };
}