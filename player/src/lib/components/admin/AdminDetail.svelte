<script lang="ts">
    import { page } from '$app/state';
    import { useAdminDatabase } from '#lib/admin/context.js';
    import { detailFor, type DetailKey } from '#lib/admin/details.js';
    import { adminPageMeta } from '#lib/adminPageMeta.js';
    import AdminHeader from './AdminHeader.svelte';
    import DetailPanel from './DetailPanel.svelte';
    import DefinitionList from './DefinitionList.svelte';
    import EntityCell from './EntityCell.svelte';
    import ContentState from '../shared/ContentState.svelte';
    import UserEditor from './UserEditor.svelte';
    let { entity }: { entity: DetailKey } = $props();
    const database = useAdminDatabase();
    const id = $derived(Number(page.params.id));
    const detail = $derived(detailFor(entity, database(), id));
    const label = $derived(adminPageMeta[entity].title);
    const recordLabel = $derived(
        {
            users: 'user',
            campaigns: 'campaign',
            sessions: 'session',
            polls: 'poll',
            'one-shots': 'one-shot',
            payments: 'payment request'
        }[entity]
    );
</script>

<div class="admin-shell">
    <AdminHeader
        pageKey={entity}
        title={detail?.title ?? 'Record not found'}
        subtitle={`Review the details for this ${recordLabel} record. Record ID: ${page.params.id}.`}
    />
    <a class="admin-detail__back" href={`/admin/${entity}`}>← {label}</a>
    {#if entity === 'users' && detail}<UserEditor userId={id} />{/if}
    {#if detail}<div class="admin-detail__grid">
            {#each detail.panels as panel (panel.eyebrow)}
                <DetailPanel eyebrow={panel.eyebrow} title={panel.title}>
                    {#if panel.fields}<DefinitionList fields={panel.fields}
                            >{#snippet value(cell)}<EntityCell {cell} />{/snippet}</DefinitionList
                        >{/if}
                    {#if panel.rows}<div class="detail-panel__list">
                            {#each panel.rows as row (row.id)}<div class="detail-panel__row">
                                    <EntityCell cell={row.primary} />{#if row.secondary}<EntityCell
                                            cell={row.secondary}
                                        />{/if}
                                </div>{:else}<ContentState title={panel.empty ?? 'No records available.'} />{/each}
                        </div>{/if}
                </DetailPanel>
            {/each}
        </div>{:else}<ContentState
            title="Record not found"
            description={`No ${label.toLowerCase()} record exists with ID ${page.params.id}.`}
        />{/if}
</div>
