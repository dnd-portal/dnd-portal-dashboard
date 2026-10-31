import type { LayoutLoad } from './$types';

export const load: LayoutLoad = async ({ fetch }) => {
    const response = await fetch('/mock/current-user.json');

    if (!response.ok) {
        throw new Error('Failed to load the current user.');
    }

    return {
        user: await response.json()
    };
};