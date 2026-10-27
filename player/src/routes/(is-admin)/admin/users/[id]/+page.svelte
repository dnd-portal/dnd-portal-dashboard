<script lang="ts">
    import { page } from '$app/state';
    import { onMount } from 'svelte';
    import AdminHeader from '#lib/components/admin/AdminHeader.svelte';
    import UserEditor from '#lib/components/admin/UserEditor.svelte';
    import { api, ApiRequestError } from '#lib/api/index.js';
    import CopyButton from '#lib/components/shared/CopyButton.svelte';
    type User = {
        id: number;
        display_name: string;
        email: string | null;
        username: string | null;
        account_status: string;
        version: number;
        roles: string[];
    };
    let user = $state<User | null>(null);
    let error = $state('');
    let resetCode = $state('');
    let resetId = $state<number | null>(null);
    let resetError = $state('');
    const userId = $derived(Number(page.params.id));
    onMount(async () => {
        try {
            user = await api.get<User>(`/api/v1/users/${userId}`);
        } catch (e) {
            error = e instanceof ApiRequestError ? e.message : 'Unable to load user.';
        }
    });
    async function generateReset() {
        resetError = '';
        try {
            const result = await api.post<{ code: string; id: number }>('/api/v1/admin/password-reset-codes', {
                user_id: userId
            });
            resetCode = result.code;
            resetId = result.id;
        } catch (e) {
            resetError = e instanceof ApiRequestError ? e.message : 'Unable to issue reset code.';
        }
    }
    async function revokeReset() {
        if (!resetId) return;
        resetError = '';
        try {
            await api.post('/api/v1/admin/password-reset-codes/revoke', { id: resetId });
            resetCode = '';
            resetId = null;
        } catch (e) {
            resetError = e instanceof ApiRequestError ? e.message : 'Unable to revoke reset code.';
        }
    }
</script>

<div class="admin-shell">
    <AdminHeader pageKey="users" />{#if error}<p class="error-message" role="alert">{error}</p>{:else if user}<section
            class="admin-detail"
        >
            <h2>{user.display_name}</h2>
            <p>{user.email ?? 'No email'} · @{user.username ?? 'No username'}</p>
            <UserEditor userId={user.id} {user} />
            <h3>Password reset</h3>
            <button class="secondary-button" type="button" onclick={generateReset}>Generate reset code</button>
            {#if resetCode}<p role="status">Code: <strong>{resetCode}</strong></p>
                <CopyButton value={resetCode} label="Copy reset code" /><button
                    class="secondary-button"
                    type="button"
                    onclick={revokeReset}>Revoke reset code</button
                >{/if}
            {#if resetError}<p class="error-message" role="alert">{resetError}</p>{/if}
        </section>{/if}
</div>
