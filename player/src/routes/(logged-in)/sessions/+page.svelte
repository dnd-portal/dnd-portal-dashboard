<script lang="ts">
    import { onMount } from 'svelte';
    import { api, ApiRequestError } from '#lib/api/index.js';
    import { formatPortalDate, formatPortalTime } from '#lib/player/date.js';
    type Campaign = { id: number; name: string };
    type Session = {
        id: number;
        title: string;
        session_date: string;
        starts_at: string | null;
        location: string | null;
        status: string;
    };
    let campaign = $state<Campaign | null>(null);
    let rows = $state<Session[]>([]);
    let error = $state('');
    onMount(async () => {
        try {
            const campaigns = await api.get<Campaign[]>('/api/v1/campaigns');
            campaign = campaigns[0] ?? null;
            if (campaign) rows = await api.get<Session[]>(`/api/v1/campaigns/${campaign.id}/sessions`);
        } catch (cause) {
            error = cause instanceof ApiRequestError ? cause.message : 'Unable to load sessions.';
        }
    });
</script>

<section class="p-dashboard">
    <a href="/dashboard">← Dashboard</a>
    <h1>Sessions</h1>
    {#if error}<p class="error-message" role="alert">{error}</p>{:else if !rows.length}<p role="status">
            No sessions available.
        </p>{:else}<ul class="p-card p-list">
            {#each rows as row}<li class="p-list__row">
                    <a href={`/sessions/${row.id}`}>{row.title}</a> — {formatPortalDate(row.session_date)} · {formatPortalTime(
                        row.starts_at
                    )} · {row.location ?? 'Location to be announced'}
                </li>{/each}
        </ul>{/if}
</section>
