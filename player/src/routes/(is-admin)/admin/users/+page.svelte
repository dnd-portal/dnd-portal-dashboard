<script lang="ts">
    import { onMount } from 'svelte';
    import AdminHeader from '#lib/components/admin/AdminHeader.svelte';
    import CopyButton from '#lib/components/shared/CopyButton.svelte';
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
    let inviteUser = $state<User | null>(null);
    let generated = $state<{ name: string; username: string; url: string } | null>(null);
    let displayName = $state('');
    let username = $state('');
    let role = $state('campaign_member');
    const roles = ['portal_admin', 'campaign_dm', 'campaign_member'];
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
</script>

<div class="admin-shell">
    <AdminHeader pageKey="users" />
    <section class="admin-detail">
        <div class="p-page-header">
            <h1>Users</h1>
            <p>Manage active accounts and pending people who still need to register.</p>
        </div>
        <div class="admin-detail__actions">
            <button class="primary-button" onclick={() => (showAdd = true)}>Add user</button><input
                class="p-input"
                placeholder="Search name or username"
                bind:value={search}
            /><select class="p-input" bind:value={filter}
                ><option value="all">All statuses</option><option value="active">Active</option><option value="pending"
                    >Pending</option
                ></select
            >
        </div>
        {#if error}<p class="error-message" role="alert">{error}</p>{/if}{#if message}<p
                class="success-message"
                role="status"
            >
                {message}
            </p>{/if}
        <div class="p-table-wrap">
            <table>
                <caption>Users</caption><thead
                    ><tr
                        ><th>Display name</th><th>Username</th><th>Email</th><th>Role</th><th>Status</th><th
                            >Invitation</th
                        ><th>Actions</th></tr
                    ></thead
                ><tbody
                    >{#each visible() as user}<tr
                            ><td>{user.display_name}</td><td>{user.username ?? '—'}</td><td
                                >{user.email ?? 'Not registered yet'}</td
                            ><td>{user.roles.join(', ') || '—'}</td><td
                                >{user.account_status === 'invited' ? 'Pending' : 'Active'}</td
                            ><td>{validInvite(user.id) ? 'Valid' : '—'}</td><td
                                >{#if user.account_status === 'invited'}<button
                                        class="secondary-button"
                                        onclick={() => requestInvite(user)}>Invite</button
                                    >{/if}</td
                            ></tr
                        >{/each}</tbody
                >
            </table>
        </div>
    </section>
</div>
{#if showAdd}<div class="p-card" role="dialog" aria-modal="true" aria-labelledby="add-user-title">
        <h2 id="add-user-title">Add user</h2>
        <label>Display name<input class="p-input" bind:value={displayName} /></label><label
            >Username<input class="p-input" bind:value={username} /></label
        ><label
            >Role<select class="p-input" bind:value={role}
                >{#each roles as item}<option value={item}>{item}</option>{/each}</select
            ></label
        ><button
            class="p-button"
            disabled={busy || !displayName.trim() || !username.trim()}
            onclick={() => void addUser()}>Create pending user</button
        ><button class="secondary-button" onclick={() => (showAdd = false)}>Cancel</button>
    </div>{/if}
{#if inviteUser}<div class="p-card" role="dialog" aria-modal="true">
        <h2>Generate an invitation</h2>
        <p>Generate an invitation so {inviteUser!.display_name} can register their account.</p>
        {#if validInvite(inviteUser!.id)}<p>This pending user already has a valid invitation.</p>
            <button class="p-button" disabled={busy} onclick={() => void invite(inviteUser!, true)}
                >Revoke and generate new invitation</button
            >{:else}<button class="p-button" disabled={busy} onclick={() => void invite(inviteUser!)}
                >Generate invitation</button
            >{/if}<button class="secondary-button" onclick={() => (inviteUser = null)}>Cancel</button>
    </div>{/if}
{#if generated}<div class="p-card" role="dialog" aria-modal="true">
        <h2>Invitation created</h2>
        <p>{generated.name} ({generated.username}) can register once using this link.</p>
        <input class="p-input" readonly value={generated.url} /><CopyButton value={generated.url} label="Copy link" />
        <p>This invitation can only be used once.</p>
        <button class="secondary-button" onclick={() => (generated = null)}>Close</button>
    </div>{/if}
