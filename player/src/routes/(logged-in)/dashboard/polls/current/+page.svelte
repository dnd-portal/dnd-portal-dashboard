<script lang="ts">
    import { onMount } from 'svelte';
    import { beforeNavigate } from '$app/navigation';
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
    let savedSelection = $state<number[]>([]);
    let loading = $state(true);
    let saving = $state(false);
    let saveStatus = $state<'idle' | 'saving' | 'saved' | 'error'>('idle');
    let saveTimer: ReturnType<typeof setTimeout> | undefined;
    let saveCycle = $state(false);
    let saveGeneration = 0;
    let error = $state('');
    async function load() {
        loading = true;
        error = '';
        saveStatus = 'idle';
        try {
            poll = await api.get<Poll | null>('/api/v1/global-polls/current');
            selected = poll?.selected_option_ids ?? [];
            savedSelection = [...selected];
        } catch (e) {
            error = e instanceof ApiRequestError ? e.message : 'Could not load the poll.';
        } finally {
            loading = false;
        }
    }
    function hasChanges() {
        return (
            selected
                .slice()
                .sort((a, b) => a - b)
                .join(',') !==
            savedSelection
                .slice()
                .sort((a, b) => a - b)
                .join(',')
        );
    }
    function isUnsaved() {
        return saveCycle || hasChanges() || saveStatus === 'error';
    }
    function scheduleSave() {
        if (!poll || poll.status === 'closed' || !hasChanges() || !selected.length) return;
        saveStatus = 'saving';
        error = '';
        if (saveTimer) clearTimeout(saveTimer);
        saveTimer = setTimeout(() => void save(), 220);
    }
    async function save() {
        if (!poll || poll.status === 'closed' || !selected.length || !hasChanges() || saveCycle) return;
        saveCycle = true;
        saving = true;
        const generation = ++saveGeneration;
        const target = [...selected];
        try {
            await api.put<Poll>(`/api/v1/global-polls/${poll.id}/response`, { option_ids: target });
            if (generation === saveGeneration) {
                savedSelection = target;
                saveStatus = 'saved';
                error = '';
            }
        } catch (e) {
            saveStatus = 'error';
            error = e instanceof ApiRequestError ? e.message : 'Could not save your answer.';
        } finally {
            saveCycle = false;
            saving = false;
            if (hasChanges() && saveStatus !== 'error') scheduleSave();
        }
    }
    function toggle(id: number) {
        if (selected.length === 1 && selected[0] === id) {
            error = 'Select at least one date.';
            return;
        }
        selected = selected.includes(id) ? selected.filter((x) => x !== id) : [...selected, id];
        scheduleSave();
    }
    onMount(() => {
        void load();
        const beforeUnload = (event: BeforeUnloadEvent) => {
            if (isUnsaved()) {
                event.preventDefault();
                event.returnValue = '';
            }
        };
        window.addEventListener('beforeunload', beforeUnload);
        return () => {
            window.removeEventListener('beforeunload', beforeUnload);
            if (saveTimer) clearTimeout(saveTimer);
        };
    });
    beforeNavigate(({ cancel }) => {
        if (isUnsaved() && !window.confirm('Your poll changes are not saved yet. Leave this page?')) cancel();
    });
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
{:else}<section class="p-page-header poll-page-header">
        <p class="p-eyebrow">GLOBAL POLL</p>
        <h1>{poll.title}</h1>
        <p>{poll.explanation ?? 'Choose the option that works best for you.'}</p>
    </section>
    <section class="p-card poll-card" class:poll-card--closed={poll.status === 'closed'}>
        <fieldset disabled={poll.status === 'closed' || saving}>
            <legend>Select all dates that work for you.</legend>
            <div class="poll-options">
                {#each poll.options as option}<label
                        class="poll-option"
                        class:poll-option--selected={selected.includes(option.id)}
                        ><input
                            type="checkbox"
                            name="global-poll"
                            checked={selected.includes(option.id)}
                            onchange={() => toggle(option.id)}
                        /> <span class="poll-option__label">{option.label}</span></label
                    >{/each}
            </div>
        </fieldset>
        {#if poll.status === 'open'}<div class="poll-save-status" aria-live="polite">
                {#if saveStatus === 'saving'}<span>Saving…</span>{:else if saveStatus === 'saved'}<span>Saved</span
                    >{:else if saveStatus === 'error'}<button
                        class="p-button p-button--secondary poll-retry"
                        type="button"
                        onclick={scheduleSave}>Try again</button
                    >{/if}
            </div>{:else}<p role="status">This poll is closed. Your saved answer is shown above.</p>{/if}{#if error}<p
                class="error-message"
                role="alert"
            >
                {error}
            </p>{/if}
    </section>{/if}
