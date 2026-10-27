import { getAdminData } from '#lib/admin/loadAdminData.js';
import type { LayoutLoad } from './$types';

export const load: LayoutLoad = async ({ fetch }) => {
    return { database: await getAdminData(fetch) };
};
