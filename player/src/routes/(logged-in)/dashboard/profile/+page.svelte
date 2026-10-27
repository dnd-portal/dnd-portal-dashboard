<script lang="ts">
    import { onMount } from 'svelte';
    import { goto } from '$app/navigation';
    import { api, ApiRequestError } from '#lib/api/index.js';
    import { getPlayerUser } from '#lib/player/context.js';

    type Campaign = { id: number; name: string; membership_role: string | null };
    const playerContext = getPlayerUser();
    let campaigns = $state<Campaign[]>([]);
    let error = $state('');
    let signingOut = $state(false);

    onMount(async () => {
        try {
            campaigns = await api.get<Campaign[]>('/api/v1/campaigns');
        } catch (cause) {
            error = cause instanceof ApiRequestError ? cause.message : 'Unable to load your campaigns.';
        }
    });

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

{#if error}<p class="error-message" role="alert">{error}</p>{/if}

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

<section class="p-card" aria-labelledby="profile-campaigns">
    <h2 id="profile-campaigns">Campaigns</h2>
    {#if campaigns.length}
        <ul>
            {#each campaigns as campaign}
                <li>{campaign.name} — {campaign.membership_role ?? 'member'}</li>
            {/each}
        </ul>
    {:else}
        <p>You are not currently a member of a campaign.</p>
    {/if}
</section>

<button class="p-button p-button--secondary" type="button" disabled={signingOut} onclick={signOut}>
    {signingOut ? 'Signing out…' : 'Sign out'}
</button>
