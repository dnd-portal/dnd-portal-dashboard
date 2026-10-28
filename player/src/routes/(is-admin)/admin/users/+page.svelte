<script lang="ts">
    import { onMount } from 'svelte';
    import AdminHeader from '#lib/components/admin/AdminHeader.svelte';
    import CopyButton from '#lib/components/shared/CopyButton.svelte';
    import RowActions from '#lib/components/shared/RowActions.svelte';
    import { api, ApiRequestError } from '#lib/api/index.js';
    type User = {
        id: number;
        display_name: string;
        email: string | null;
        username: string | null;
        account_status: string;
        roles: string[];
    };
    type Invite = {
        id: number;
        invited_user_id: number | null;
        display_name: string | null;
        username: string | null;
        expires_at: string;
        consumed_at: string | null;
        revoked_at: string | null;
    };
    let users = $state<User[]>([]);
    let invites = $state<Invite[]>([]);
    let search = $state('');
    let filter = $state<'all' | 'active' | 'pending'>('all');
    let error = $state('');
    let message = $state('');
    let busy = $state(false);
    let showAdd = $state(false);
    let showImport = $state(false);
    let inviteUser = $state<User | null>(null);
    let deleteUserTarget = $state<User | null>(null);
    let generated = $state<{ name: string; username: string; url: string } | null>(null);
    let displayName = $state('');
    let username = $state('');
    let role = $state('campaign_member');
    let addTrigger = $state<HTMLButtonElement | null>(null);
    let importTrigger = $state<HTMLButtonElement | null>(null);
    let importDisplayName = $state('');
    let importUsername = $state('');
    let importEmail = $state('');
    let importRole = $state('campaign_member');
    let importHash = $state('');
    let importError = $state('');
    const roles = ['portal_admin', 'campaign_dm', 'campaign_member'];
    const roleLabel = (value: string) =>
        ({ portal_admin: 'Portal administrator', campaign_dm: 'Dungeon Master', campaign_member: 'Player' })[value] ??
        value;
    const validInvite = (id: number) =>
        invites.find(
            (i) => i.invited_user_id === id && !i.consumed_at && !i.revoked_at && new Date(i.expires_at) > new Date()
        );
    const link = (code: string) => `${window.location.origin}/register?code=${encodeURIComponent(code)}`;
    async function load() {
        try {
            [users, invites] = await Promise.all([
                api.get<User[]>('/api/v1/users'),
                api.get<Invite[]>('/api/v1/admin/invites')
            ]);
        } catch (e) {
            error = e instanceof ApiRequestError ? e.message : 'Unable to load users.';
        }
    }
    async function addUser() {
        busy = true;
        error = '';
        try {
            const user = await api.post<User>('/api/v1/admin/users/pending', {
                display_name: displayName,
                username,
                role
            });
            users = [...users, user];
            showAdd = false;
            inviteUser = user;
            displayName = '';
            username = '';
            role = 'campaign_member';
        } catch (e) {
            error = e instanceof ApiRequestError ? e.message : 'Unable to create pending user.';
        } finally {
            busy = false;
        }
    }
    function closeAdd() {
        showAdd = false;
        addTrigger?.focus();
    }
    function closeImport() {
        showImport = false;
        importTrigger?.focus();
    }
    function handleKeydown(event: KeyboardEvent) {
        if (event.key === 'Escape' && showAdd) closeAdd();
        if (event.key === 'Escape' && showImport) closeImport();
        if (event.key === 'Escape' && deleteUserTarget) deleteUserTarget = null;
    }
    async function importUser() {
        busy = true;
        importError = '';
        try {
            const user = await api.post<User>('/api/v1/admin/users/import', {
                display_name: importDisplayName,
                username: importUsername,
                email: importEmail,
                role: importRole,
                password_hash: importHash
            });
            users = [...users, user];
            closeImport();
            message = 'User imported successfully.';
            importDisplayName = '';
            importUsername = '';
            importEmail = '';
            importRole = 'campaign_member';
            importHash = '';
        } catch (e) {
            importError = e instanceof ApiRequestError ? e.message : 'Unable to import user.';
        } finally {
            busy = false;
        }
    }
    async function deleteUser() {
        if (!deleteUserTarget) return;
        busy = true;
        error = '';
        try {
            await api.delete(`/api/v1/users/${deleteUserTarget.id}`);
            users = users.filter((user) => user.id !== deleteUserTarget!.id);
            message = `${deleteUserTarget.display_name} was deleted.`;
            deleteUserTarget = null;
        } catch (e) {
            error = e instanceof ApiRequestError ? e.message : 'Unable to delete user.';
        } finally {
            busy = false;
        }
    }
    async function invite(user: User, replace = false) {
        busy = true;
        error = '';
        try {
            if (replace) {
                const old = validInvite(user.id);
                if (old) await api.post('/api/v1/admin/invites/revoke', { id: old.id });
            }
            const result = await api.post<{ code: string }>('/api/v1/admin/invites', { user_id: user.id });
            generated = { name: user.display_name, username: user.username ?? '', url: link(result.code) };
            await load();
            inviteUser = null;
        } catch (e) {
            error = e instanceof ApiRequestError ? e.message : 'Unable to generate invitation.';
        } finally {
            busy = false;
        }
    }
    function requestInvite(user: User) {
        inviteUser = user;
        if (validInvite(user.id)) message = 'A valid invitation already exists for this pending user.';
    }
    onMount(() => void load());
    const visible = () =>
        users.filter(
            (u) =>
                (filter === 'all' ||
                    (filter === 'active' ? u.account_status === 'active' : u.account_status === 'invited')) &&
                `${u.display_name} ${u.username ?? ''}`.toLowerCase().includes(search.toLowerCase())
        );
    const userActions = (user: User) => [
        ...(user.account_status === 'invited'
            ? [
                  {
                      label: validInvite(user.id) ? 'Manage invitation' : 'Generate invitation',
                      onselect: () => requestInvite(user)
                  }
              ]
            : []),
        { label: 'Delete user', tone: 'danger' as const, onselect: () => (deleteUserTarget = user) }
    ];
