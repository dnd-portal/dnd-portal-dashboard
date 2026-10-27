<script lang="ts">
    import { onMount } from 'svelte';
    import { api, ApiRequestError } from '#lib/api/index.js';
    type Poll = {
        id: number;
        title: string;
        explanation: string | null;
        choice_type: 'single' | 'multiple';
        status: 'open' | 'closed';
        options: { id: number; label: string }[];
        selected_option_ids: number[];
    };
    let poll = $state<Poll | null>(null);
    let selected = $state<number[]>([]);
    let loading = $state(true);
    let saving = $state(false);
    let message = $state('');
    let error = $state('');
    async function load() {
        loading = true;
        error = '';
        try {
            poll = await api.get<Poll | null>('/api/v1/global-polls/current');
            selected = poll?.selected_option_ids ?? [];
        } catch (e) {
            error = e instanceof ApiRequestError ? e.message : 'Could not load the poll.';
        } finally {
            loading = false;
        }
    }
    function toggle(id: number) {
        if (poll?.choice_type === 'single') selected = [id];
        else selected = selected.includes(id) ? selected.filter((x) => x !== id) : [...selected, id];
    }
    async function save() {
        if (!poll || !selected.length) return;
        saving = true;
        message = '';
        error = '';
        try {
            poll = await api.put<Poll>(`/api/v1/global-polls/${poll.id}/response`, { option_ids: selected });
            selected = poll.selected_option_ids;
            message = 'Your answer is saved.';
        } catch (e) {
            error = e instanceof ApiRequestError ? e.message : 'Could not save your answer.';
        } finally {
            saving = false;
        }
    }
    onMount(() => void load());
</script>

<svelte:head><title>Current poll · D&amp;D Portal</title></svelte:head>
{#if loading}<section class="p-state"><h1>Loading current poll…</h1></section>
{:else if error}<section class="p-state" role="alert">
        <h1>Poll unavailable</h1>
        <p>{error}</p>
        <button class="p-button" onclick={() => void load()}>Try again</button>
    </section>
{:else if !poll}<section class="p-state">
        <h1>No open poll</h1>
        <p>There is no global poll available right now.</p>
    </section>
{:else}<section class="p-page-header">
        <p class="p-eyebrow">GLOBAL POLL</p>
        <h1>{poll.title}</h1>
        <p>{poll.explanation ?? 'Choose the option that works best for you.'}</p>
    </section>
    <section class="p-card poll-card">
        <fieldset disabled={poll.status === 'closed' || saving}>
            <legend>{poll.choice_type === 'multiple' ? 'Choose one or more options' : 'Choose one option'}</legend
            >{#each poll.options as option}<label class="poll-option"
                    ><input
                        type={poll.choice_type === 'multiple' ? 'checkbox' : 'radio'}
                        name="global-poll"
                        checked={selected.includes(option.id)}
                        onchange={() => toggle(option.id)}
                    /> <span>{option.label}</span></label
                >{/each}
        </fieldset>
        {#if poll.status === 'open'}<button
                class="p-button"
                disabled={!selected.length || saving}
                onclick={() => void save()}>{saving ? 'Saving…' : 'Save answer'}</button
            >{:else}<p role="status">This poll is closed. Your saved answer is shown above.</p>{/if}{#if message}<p
                class="success-message"
                role="status"
            >
                {message}
            </p>{/if}{#if error}<p class="error-message" role="alert">{error}</p>{/if}
    </section>{/if}
