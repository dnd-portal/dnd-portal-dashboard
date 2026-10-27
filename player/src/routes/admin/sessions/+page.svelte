<script lang="ts">
    import AdminHeader from '#lib/components/admin/AdminHeader.svelte';
    import { api, ApiRequestError } from '#lib/api/index.js';
    type Session = { id: number; title: string; session_date: string; status: string; version: number };
    let campaignId = $state('');
    let rows = $state<Session[]>([]);
    let error = $state('');
    let title = $state('');
    let date = $state('');
    let busy = $state(false);
    async function load() {
        try {
            rows = await api.get<Session[]>(`/api/v1/campaigns/${Number(campaignId)}/sessions`);
        } catch (e) {
            error = e instanceof ApiRequestError ? e.message : 'Unable to load sessions.';
        }
    }
    async function create() {
        busy = true;
        try {
            await api.post(`/api/v1/campaigns/${Number(campaignId)}/sessions`, { title, session_date: date });
            title = '';
            date = '';
            await load();
        } catch (e) {
            error = e instanceof ApiRequestError ? e.message : 'Unable to create session.';
        } finally {
            busy = false;
        }
    }
</script>

<div class="admin-shell">
    <AdminHeader pageKey="sessions" />
    <section class="admin-detail">
        <label for="session-campaign">Campaign ID</label><input
            id="session-campaign"
            type="number"
            bind:value={campaignId}
        /><button class="secondary-button" disabled={!campaignId} onclick={load}>Load sessions</button>
        <h2>Create session</h2>
        <label for="session-title">Title</label><input id="session-title" bind:value={title} /><label for="session-date"
            >Calendar date</label
        ><input id="session-date" type="date" bind:value={date} /><button
            class="primary-button"
            disabled={busy || !campaignId || !title.trim() || !date}
            onclick={create}>Create</button
        >{#if error}<p class="error-message" role="alert">{error}</p>{/if}
    </section>
    {#if rows.length}<section class="admin-detail">
            <ul>
                {#each rows as row}<li>
                        <a href={`/admin/sessions/${row.id}`}>{row.title}</a> — {row.session_date} — {row.status}
                    </li>{/each}
            </ul>
        </section>{:else if campaignId}<p role="status">No sessions loaded.</p>{/if}
</div>
