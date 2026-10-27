<script lang="ts">
    import type { Filter } from '#lib/admin/types.js';
    import DropdownSelect from '../shared/DropdownSelect.svelte';
    let {
        label,
        query,
        filters = [],
        values,
        count,
        total,
        onchange
    }: {
        label: string;
        query: string;
        filters?: Filter[];
        values: Record<string, string>;
        count: number;
        total: number;
        onchange: (values: Record<string, string>) => void;
    } = $props();
    const id = $props.id();
    const active = $derived(
        Boolean(query) || filters.some((filter) => values[filter.key] && values[filter.key] !== 'all')
    );
</script>

<div class="table-toolbar">
    <label class="table-toolbar__search" for={`${id}-search`}
        >Search {label.toLowerCase()}<input
            id={`${id}-search`}
            type="search"
            value={query}
            placeholder={`Search ${label.toLowerCase()}…`}
            oninput={(event) => onchange({ q: event.currentTarget.value })}
        /></label
    >
    {#each filters as filter (filter.key)}<div class="table-toolbar__filter">
            <DropdownSelect
                id={`${id}-${filter.key}`}
                label={filter.label}
                options={filter.options}
                value={values[filter.key] || 'all'}
                onchange={(value) => onchange({ [filter.key]: value })}
            />
        </div>{/each}
    {#if active}<button
            class="table-toolbar__clear"
            type="button"
            onclick={() =>
                onchange(Object.fromEntries(['q', ...filters.map((filter) => filter.key)].map((key) => [key, ''])))}
            >Clear filters</button
        >{/if}
    <span class="table-toolbar__count" role="status">{count} of {total}</span>
</div>
