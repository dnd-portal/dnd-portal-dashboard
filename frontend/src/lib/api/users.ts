import { apiGet, ApiError } from './client';
import { raiseApiError } from './errors';
import type { Database } from '../types/database';

export async function getCurrentUser(
    fetcher: typeof globalThis.fetch
): Promise<Database.User | null> {
    const result = await apiGet<Database.User | null>('me', {
        fetch: fetcher,
        mockPath: '/mock/current-user.json'
    });

    // The /me endpoint should return a user or JSON null.
    if (result.status === 204) {
        throw new ApiError(502, 'me');
    }

    return result.data;
}

export function createApi(fetcher: typeof globalThis.fetch) {
    let userRequest: Promise<Database.User | null> | undefined;

    function currentUser(): Promise<Database.User | null> {
        return (userRequest ??= getCurrentUser(fetcher));
    }

    return {
        async requirePermission(
            permission: Database.Permission
        ): Promise<void> {
            let user: Database.User | null;

            try {
                user = await currentUser();
            } catch (cause) {
                if (cause instanceof ApiError) {
                    raiseApiError(cause.status);
                }

                throw cause;
            }

            if (!user) {
                raiseApiError(401);
            }

            if (user.permissions?.[permission] !== true) {
                if (import.meta.env.DEV) {
                    console.warn('[Permissions] Access denied', {
                        permission,
                        received: user.permissions?.[permission]
                    });
                }

                raiseApiError(403);
            }
        }
    };
}