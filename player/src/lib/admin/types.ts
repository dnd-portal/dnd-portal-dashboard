import type { getDatabase } from '#lib/database/index.js';

export type Database = Awaited<ReturnType<typeof getDatabase>>;
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
