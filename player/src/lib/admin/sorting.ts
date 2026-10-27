import type { Column, EntityRow } from './types.js';

export type SortDirection = 'ascending' | 'descending';
export type SortState = { key: string; direction: SortDirection } | null;
type SortType = NonNullable<Column<EntityRow>['sortType']>;

const collator = new Intl.Collator(undefined, { sensitivity: 'base', numeric: true });

function rawValue(row: EntityRow, column: Column<EntityRow>) {
    return column.sortValue ? column.sortValue(row) : (row.cells[column.key]?.value ?? row.cells[column.key]?.text);
}

function inferType(values: unknown[], explicit?: SortType): SortType {
    if (explicit && explicit !== 'auto') return explicit;
    const value = values.find((item) => item !== null && item !== undefined && item !== '');
    if (typeof value === 'number') return 'number';
    if (typeof value === 'boolean') return 'boolean';
    if (value instanceof Date) return 'date';
    return 'text';
}

function comparable(value: unknown, type: SortType): number | string | boolean | null {
    if (value === null || value === undefined || value === '') return null;
    if (type === 'number') {
        const number = typeof value === 'number' ? value : Number(value);
        return Number.isFinite(number) ? number : null;
    }
    if (type === 'date') {
        const time = value instanceof Date ? value.getTime() : Date.parse(String(value));
        return Number.isNaN(time) ? null : time;
    }
    if (type === 'boolean') return typeof value === 'boolean' ? value : null;
    return String(value);
}

function compareValues(left: unknown, right: unknown, type: SortType) {
    const a = comparable(left, type);
    const b = comparable(right, type);
    if (a === null && b === null) return 0;
    if (a === null) return 1;
    if (b === null) return -1;
    if (type === 'text') return collator.compare(String(a), String(b));
    if (a === b) return 0;
    return a < b ? -1 : 1;
}

export function defaultSort(columns: Column<EntityRow>[]): SortState {
    const id = columns.find((column) => column.key === 'id' && column.sortable !== false);
    return id ? { key: id.key, direction: 'ascending' } : null;
}

export function sortableColumns(rows: EntityRow[], columns: Column<EntityRow>[]) {
    return columns.map((column) => ({
        column,
        sortable:
            column.sortable !== false &&
            (column.sortValue !== undefined ||
                rows.some((row) => row.cells[column.key]?.value !== undefined || row.cells[column.key]?.text))
    }));
}

export function sortRows(rows: EntityRow[], columns: Column<EntityRow>[], state: SortState) {
    if (!state) return rows.slice();
    const column = columns.find((item) => item.key === state.key);
    if (!column || column.sortable === false) return rows.slice();
    const values = rows.map((row) => rawValue(row, column));
    const type = inferType(values, column.sortType);
    const direction = state.direction === 'ascending' ? 1 : -1;
    return rows
        .map((row, index) => ({ row, index, value: rawValue(row, column) }))
        .sort((left, right) => {
            const leftValue = comparable(left.value, type);
            const rightValue = comparable(right.value, type);
            if (leftValue === null || rightValue === null) {
                return (leftValue === null ? 1 : -1) || left.index - right.index;
            }
            return compareValues(left.value, right.value, type) * direction || left.index - right.index;
        })
        .map(({ row }) => row);
}
