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
        if (!window.confirm('Revoke this invitation? The link will stop working.')) return;
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
    <AdminHeader pageKey="invite-codes" subtitle="Review and revoke registration invitations created from Users." />
    <section class="admin-detail">
        <p class="p-page-header__context">Invitation history</p>
        <p>Invitation codes are generated from the Users page. Raw codes are only shown once at creation.</p>
        {#if error}<p class="error-message" role="alert">{error}</p>{/if}
        {#if invites.length === 0}<div class="p-card admin-mvp-empty">
                <h2>No invitations yet</h2>
                <p>Create a pending user on Users, then generate an invitation for them.</p>
                <a class="primary-button admin-mvp-link-button" href="/admin/users">Go to Users</a>
            </div>{:else}<div class="p-table-wrap admin-mvp-table">
                <table class="p-table">
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
            </div>{/if}
    </section>
</div>
