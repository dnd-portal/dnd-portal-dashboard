import { getContext, setContext } from 'svelte';
import type { Database } from './types.js';

const key = Symbol('admin-database');
export function provideAdminDatabase(database: () => Database) {
    setContext(key, database);
}
export function useAdminDatabase(): () => Database {
    const database = getContext<() => Database>(key);
    if (!database) throw new Error('Admin components must render inside the admin layout.');
    return database;
}
