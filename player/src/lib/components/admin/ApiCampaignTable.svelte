<script lang="ts">
    import { goto } from '$app/navigation';
    import AdminHeader from './AdminHeader.svelte';
    import DataTable from './DataTable.svelte';
    import EntityCell from './EntityCell.svelte';
    import type { Cell, Column, EntityRow } from '#lib/admin/types.js';
    import { api, ApiRequestError } from '#lib/api/index.js';
    let { type }: { type: 'campaign' | 'one_shot' } = $props();
    type Campaign = {
        id: number;
        name: string;
        slug: string;
        type: string;
        location: string | null;
        timezone: string;
        archived_at: string | null;
        version: number;
    };
    let rows = $state<Campaign[]>([]);
    let error = $state('');
    let busy = $state(false);
    let name = $state('');
    let slug = $state('');
    let slugTouched = $state(false);
    let fieldError = $state('');
    function updateName(event: Event) {
        name = (event.currentTarget as HTMLInputElement).value;
        if (!slugTouched) slug = slugify(name);
    }
    function slugify(value: string) {
        return value
            .trim()
            .toLowerCase()
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-+|-+$/g, '')
            .slice(0, 120);
    }
    function updateSlug(event: Event) {
        slug = (event.currentTarget as HTMLInputElement).value;
        slugTouched = true;
    }
    const columns: Column[] = [
        { key: 'id', label: 'ID', sortType: 'number' },
        { key: 'name', label: 'Name' },
        { key: 'slug', label: 'Slug' },
        { key: 'location', label: 'Location' },
        { key: 'status', label: 'Status' }
    ];
    function entityRows(): EntityRow[] {
        return rows
            .filter((item) => item.type === type)
            .map((item) => ({
                id: String(item.id),
                label: item.name,
                cells: {
                    id: { text: String(item.id), value: item.id },
                    name: {
                        text: item.name,
                        value: item.name,
                        href: `/admin/${type === 'campaign' ? 'campaigns' : 'one-shots'}/${item.id}`
                    },
                    slug: { text: item.slug, value: item.slug },
                    location: { text: item.location ?? '—', value: item.location },
                    status: { text: item.archived_at ? 'Archived' : 'Active', value: item.archived_at ?? '' }
                },
                actions: [
                    {
                        label: 'View details',
                        href: `/admin/${type === 'campaign' ? 'campaigns' : 'one-shots'}/${item.id}`
                    }
                ]
            }));
    }
    async function load() {
        try {
            rows = await api.get<Campaign[]>('/api/v1/campaigns');
        } catch (e) {
            error = e instanceof ApiRequestError ? e.message : 'Unable to load campaigns.';
        }
    }
    async function create() {
        busy = true;
        error = '';
        fieldError = '';
        if (!name.trim() || !slug.trim()) {
            fieldError = 'Enter a campaign name and slug.';
            busy = false;
            return;
        }
        try {
            await api.post('/api/v1/campaigns', { name, slug, type });
            name = '';
            slug = '';
            await load();
        } catch (e) {
            error = e instanceof ApiRequestError ? e.message : 'Unable to create campaign.';
        } finally {
            busy = false;
        }
    }
    $effect(() => {
        void load();
    });
</script>

<AdminHeader pageKey={type === 'campaign' ? 'campaigns' : 'one-shots'} />
<section class="admin-detail">
    <h2>Create {type === 'campaign' ? 'campaign' : 'one-shot'}</h2>
    <form
        class="admin-form"
        onsubmit={(event) => {
            event.preventDefault();
            void create();
        }}
    >
        <div class="admin-form__field">
            <label for="campaign-name">Name</label>
            <input id="campaign-name" bind:value={name} oninput={updateName} autocomplete="off" required />
            <small>Use a name your players will recognize.</small>
        </div>
        <div class="admin-form__field">
            <label for="campaign-slug">Slug</label>
            <input id="campaign-slug" bind:value={slug} oninput={updateSlug} autocomplete="off" required />
            <small>Lowercase letters, numbers and hyphens.</small>
        </div>
        {#if fieldError}<p class="field-error" role="alert">{fieldError}</p>{/if}
        {#if error}<p class="error-message" role="alert">{error}</p>{/if}
        <button class="primary-button" type="submit" disabled={busy || !name.trim() || !slug.trim()}>
            {busy ? 'Creating…' : 'Create campaign'}
        </button>
    </form>
</section>
{#snippet cell(row: EntityRow, key: string)}<EntityCell cell={row.cells[key]} />{/snippet}
{#snippet actions(row: EntityRow)}<a class="secondary-button" href={row.actions[0]?.href}>View details</a>{/snippet}
<DataTable
    rows={entityRows()}
    {columns}
    rowKey={(row) => row.id}
    rowLabel={(row) => row.label}
    {cell}
    {actions}
    selectable
    caption={type === 'campaign' ? 'Campaigns' : 'One-shots'}
/>
