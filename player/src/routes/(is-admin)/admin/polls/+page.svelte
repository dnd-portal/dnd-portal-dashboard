<script lang="ts">
    import AdminHeader from '#lib/components/admin/AdminHeader.svelte';
    import { api, ApiRequestError } from '#lib/api/index.js';
    type Poll = {
        id: number;
        campaign_id: number;
        title: string;
        choice_type: string;
        status: string;
        version: number;
    };
    let campaignId = $state('');
    let rows = $state<Poll[]>([]);
    let error = $state('');
    let busy = $state(false);
    let title = $state('');
    let choiceType = $state('single');
    let options = $state('');
    async function load() {
        error = '';
        try {
            rows = await api.get<Poll[]>(`/api/v1/campaigns/${Number(campaignId)}/polls`);
        } catch (e) {
            error = e instanceof ApiRequestError ? e.message : 'Unable to load polls.';
        }
    }
    async function create() {
        busy = true;
        error = '';
        try {
            await api.post(`/api/v1/campaigns/${Number(campaignId)}/polls`, {
                title,
                choice_type: choiceType,
                options: options.split('\n')
            });
            title = '';
            options = '';
            await load();
        } catch (e) {
            error = e instanceof ApiRequestError ? e.message : 'Unable to create poll.';
        } finally {
            busy = false;
        }
    }
</script>

<AdminHeader pageKey="polls" />
<section class="admin-detail">
    <label for="poll-campaign">Campaign ID</label><input id="poll-campaign" type="number" bind:value={campaignId} />
    <button class="secondary-button" disabled={!campaignId || busy} onclick={load}>Load polls</button>
    <h2>Create poll</h2>
    <label for="poll-title">Poll title</label><input id="poll-title" bind:value={title} />
    <label for="poll-type">Choice type</label><select id="poll-type" bind:value={choiceType}
        ><option value="single">Single</option><option value="multiple">Multiple</option></select
    >
    <label for="poll-options">Options, one per line</label><textarea id="poll-options" bind:value={options}></textarea>
    <button class="primary-button" disabled={busy || !campaignId || !title.trim()} onclick={create}>Create</button>
    {#if error}<p class="error-message" role="alert">{error}</p>{/if}
</section>
{#if rows.length}<section class="admin-detail">
        <h2>Polls</h2>
        <ul>
            {#each rows as row}<li>
                    <a href={`/admin/polls/${row.id}`}>{row.title}</a> — {row.status} ({row.choice_type})
                </li>{/each}
        </ul>
    </section>{:else if campaignId}<p role="status">No polls loaded.</p>{/if}