</script>

<svelte:window onkeydown={handleKeydown} />

<div class="admin-shell">
    <AdminHeader pageKey="users" subtitle="Manage active accounts and pending people who still need to register." />
    <section class="admin-detail">
        <div class="p-page-header">
            <p class="p-page-header__context">Accounts and invitations</p>
        </div>
        <div class="admin-mvp-toolbar">
            <div class="admin-mvp-toolbar__actions">
                <button bind:this={addTrigger} class="p-button" onclick={() => (showAdd = true)}>Add user</button
                ><button
                    bind:this={importTrigger}
                    class="p-button p-button--secondary"
                    onclick={() => (showImport = true)}>Import user</button
                >
            </div>
            <div class="admin-mvp-toolbar__filters">
                <label for="user-search">Search</label><input
                    id="user-search"
                    class="p-input"
                    placeholder="Name or username"
                    bind:value={search}
                /><label for="user-status">Status</label><select id="user-status" class="p-input" bind:value={filter}
                    ><option value="all">All statuses</option><option value="active">Active</option><option
                        value="pending">Pending</option
                    ></select
                >
            </div>
        </div>
        {#if error}<p class="error-message" role="alert">{error}</p>{/if}{#if message}<p
                class="success-message"
                role="status"
            >
                {message}
            </p>{/if}
        {#if visible().length === 0}<div class="p-card admin-mvp-empty">
                <h2>No users found</h2>
                <p>Try another search or status filter.</p>
            </div>{:else}<div class="p-table-wrap admin-mvp-table">
                <table class="p-table">
                    <caption>Users</caption><thead
                        ><tr
                            ><th>Display name</th><th>Username</th><th>Email</th><th>Role</th><th>Status</th><th
                                >Invitation</th
                            ><th>Actions</th></tr
                        ></thead
                    ><tbody
                        >{#each visible() as user}<tr
                                ><td class="admin-mvp-wrap">{user.display_name}</td><td class="admin-mvp-wrap"
                                    >{user.username ?? '—'}</td
                                ><td>{user.email ?? 'Not registered yet'}</td><td
                                    >{user.roles.map(roleLabel).join(', ') || '—'}</td
                                ><td
                                    ><span
                                        class:admin-mvp-badge--pending={user.account_status === 'invited'}
                                        class="admin-mvp-badge"
                                        >{user.account_status === 'invited' ? 'Pending' : 'Active'}</span
                                    ></td
                                ><td
                                    ><span class="admin-mvp-badge admin-mvp-badge--neutral"
                                        >{validInvite(user.id) ? 'Valid' : '—'}</span
                                    ></td
                                ><td><RowActions label={user.display_name} actions={userActions(user)} /></td></tr
                            >{/each}</tbody
                    >
                </table>
            </div>{/if}
    </section>
</div>
{#if showAdd}<div
        class="admin-mvp-modal-backdrop"
        role="presentation"
        onclick={(event) => event.target === event.currentTarget && closeAdd()}
    >
        <div class="p-card admin-mvp-modal" role="dialog" aria-modal="true" aria-labelledby="add-user-title">
            <h2 id="add-user-title">Add user</h2>
            <label>Display name<input class="p-input" bind:value={displayName} /></label><label
                >Username<input class="p-input" bind:value={username} /></label
            ><label
                >Role<select class="p-input" bind:value={role}
                    >{#each roles as item}<option value={item}>{roleLabel(item)}</option>{/each}</select
                ></label
            >
            <div class="admin-mvp-modal__actions">
                <button
                    class="p-button"
                    disabled={busy || !displayName.trim() || !username.trim()}
                    onclick={() => void addUser()}>Create pending user</button
                ><button class="p-button p-button--secondary" type="button" onclick={closeAdd}>Cancel</button>
            </div>
        </div>
    </div>{/if}
{#if showImport}<div class="admin-mvp-modal-backdrop">
        <div class="p-card admin-mvp-modal" role="dialog" aria-modal="true" aria-labelledby="import-user-title">
            <h2 id="import-user-title">Import user</h2>
            <p>Import an existing active account using its already-generated PHP password hash.</p>
            {#if importError}<p class="error-message" role="alert">{importError}</p>{/if}
            <label>Display name<input class="p-input" bind:value={importDisplayName} autocomplete="off" /></label>
            <label>Username<input class="p-input" bind:value={importUsername} autocomplete="off" /></label>
            <label>Email<input class="p-input" type="email" bind:value={importEmail} autocomplete="off" /></label>
            <label
                >Role<select class="p-input" bind:value={importRole}
                    >{#each roles as item}<option value={item}>{roleLabel(item)}</option>{/each}</select
                ></label
            >
            <label
                >Password hash<input
                    class="p-input"
                    type="password"
                    bind:value={importHash}
                    autocomplete="new-password"
                    spellcheck="false"
                /></label
            >
            <div class="admin-mvp-modal__actions">
                <button
                    class="p-button"
                    disabled={busy ||
                        !importDisplayName.trim() ||
                        !importUsername.trim() ||
                        !importEmail.trim() ||
                        !importHash.trim()}
                    onclick={() => void importUser()}>{busy ? 'Importing…' : 'Import user'}</button
                >
                <button class="p-button p-button--secondary" type="button" disabled={busy} onclick={closeImport}
                    >Cancel</button
                >
            </div>
        </div>
    </div>{/if}
{#if deleteUserTarget}<div class="admin-mvp-modal-backdrop">
        <div class="p-card admin-mvp-modal" role="dialog" aria-modal="true" aria-labelledby="delete-user-title">
            <h2 id="delete-user-title">Delete user?</h2>
            <p>
                This permanently removes {deleteUserTarget.display_name} and account-owned authentication data. Historical
                records owned by the user are protected and will refuse deletion.
            </p>
            <div class="admin-mvp-modal__actions">
                <button class="p-button p-button--danger" disabled={busy} onclick={() => void deleteUser()}
                    >{busy ? 'Deleting…' : 'Delete user'}</button
                >
                <button
                    class="p-button p-button--secondary"
                    type="button"
                    disabled={busy}
                    onclick={() => (deleteUserTarget = null)}>Cancel</button
                >
            </div>
        </div>
    </div>{/if}
{#if inviteUser}<div class="admin-mvp-modal-backdrop">
        <div class="p-card admin-mvp-modal" role="dialog" aria-modal="true">
            <h2>Generate an invitation</h2>
            <p>Generate an invitation so {inviteUser!.display_name} can register their account.</p>
            <div class="admin-mvp-modal__actions">
                {#if validInvite(inviteUser!.id)}<p>This pending user already has a valid invitation.</p>
                    <button
                        class="p-button p-button--danger"
                        disabled={busy}
                        onclick={() => void invite(inviteUser!, true)}>Revoke and generate new invitation</button
                    >
                {:else}<button class="p-button" disabled={busy} onclick={() => void invite(inviteUser!)}
                        >Generate invitation</button
                    >{/if}
                <button class="p-button p-button--secondary" type="button" onclick={() => (inviteUser = null)}
                    >Cancel</button
                >
            </div>
        </div>
    </div>{/if}
{#if generated}<div class="admin-mvp-modal-backdrop">
        <div class="p-card admin-mvp-modal" role="dialog" aria-modal="true">
            <h2>Invitation created</h2>
            <p>{generated.name} ({generated.username}) can register once using this link.</p>
            <input class="p-input" readonly value={generated.url} /><CopyButton
                value={generated.url}
                label="Copy link"
            />
            <p>This invitation can only be used once.</p>
            <div class="admin-mvp-modal__actions">
                <button class="p-button p-button--secondary" type="button" onclick={() => (generated = null)}
                    >Close</button
                >
            </div>
        </div>
    </div>{/if}
