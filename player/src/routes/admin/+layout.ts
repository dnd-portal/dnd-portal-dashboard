import { getDatabase } from '#lib/database/index.js';
import type { LayoutLoad } from './$types';

export const load: LayoutLoad = async ({ fetch }) => {
    try {
        return { database: await getDatabase(fetch) };
    } catch {
        // Anonymous visitors are rendered by the client-side access check.
        // Keep the protected loader data-free until that check succeeds.
        return {
            database: {
                user: { id: 0, name: '', email: '', username: '', role: '', registered: false },
                users: [],
                campaigns: [],
                oneShots: [],
                memberships: [],
                sessions: [],
                polls: [],
                pollResponses: [],
                paymentRequests: [],
                paymentRequestUsers: [],
                inviteCodes: [],
                organizerDashboard: [],
                activity: [],
                availability: []
            } as Awaited<ReturnType<typeof getDatabase>>
        };
    }
};
