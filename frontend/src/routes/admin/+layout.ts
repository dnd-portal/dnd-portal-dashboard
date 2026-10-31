import { createApi } from '../../lib/api/users';
import type { LayoutLoad } from './$types';

export const load: LayoutLoad = async ({ fetch }) => {
    const api = createApi(fetch);

    await api.requirePermission('adminCanVisitAdmin');

    return {};
};