<script lang="ts">
    import { page } from '$app/state';
    import { api, ApiRequestError } from '#lib/api/index.js';

    let { manager = false }: { manager?: boolean } = $props();
    type Option = { id: number; label: string; position: number };
    type Poll = {
        id: number;
        title: string;
        choice_type: 'single' | 'multiple';
        status: string;
        closes_at?: string | null;
        version: number;
        options: Option[];
        selected_option_ids: number[];
        response_state: 'pending' | 'voted' | 'responded_without_option';
    };
    type Result = { id: number; label: string; vote_count: number };
    const id = Number(page.params.id);
    let poll = $state<Poll | null>(null);
    let results = $state<Result[]>([]);
    let error = $state('');
    let notice = $state('');
    let busy = $state(false);
    let draft = $state('');
    let selected = $state<number[]>([]);
    async function load() {
        try {
            poll = await api.get<Poll>(`/api/v1/polls/${id}`);
            draft = poll.title;
            selected = [...poll.selected_option_ids];
            error = '';
        } catch (e) {
            error = e instanceof ApiRequestError ? e.message : 'Unable to load poll.';
        }
    }
    async function save() {
        if (!poll) return;
        busy = true;
        error = '';
        try {
            poll = await api.patch<Poll>(`/api/v1/polls/${id}`, { title: draft, version: poll.version });
            notice = 'Saved.';
        } catch (e) {
            error = e instanceof ApiRequestError ? `${e.message} (${e.code})` : 'Unable to save poll.';
            await load();
        } finally {
            busy = false;
        }
    }
    async function vote() {
        if (!poll) return;
        busy = true;
        error = '';
        notice = '';
        try {
            await api.put(`/api/v1/polls/${id}/vote`, { option_ids: selected });
            notice = 'Vote saved.';
            await load();
        } catch (e) {
            error = e instanceof ApiRequestError ? e.message : 'Unable to save vote.';
        } finally {
            busy = false;
        }
    }
    async function respondWithoutOption() {
        if (!poll) return;
        busy = true;
        error = '';
        notice = '';
        try {
            await api.post(`/api/v1/polls/${id}/respond-without-option`, {});
            notice = 'Response saved.';
            await load();
        } catch (e) {
            error = e instanceof ApiRequestError ? e.message : 'Unable to save response.';
        } finally {
            busy = false;
        }
    }
    async function close() {
        if (!poll) return;
        busy = true;
        try {
            poll = await api.patch<Poll>(`/api/v1/polls/${id}`, { status: 'closed', version: poll.version });
            notice = 'Poll closed.';
        } catch (e) {
            error = e instanceof ApiRequestError ? e.message : 'Unable to close poll.';
        } finally {
            busy = false;
        }
    }
    async function showResults() {
        try {
            const response = await api.get<{ poll_id: number; options: Result[] }>(`/api/v1/polls/${id}/results`);
            results = response.options;
        } catch (e) {
            error = e instanceof ApiRequestError ? e.message : 'Unable to load results.';
        }
    }
    function toggle(option: number) {
        if (!poll) return;
        if (poll.choice_type === 'single') selected = [option];
        else
            selected = selected.includes(option) ? selected.filter((value) => value !== option) : [...selected, option];
    }
    $effect(() => void load());
</script>

{#if poll}<section class="admin-detail">
        <h1>{poll.title}</h1>
        {#if manager}<label for="poll-detail-title">Title</label><input
                id="poll-detail-title"
                bind:value={draft}
            /><button class="primary-button" disabled={busy} onclick={save}>Save changes</button>
            <button class="secondary-button" disabled={busy || poll.status === 'closed'} onclick={close}
                >Close poll</button
            >{/if}
        <p>
            Status: {poll.status}{#if poll.closes_at}
                · closes {poll.closes_at}{/if} · {poll.choice_type}
        </p>
        {#if poll.status === 'open'}<fieldset>
                <legend>Your answer</legend>{#each poll.options as option}<label
                        ><input
                            type={poll.choice_type === 'single' ? 'radio' : 'checkbox'}
                            name="poll-option"
                            checked={selected.includes(option.id)}
                            onchange={() => toggle(option.id)}
                        />
                        {option.label}</label
                    >{/each}
                <button class="primary-button" disabled={busy || selected.length === 0} onclick={vote}>Save vote</button
                >
                <button class="secondary-button" disabled={busy} onclick={respondWithoutOption}>No option fits</button>
            </fieldset>{:else}<p role="status">This poll is closed or expired.</p>{/if}
        <button class="secondary-button" disabled={busy} onclick={showResults}>View results</button>
        {#if results.length}<ul>
                {#each results as result}<li>{result.label} — {result.vote_count}</li>{/each}
            </ul>{/if}
        {#if notice}<p role="status">{notice}</p>{/if}{#if error}<p class="error-message" role="alert">{error}</p>{/if}
    </section>{:else if error}<p class="error-message" role="alert">{error}</p>{:else}<p role="status">Loading…</p>{/if}
