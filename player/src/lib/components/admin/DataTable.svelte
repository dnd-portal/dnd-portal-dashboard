<script lang="ts" generics="Row">
    import type { Snippet } from 'svelte';
    import type { Column, EntityRow } from '#lib/admin/types.js';
    import { defaultSort, sortRows, sortableColumns, type SortState } from '#lib/admin/sorting.js';
    import { selectionState, toggleVisible } from '#lib/admin/selection.js';
    import ContentState from '../shared/ContentState.svelte';
    let {
        rows,
        columns,
        rowKey,
        rowLabel,
        cell,
        actions,
        toolbar,
        selected = $bindable([]),
        selectable = false,
        caption,
        emptyMessage = 'No records match the current search and filters.'
    }: {
        rows: Row[];
        columns: Column[];
        rowKey: (row: Row) => string;
        rowLabel: (row: Row) => string;
        cell: Snippet<[Row, string]>;
        actions?: Snippet<[Row]>;
        toolbar?: Snippet;
        selected?: string[];
        selectable?: boolean;
        caption: string;
        emptyMessage?: string;
    } = $props();
    const ids = $derived(rows.map(rowKey));
    const selection = $derived(selectionState(ids, selected));
    let sortState = $state<SortState>(null);
    const entityColumns = $derived(columns as Column<EntityRow>[]);
    const sortable = $derived(sortableColumns(rows as EntityRow[], entityColumns));
    const activeSort = $derived.by(() => {
        const candidate = sortState;
        return candidate && sortable.some((item) => item.sortable && item.column.key === candidate.key)
            ? candidate
            : defaultSort(entityColumns);
    });
    const sortedRows = $derived(sortRows(rows as EntityRow[], entityColumns, activeSort) as Row[]);
    function sortBy(column: Column<EntityRow>) {
        if (column.sortable === false) return;
        sortState =
            activeSort?.key === column.key
                ? { key: column.key, direction: activeSort.direction === 'ascending' ? 'descending' : 'ascending' }
                : { key: column.key, direction: 'ascending' };
    }
    function toggle(id: string) {
        selected = selected.includes(id) ? selected.filter((value) => value !== id) : [...selected, id];
    }
</script>

<section class="admin-table" aria-label={caption}>
    {#if toolbar}<div class="admin-table__toolbar">{@render toolbar()}</div>{/if}
    {#if selectable && selected.length}<div class="admin-table__selection" role="status">
            <strong>{selected.length} selected</strong><button type="button" onclick={() => (selected = [])}
                >Clear selection</button
            >
        </div>{/if}
    <!-- svelte-ignore a11y_no_noninteractive_tabindex (The overflow region needs keyboard focus for horizontal scrolling.) -->
    <div class="admin-table__scroll" tabindex="0" role="region" aria-label={`${caption} table`}>
        <table class="admin-table__table">
            <caption class="sr-only">{caption}</caption>
            <thead
                ><tr>
                    {#if selectable}<th class="admin-table__select"
                            ><input
                                type="checkbox"
                                aria-label="Select all visible rows"
                                checked={selection.all}
                                indeterminate={selection.some}
                                onchange={() => (selected = toggleVisible(ids, selected))}
                            /></th
                        >{/if}
                    {#each sortable as item (item.column.key)}<th
                            scope="col"
                            aria-sort={activeSort?.key === item.column.key ? activeSort.direction : undefined}
                            >{#if item.sortable}<button
                                    class="admin-table__sort"
                                    type="button"
                                    aria-label={`Sort ${item.column.label} ${activeSort?.key === item.column.key && activeSort.direction === 'ascending' ? 'descending' : 'ascending'}`}
                                    onclick={() => sortBy(item.column)}
                                    ><span>{item.column.label}</span><span
                                        class:admin-table__sort-icon--active={activeSort?.key === item.column.key}
                                        class:admin-table__sort-icon--descending={activeSort?.key === item.column.key &&
                                            activeSort.direction === 'descending'}
                                        class="admin-table__sort-icon"
                                        aria-hidden="true"
                                    ></span></button
                                >{:else}{item.column.label}{/if}</th
                        >{/each}
                    {#if actions}<th class="admin-table__actions"><span class="sr-only">Actions</span></th>{/if}
                </tr></thead
            >
            <tbody
                >{#each sortedRows as row (rowKey(row))}<tr
                        class:admin-table__row--selected={selected.includes(rowKey(row))}
                    >
                        {#if selectable}<td class="admin-table__select"
                                ><input
                                    type="checkbox"
                                    aria-label={`Select ${rowLabel(row)}`}
                                    checked={selected.includes(rowKey(row))}
                                    onchange={() => toggle(rowKey(row))}
                                /></td
                            >{/if}
                        {#each columns as column (column.key)}<td>{@render cell(row, column.key)}</td>{/each}
                        {#if actions}<td class="admin-table__actions">{@render actions(row)}</td>{/if}
                    </tr>{:else}<tr
                        ><td colspan={columns.length + Number(selectable) + Number(Boolean(actions))}
                            ><ContentState title={emptyMessage} /></td
                        ></tr
                    >{/each}</tbody
            >
        </table>
    </div>
</section>
