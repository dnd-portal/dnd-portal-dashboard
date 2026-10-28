<script lang="ts">
    import { getPlayerUser } from '#lib/player/context.js';

    const playerContext = getPlayerUser();
    const roleLabels: Record<string, string> = {
        campaign_member: 'Player',
        dungeon_master: 'Dungeon Master',
        portal_admin: 'Portal administrator'
    };

    const initials = $derived(
        (playerContext.user?.display_name ?? 'Player')
            .split(/\s+/)
            .filter(Boolean)
            .map((part) => part[0])
            .join('')
            .slice(0, 2)
            .toUpperCase()
    );
</script>

<svelte:head><title>Profile | D&amp;D Portal</title></svelte:head>

<section class="p-page-header profile-page-header">
    <p class="p-eyebrow">ACCOUNT</p>
    <h1>Profile</h1>
    <p>Review the account details connected to your portal account.</p>
</section>

<section class="p-card profile-card" aria-labelledby="profile-details">
    <div class="profile-card__identity">
        <div class="p-avatar profile-card__avatar" aria-hidden="true">{initials}</div>
        <div>
            <h2 id="profile-details">{playerContext.user?.display_name ?? 'Your account'}</h2>
            <p>@{playerContext.user?.username ?? '—'}</p>
            <div class="profile-details__roles">
                {#if playerContext.user?.roles?.length}
                    {#each playerContext.user.roles as role}<span class="profile-role"
                            >{roleLabels[role] ?? 'Player'}</span
                        >{/each}
                {:else}<span class="profile-role">Player</span>{/if}
            </div>
        </div>
    </div>
    <dl class="profile-details">
        <div>
            <dt>Email</dt>
            <dd>{playerContext.user?.email ?? '—'}</dd>
        </div>
    </dl>
</section>
