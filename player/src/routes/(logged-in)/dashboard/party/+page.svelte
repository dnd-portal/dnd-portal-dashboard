<script lang="ts">
    import { onMount } from 'svelte';
    import { api, ApiRequestError } from '#lib/api/index.js';
    type Campaign = { id: number; name: string };
    type Member = { display_name: string; membership_role: string };
    let campaign = $state<Campaign | null>(null);
    let rows = $state<Member[]>([]);
    let error = $state('');
    onMount(async () => {
        try {
            const campaigns = await api.get<Campaign[]>('/api/v1/campaigns');
            campaign = campaigns[0] ?? null;
            if (campaign) rows = await api.get<Member[]>(`/api/v1/campaigns/${campaign.id}/members`);
        } catch (cause) {
            error = cause instanceof ApiRequestError ? cause.message : 'Unable to load party.';
        }
    });
</script>

<section class="p-dashboard">
    <a href="/dashboard">← Dashboard</a>
    <h1>Party roster</h1>
    {#if error}<p class="error-message" role="alert">{error}</p>{:else if !rows.length}<p role="status">
            No party members available.
        </p>{:else}<ul>
            {#each rows as row}<li>{row.display_name} — {row.membership_role}</li>{/each}
        </ul>{/if}
</section>
