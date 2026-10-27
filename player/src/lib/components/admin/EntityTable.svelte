<script lang="ts">
    import { page } from '$app/state';
    import { goto } from '$app/navigation';
    import { useAdminDatabase } from '#lib/admin/context.js';
    import { entities, entityFilters, filterRows, type EntityKey } from '#lib/admin/entities.js';
    import { adminPageMeta } from '#lib/adminPageMeta.js';
    import DataTable from './DataTable.svelte';
    import TableToolbar from './TableToolbar.svelte';
    import EntityCell from './EntityCell.svelte';
    import RowActions from '../shared/RowActions.svelte';
    let { entity }: { entity: EntityKey } = $props();
    const database = useAdminDatabase();
    let selected = $state<string[]>([]);
    const definition = $derived(entities[entity]);
    const rows = $derived(definition.rows(database()));
    const filters = $derived(entityFilters(definition, rows));
    const currentUrl = $derived(page.shallow?.url ?? page.url);
    const query = $derived(currentUrl.searchParams.get('q') ?? '');
    const values = $derived(
        Object.fromEntries(filters.map((filter) => [filter.key, currentUrl.searchParams.get(filter.key) ?? 'all']))
    );
    const filtered = $derived(filterRows(rows, query, values));
    const hasActions = $derived(rows.some((row) => row.actions.length > 0));
    function update(next: Record<string, string>) {
        const url = new URL(currentUrl.href);
        for (const [key, value] of Object.entries(next)) {
            if (!value || value === 'all') url.searchParams.delete(key);
            else url.searchParams.set(key, value);
        }
        void goto(url, { state: page.state, shallow: true, replace: true });
    }
    // A route transition must not carry IDs selected in another entity table.
    $effect(() => {
        entity;
        selected = [];
    });
</script>

{#snippet toolbar()}<TableToolbar
        label={adminPageMeta[entity].title}
        {query}
        {filters}
        {values}
        count={filtered.length}
        total={rows.length}
        onchange={update}
    />{/snippet}
{#snippet cell(row: import('#lib/admin/types.js').EntityRow, key: string)}<EntityCell cell={row.cells[key]} />{/snippet}
{#snippet actions(row: import('#lib/admin/types.js').EntityRow)}<RowActions
        label={row.label}
        actions={row.actions}
    />{/snippet}
<DataTable
    rows={filtered}
    columns={definition.columns}
    rowKey={(row) => row.id}
    rowLabel={(row) => row.label}
    {cell}
    {toolbar}
    actions={hasActions ? actions : undefined}
    selectable
    bind:selected
    caption={adminPageMeta[entity].title}
/>
