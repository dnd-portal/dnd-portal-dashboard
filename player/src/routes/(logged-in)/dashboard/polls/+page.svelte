<script lang="ts">
    import { onMount } from 'svelte';
    import { api, ApiRequestError } from '#lib/api/index.js';
    type PollSummary = {
        id: number;
        title: string;
        explanation: string | null;
        status: 'open';
        selected_count: number;
    };
    let polls = $state<PollSummary[]>([]);
    let loading = $state(true);
    let error = $state('');
    async function load() {
        loading = true;
        error = '';
        try {
            polls = await api.get<PollSummary[]>('/api/v1/global-polls');
        } catch (cause) {
            error = cause instanceof ApiRequestError ? cause.message : 'Could not load polls.';
        } finally {
            loading = false;
        }
    }
    onMount(() => void load());
</script>

<svelte:head><title>Polls · D&amp;D Portal</title></svelte:head>
{#if loading}<section class="p-state"><h1>Loading polls…</h1></section>
{:else if error}<section class="p-state" role="alert">
        <h1>Polls unavailable</h1>
        <p>{error}</p>
        <button class="p-button" onclick={() => void load()}>Try again</button>
    </section>
{:else}<section class="p-page-header poll-page-header">
        <p class="p-eyebrow">POLLS</p>
        <h1>Open polls</h1>
        <p>Share the dates that work for you.</p>
    </section>
    {#if polls.length === 0}<section class="p-state poll-empty">
            <h2>No open polls</h2>
            <p>There are no global polls waiting for your answer.</p>
        </section>
    {:else}<section class="poll-list" aria-label="Open polls">
            {#each polls as poll}<a class="p-card poll-summary" href={`/dashboard/polls/${poll.id}`}>
                    <div>
                        <div class="poll-summary__topline">
                            <span class="poll-status">Open</span><span
                                >{poll.selected_count ? 'Answered' : 'Not answered'}</span
                            >
                        </div>
                        <h2>{poll.title}</h2>
                        {#if poll.explanation}<p>{poll.explanation}</p>{/if}
                    </div>
                    <div class="poll-summary__action">
                        <span>{poll.selected_count ? `${poll.selected_count} dates selected` : 'Choose dates'}</span
                        ><span aria-hidden="true">→</span>
                    </div>
                </a>{/each}
        </section>{/if}{/if}
