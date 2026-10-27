<script lang="ts">
    import { goto } from '$app/navigation';
    import AdminHeader from './AdminHeader.svelte';
    import { api, ApiRequestError } from '#lib/api/index.js';
    let { id, type }: { id: number; type: 'campaign' | 'one_shot' } = $props();
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
    let item = $state<Campaign | null>(null);
    let error = $state('');
    let editing = $state(false);
    let busy = $state(false);
    let draft = $state<Partial<Campaign>>({});
    async function load() {
        try {
            item = await api.get<Campaign>(`/api/v1/campaigns/${id}`);
            draft = { ...item };
        } catch (e) {
            error = e instanceof ApiRequestError ? e.message : 'Unable to load campaign.';
        }
    }
    async function save() {
        if (!item) return;
        busy = true;
        error = '';
        try {
            item = await api.patch<Campaign>(`/api/v1/campaigns/${id}`, {
                name: draft.name,
                slug: draft.slug,
                location: draft.location,
                timezone: draft.timezone,
                archived_at: draft.archived_at,
                version: item.version
            });
            editing = false;
        } catch (e) {
            error = e instanceof ApiRequestError ? `${e.message} (${e.code})` : 'Unable to save campaign.';
            await load();
        } finally {
            busy = false;
        }
    }
    async function archive(value: boolean) {
        if (!item) return;
        draft.archived_at = value ? new Date().toISOString() : null;
        await save();
    }
    $effect(() => {
        void load();
    });
</script>

<AdminHeader
    pageKey={type === 'campaign' ? 'campaigns' : 'one-shots'}
    title={item?.name ?? 'Campaign'}
    subtitle={`Record ID: ${id}`}
/>
<a class="admin-detail__back" href={`/admin/${type === 'campaign' ? 'campaigns' : 'one-shots'}`}>← Back</a>
{#if item}<section class="admin-detail">
        <dl class="definition-list">
            <dt>ID</dt>
            <dd>{item.id}</dd>
            <dt>Type</dt>
            <dd>{item.type}</dd>
            <dt>Status</dt>
            <dd>{item.archived_at ? 'Archived' : 'Active'}</dd>
        </dl>
        {#if editing}<label for="detail-name">Name</label><input id="detail-name" bind:value={draft.name} /><label
                for="detail-slug">Slug</label
            ><input id="detail-slug" bind:value={draft.slug} /><label for="detail-location">Location</label><input
                id="detail-location"
                bind:value={draft.location}
            /><button class="primary-button" disabled={busy} onclick={save}>Save changes</button><button
                class="secondary-button"
                onclick={() => {
                    editing = false;
                    draft = { ...item };
                }}>Cancel</button
            >{:else}<button class="primary-button" onclick={() => (editing = true)}>Edit campaign</button
            >{#if item.archived_at}<button class="secondary-button" onclick={() => archive(false)}>Restore</button
                >{:else}<button class="secondary-button" onclick={() => archive(true)}>Archive</button
                >{/if}{/if}{#if error}<p class="error-message" role="alert">{error}</p>{/if}
    </section>{:else}<p role="status">Loading…</p>{/if}
