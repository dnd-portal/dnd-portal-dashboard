<script lang="ts">
    import { beforeNavigate } from '$app/navigation';
    import { onMount } from 'svelte';
    import { api, ApiRequestError } from '#lib/api/index.js';

    export type PlayerPoll = {
        id: number;
        title: string;
        explanation: string | null;
        choice_type: 'single' | 'multiple';
        status: 'open' | 'closed';
        options: { id: number; label: string }[];
        selected_option_ids: number[];
    };

    let { poll }: { poll: PlayerPoll } = $props();
    let selected = $state<number[]>([]);
    let savedSelection = $state<number[]>([]);
    let saving = $state(false);
    let saveStatus = $state<'idle' | 'saving' | 'saved' | 'error'>('idle');
    let saveTimer: ReturnType<typeof setTimeout> | undefined;
    let saveCycle = $state(false);
    let error = $state('');

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
        if (poll.status === 'closed' || !hasChanges() || !selected.length) return;
        saveStatus = 'saving';
        error = '';
        if (saveTimer) clearTimeout(saveTimer);
        saveTimer = setTimeout(() => void save(), 220);
    }
    function toggle(id: number) {
        if (selected.length === 1 && selected[0] === id) {
            error = 'Select at least one date.';
            return;
        }
        selected = selected.includes(id) ? selected.filter((x) => x !== id) : [...selected, id];
        scheduleSave();
    }
    async function save() {
        if (poll.status === 'closed' || !selected.length || !hasChanges() || saveCycle) return;
        saveCycle = true;
        saving = true;
        const target = [...selected];
        try {
            const response = await api.put<PlayerPoll>(`/api/v1/global-polls/${poll.id}/response`, {
                option_ids: target
            });
            if (response.id === poll.id) savedSelection = target;
            saveStatus = 'saved';
            error = '';
        } catch (cause) {
            saveStatus = 'error';
            error = cause instanceof ApiRequestError ? cause.message : 'Could not save your answer.';
        } finally {
            saveCycle = false;
            saving = false;
            if (hasChanges() && saveStatus !== 'error') scheduleSave();
        }
    }
    onMount(() => {
        selected = [...poll.selected_option_ids];
        savedSelection = [...poll.selected_option_ids];
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

<section class="p-page-header poll-page-header">
    <p class="p-eyebrow">GLOBAL POLL</p>
    <h1>{poll.title}</h1>
    <p>{poll.explanation ?? 'Choose the dates that work best for you.'}</p>
</section>
<section class="p-card poll-card" class:poll-card--closed={poll.status === 'closed'}>
    <fieldset disabled={poll.status === 'closed' || saving}>
        <legend>Select all dates that work for you.</legend>
        <div class="poll-options">
            {#each poll.options as option}<label
                    class="poll-option"
                    class:poll-option--selected={selected.includes(option.id)}
                >
                    <input
                        type="checkbox"
                        name={`poll-${poll.id}`}
                        checked={selected.includes(option.id)}
                        onchange={() => toggle(option.id)}
                    />
                    <span class="poll-option__label">{option.label}</span>
                </label>{/each}
        </div>
    </fieldset>
    {#if poll.status === 'open'}<div class="poll-save-status" aria-live="polite">
            {#if saveStatus === 'saving'}<span>Saving…</span>{:else if saveStatus === 'saved'}<span>Saved</span
                >{:else if saveStatus === 'error'}<button
                    class="p-button p-button--secondary poll-retry"
                    type="button"
                    onclick={scheduleSave}>Try again</button
                >{/if}
        </div>{:else}<p role="status">This poll is closed. Your saved answer is shown above.</p>{/if}
    {#if error}<p class="error-message" role="alert">{error}</p>{/if}
</section>
