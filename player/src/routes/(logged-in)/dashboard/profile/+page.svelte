<script lang="ts">
    import { goto } from '$app/navigation';
    import { api } from '#lib/api/index.js';
    import { getPlayerUser } from '#lib/player/context.js';

    const playerContext = getPlayerUser();
    let signingOut = $state(false);

    async function signOut() {
        signingOut = true;
        try {
            await api.post('/api/v1/auth/logout', {});
        } finally {
            await goto('/login');
        }
    }
</script>

<svelte:head><title>Profile | D&amp;D Portal</title></svelte:head>

<section class="p-page-header">
    <p class="p-eyebrow">ACCOUNT</p>
    <h1>Profile</h1>
    <p>Review the account details and campaign memberships associated with your portal account.</p>
</section>

<section class="p-card" aria-labelledby="profile-details">
    <h2 id="profile-details">Account details</h2>
    <dl class="p-definition-list">
        <div>
            <dt>Display name</dt>
            <dd>{playerContext.user?.display_name ?? '—'}</dd>
        </div>
        <div>
            <dt>Username</dt>
            <dd>{playerContext.user?.username ?? '—'}</dd>
        </div>
        <div>
            <dt>Email</dt>
            <dd>{playerContext.user?.email ?? '—'}</dd>
        </div>
        <div>
            <dt>Roles</dt>
            <dd>{playerContext.user?.roles?.join(', ') || 'Player'}</dd>
        </div>
    </dl>
</section>

<!-- TODO post-MVP: restore campaign memberships when campaign data is part of the MVP. -->

<button class="p-button p-button--secondary" type="button" disabled={signingOut} onclick={signOut}>
    {signingOut ? 'Signing out…' : 'Sign out'}
</button>
