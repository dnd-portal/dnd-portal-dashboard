<script lang="ts">
    import { onMount } from 'svelte';
    import { api, ApiRequestError } from '#lib/api/index.js';
    import PlayerShell from '#lib/components/player/PlayerShell.svelte';
    type User = { display_name: string; username: string | null; roles?: string[] };
    type Campaign = { id: number; name: string };
    type Member = { display_name: string; membership_role: string };
    let user = $state<User | null>(null);
    let campaign = $state<Campaign | null>(null);
    let rows = $state<Member[]>([]);
    let error = $state('');
    onMount(async () => {
        try {
            user = await api.get<User>('/api/v1/auth/me');
            const campaigns = await api.get<Campaign[]>('/api/v1/campaigns');
            campaign = campaigns[0] ?? null;
            if (campaign) rows = await api.get<Member[]>(`/api/v1/campaigns/${campaign.id}/members`);
        } catch (cause) {
            error = cause instanceof ApiRequestError ? cause.message : 'Unable to load party.';
        }
    });
</script>

{#if user}<PlayerShell {user} campaignName={campaign?.name}
        ><section class="player-page">
            <a href="/">← Dashboard</a>
            <h1>Party roster</h1>
            {#if error}<p class="error-message" role="alert">{error}</p>{:else if !rows.length}<p role="status">
                    No party members available.
                </p>{:else}<ul>
                    {#each rows as row}<li>{row.display_name} — {row.membership_role}</li>{/each}
                </ul>{/if}
        </section></PlayerShell
    >{:else}<p class="content-state">{error || 'Checking access…'}</p>{/if}
