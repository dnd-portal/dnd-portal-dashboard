<script lang="ts">
    import { api, ApiRequestError } from '#lib/api/index.js';
    type Campaign = { id: number; name: string };
    type Member = { user_id: number; display_name: string; membership_role: string; left_at: string | null };
    let rows = $state<Member[]>([]);
    let error = $state('');
    async function load() {
        try {
            const campaigns = await api.get<Campaign[]>('/api/v1/campaigns');
            rows = campaigns[0] ? await api.get<Member[]>(`/api/v1/campaigns/${campaigns[0].id}/members`) : [];
        } catch (cause) {
            error = cause instanceof ApiRequestError ? cause.message : 'Unable to load party.';
        }
    }
    $effect(() => void load());
</script>

<main class="admin-content">
    <section class="admin-detail">
        <a href="/">← Dashboard</a>
        <h1>Party roster</h1>
        {#if error}<p class="error-message" role="alert">{error}</p>{:else if !rows.length}<p role="status">
                No party members available.
            </p>{:else}<ul>
                {#each rows as row}<li>{row.display_name} — {row.membership_role}</li>{/each}
            </ul>{/if}
    </section>
</main>
