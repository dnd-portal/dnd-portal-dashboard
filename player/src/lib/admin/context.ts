import { getContext, setContext } from 'svelte';
import type { AdminData } from './types.js';

const key = Symbol('admin-database');
export function provideAdminDatabase(database: () => AdminData) {
    setContext(key, database);
}
export function useAdminDatabase(): () => AdminData {
    const database = getContext<() => AdminData>(key);
    if (!database) throw new Error('Admin components must render inside the admin layout.');
    return database;
}
