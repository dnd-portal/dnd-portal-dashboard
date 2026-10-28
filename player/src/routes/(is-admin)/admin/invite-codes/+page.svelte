<script lang="ts">
    import { onMount } from 'svelte';
    import AdminHeader from '#lib/components/admin/AdminHeader.svelte';
    import { api, ApiRequestError } from '#lib/api/index.js';
    type Invite = {
        id: number;
        invited_user_id: number | null;
        invited_email: string | null;
        display_name: string | null;
        username: string | null;
        expires_at: string;
        consumed_at: string | null;
        revoked_at: string | null;
    };
    let invites = $state<Invite[]>([]);
    let error = $state('');
    async function load() {
        try {
            invites = await api.get<Invite[]>('/api/v1/admin/invites');
        } catch (e) {
            error = e instanceof ApiRequestError ? e.message : 'Unable to load invitations.';
        }
    }
    async function revoke(id: number) {
        try {
            await api.post('/api/v1/admin/invites/revoke', { id });
            await load();
        } catch (e) {
            error = e instanceof ApiRequestError ? e.message : 'Unable to revoke invitation.';
        }
    }
    const status = (i: Invite) =>
        i.revoked_at ? 'Revoked' : i.consumed_at ? 'Used' : new Date(i.expires_at) < new Date() ? 'Expired' : 'Valid';
    onMount(() => void load());
</script>

<div class="admin-shell">
    <AdminHeader pageKey="invite-codes" />
    <section class="admin-detail">
        <h1>Invitations</h1>
        <p>Invitation codes are generated from the Users page. Raw codes are only shown once at creation.</p>
        {#if error}<p class="error-message" role="alert">{error}</p>{/if}
        <div class="p-table-wrap">
            <table>
                <caption>Invitation history</caption><thead
                    ><tr><th>User</th><th>Status</th><th>Expires</th><th>Actions</th></tr></thead
                ><tbody
                    >{#each invites as invite}<tr
                            ><td>{invite.display_name ?? invite.invited_email ?? 'Blank Type B invitation'}</td><td
                                >{status(invite)}</td
                            ><td>{invite.expires_at}</td><td
                                >{#if status(invite) === 'Valid'}<button
                                        class="secondary-button"
                                        onclick={() => void revoke(invite.id)}>Revoke</button
                                    >{/if}</td
                            ></tr
                        >{/each}</tbody
                >
            </table>
        </div>
    </section>
</div>
