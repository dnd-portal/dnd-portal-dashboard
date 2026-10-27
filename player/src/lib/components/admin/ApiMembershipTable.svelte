<script lang="ts">
    import DataTable from './DataTable.svelte';
    import EntityCell from './EntityCell.svelte';
    import AdminHeader from './AdminHeader.svelte';
    import type { Column, EntityRow } from '#lib/admin/types.js';
    import { api, ApiRequestError } from '#lib/api/index.js';
    type Member = {
        campaign_id: number;
        user_id: number;
        membership_role: 'member' | 'dm';
        left_at: string | null;
        display_name: string;
        email: string | null;
        username: string | null;
    };
    let rows = $state<Member[]>([]);
    let campaignId = $state('');
    let userId = $state('');
    let role = $state<'member' | 'dm'>('member');
    let error = $state('');
    let busy = $state(false);
    const columns: Column[] = [
        { key: 'campaign', label: 'Campaign', sortType: 'number' },
        { key: 'user', label: 'User' },
        { key: 'role', label: 'Role' },
        { key: 'status', label: 'Status' }
    ];
    const view = () =>
        rows.map((m): EntityRow => ({
            id: `${m.campaign_id}-${m.user_id}`,
            label: m.display_name,
            cells: {
                campaign: { text: String(m.campaign_id), value: m.campaign_id },
                user: { text: m.display_name, value: m.display_name },
                role: { text: m.membership_role, value: m.membership_role },
                status: { text: m.left_at ? 'Ended' : 'Active', value: m.left_at ?? '' }
            },
            actions: []
        }));
    async function load() {
        if (!campaignId) return;
        try {
            rows = await api.get<Member[]>(`/api/v1/campaigns/${campaignId}/members`);
            error = '';
        } catch (e) {
            error = e instanceof ApiRequestError ? e.message : 'Unable to load memberships.';
        }
    }
    async function add() {
        busy = true;
        try {
            await api.post(`/api/v1/campaigns/${campaignId}/members`, {
                user_id: Number(userId),
                membership_role: role
            });
            await load();
        } catch (e) {
            error = e instanceof ApiRequestError ? e.message : 'Unable to save membership.';
        } finally {
            busy = false;
        }
    }
    async function end(member: Member) {
        try {
            await api.post(`/api/v1/campaigns/${member.campaign_id}/members/${member.user_id}/leave`, {});
            await load();
        } catch (e) {
            error = e instanceof ApiRequestError ? e.message : 'Unable to end membership.';
        }
    }
</script>

<AdminHeader pageKey="memberships" />
<section class="admin-detail">
    <h2>Manage campaign members</h2>
    <div class="form-grid">
        <label for="membership-campaign">Campaign ID</label><input
            id="membership-campaign"
            inputmode="numeric"
            bind:value={campaignId}
        /><label for="membership-user">User ID</label><input
            id="membership-user"
            inputmode="numeric"
            bind:value={userId}
        /><label for="membership-role">Role</label><select id="membership-role" bind:value={role}
            ><option value="member">Member</option><option value="dm">Campaign DM</option></select
        >
    </div>
    <button class="primary-button" disabled={busy || !campaignId || !userId} onclick={add}>Add or reactivate</button
    ><button class="secondary-button" disabled={!campaignId} onclick={load}>Load members</button>{#if error}<p
            class="error-message"
            role="alert"
        >
            {error}
        </p>{/if}
</section>
{#snippet cell(row: EntityRow, key: string)}<EntityCell cell={row.cells[key]} />{/snippet}
{#snippet actions(row: EntityRow)}<button
        class="secondary-button"
        onclick={() => {
            const [c, u] = row.id.split('-');
            void end({
                campaign_id: Number(c),
                user_id: Number(u),
                membership_role: 'member',
                left_at: null,
                display_name: '',
                email: null,
                username: null
            });
        }}>End membership</button
    >{/snippet}
<DataTable
    rows={view()}
    {columns}
    rowKey={(row) => row.id}
    rowLabel={(row) => row.label}
    {cell}
    {actions}
    caption="Campaign memberships"
/>
