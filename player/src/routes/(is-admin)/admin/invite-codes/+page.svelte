<script lang="ts">
    import AdminHeader from '#lib/components/admin/AdminHeader.svelte';
    import { api, ApiRequestError } from '#lib/api/index.js';
    import CopyButton from '#lib/components/shared/CopyButton.svelte';
    let email = $state('');
    let code = $state('');
    let inviteId = $state<number | null>(null);
    let error = $state('');
    let busy = $state(false);
    async function generate() {
        if (busy) return;
        busy = true;
        error = '';
        try {
            const result = await api.post<{ code: string; id: number }>('/api/v1/admin/invites', { email });
            code = result.code;
            inviteId = result.id;
        } catch (e) {
            error = e instanceof ApiRequestError ? e.message : 'Unable to issue invite.';
        } finally {
            busy = false;
        }
    }
    async function revoke() {
        if (!inviteId) return;
        try {
            await api.post('/api/v1/admin/invites/revoke', { id: inviteId });
            code = '';
            inviteId = null;
        } catch (e) {
            error = e instanceof ApiRequestError ? e.message : 'Unable to revoke invite.';
        }
    }
</script>

<div class="admin-shell">
    <AdminHeader pageKey="invite-codes" />
    <section class="admin-detail">
        <h2>Registration invite</h2>
        <form
            onsubmit={(event) => {
                event.preventDefault();
                void generate();
            }}
        >
            <label for="invite-email">Email</label><input
                id="invite-email"
                type="email"
                bind:value={email}
                required
            /><button class="primary-button" type="submit" disabled={busy || !email}>Generate invite</button>
        </form>
        {#if code}<p role="status">Code: <strong>{code}</strong></p>
            <CopyButton value={code} label="Copy invite code" /><button
                class="secondary-button"
                type="button"
                onclick={revoke}>Revoke</button
            >{/if}{#if error}<p class="error-message" role="alert">{error}</p>{/if}
    </section>
</div>
