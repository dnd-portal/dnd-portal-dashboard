import type { getAdminData } from '#lib/admin/loadAdminData.js';

export type AdminData = Awaited<ReturnType<typeof getAdminData>>;
/** @deprecated Use AdminData in new code. */
export type Database = AdminData;
import type { Action, Tone } from '../ui/types.js';
export type { Action, Tone } from '../ui/types.js';
export type Cell = {
    text: string;
    value?: unknown;
    href?: string;
    kind?: 'text' | 'user' | 'status' | 'responses';
    tone?: Tone;
    names?: string[];
};
export type EntityRow = { id: string; label: string; cells: Record<string, Cell>; actions: Action[] };
export type Column<Row = EntityRow> = {
    key: string;
    label: string;
    sortable?: boolean;
    sortType?: 'auto' | 'text' | 'number' | 'date' | 'boolean';
    sortValue?: (row: Row) => unknown;
};
export type Filter = { key: string; label: string; options: { value: string; label: string }[] };
