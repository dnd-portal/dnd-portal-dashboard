import { getAdminData } from '#lib/admin/loadAdminData.js';
import { AdminDataRequestError } from '#lib/admin/loadAdminData.js';
import { redirect } from '@sveltejs/kit';
import type { LayoutLoad } from './$types';

export const load: LayoutLoad = async ({ fetch, url }) => {
    try {
        return { database: await getAdminData(fetch) };
    } catch (cause) {
        if (cause instanceof AdminDataRequestError && cause.status === 401) {
            const returnTo = `${url.pathname}${url.search}`;
            redirect(303, `/login?returnTo=${encodeURIComponent(returnTo)}`);
        }
        throw cause;
    }
};
