<script lang="ts">
    import { onMount } from 'svelte';
    import { api, ApiRequestError } from '#lib/api/index.js';
    import PlayerShell from '#lib/components/player/PlayerShell.svelte';
    import { formatPortalDate, formatPortalTime } from '#lib/player/date.js';
    type User = { display_name: string; username: string | null; roles?: string[] };
    type Campaign = { id: number; name: string };
    type Session = {
        id: number;
        title: string;
        session_date: string;
        starts_at: string | null;
        location: string | null;
        status: string;
    };
    let user = $state<User | null>(null);
    let campaign = $state<Campaign | null>(null);
    let rows = $state<Session[]>([]);
    let error = $state('');
    onMount(async () => {
        try {
            user = await api.get<User>('/api/v1/auth/me');
            const campaigns = await api.get<Campaign[]>('/api/v1/campaigns');
            campaign = campaigns[0] ?? null;
            if (campaign) rows = await api.get<Session[]>(`/api/v1/campaigns/${campaign.id}/sessions`);
        } catch (cause) {
            error = cause instanceof ApiRequestError ? cause.message : 'Unable to load sessions.';
        }
    });
</script>

{#if user}<PlayerShell {user} campaignName={campaign?.name}
        ><section class="player-page">
            <a href="/">← Dashboard</a>
            <h1>Sessions</h1>
            {#if error}<p class="error-message" role="alert">{error}</p>{:else if !rows.length}<p role="status">
                    No sessions available.
                </p>{:else}<ul>
                    {#each rows as row}<li>
                            <a href={`/sessions/${row.id}`}>{row.title}</a> — {formatPortalDate(row.session_date)} · {formatPortalTime(
                                row.starts_at
                            )} · {row.location ?? 'Location to be announced'}
                        </li>{/each}
                </ul>{/if}
        </section></PlayerShell
    >{:else}<p class="content-state">{error || 'Checking access…'}</p>{/if}
