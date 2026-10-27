<script lang="ts">
    import { api, ApiRequestError } from '#lib/api/index.js';
    type Campaign = { id: number; name: string };
    type Session = {
        id: number;
        title: string;
        session_date: string;
        starts_at: string | null;
        ends_at: string | null;
        location: string | null;
        status: string;
    };
    let rows = $state<Session[]>([]);
    let error = $state('');
    async function load() {
        try {
            const campaigns = await api.get<Campaign[]>('/api/v1/campaigns');
            rows = campaigns[0] ? await api.get<Session[]>(`/api/v1/campaigns/${campaigns[0].id}/sessions`) : [];
        } catch (cause) {
            error = cause instanceof ApiRequestError ? cause.message : 'Unable to load sessions.';
        }
    }
    $effect(() => void load());
</script>

<main class="admin-content">
    <section class="admin-detail">
        <a href="/">← Dashboard</a>
        <h1>Sessions</h1>
        {#if error}<p class="error-message" role="alert">{error}</p>{:else if !rows.length}<p role="status">
                No sessions available.
            </p>{:else}<ul>
                {#each rows as row}<li>
                        <a href={`/sessions/${row.id}`}>{row.title}</a> — {row.session_date} — {row.location ??
                            'Location to be announced'}
                    </li>{/each}
            </ul>{/if}
    </section>
</main>
