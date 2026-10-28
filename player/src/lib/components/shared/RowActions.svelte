<script lang="ts">
    import { tick } from 'svelte';
    import type { Action } from '#lib/ui/types.js';
    let { label, actions }: { label: string; actions: Action[] } = $props();
    const id = $props.id();
    let panel = $state<HTMLDivElement>(null!);
    let trigger = $state<HTMLButtonElement>(null!);
    let open = $state(false);
    function close(restore = false) {
        panel?.hidePopover();
        if (restore) trigger?.focus();
    }
    async function enter(event: KeyboardEvent) {
        if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
            event.preventDefault();
            if (!panel.matches(':popover-open')) trigger.click();
            await tick();
            const links = panel.querySelectorAll<HTMLElement>('[role="menuitem"]');
            (event.key === 'ArrowUp' ? links[links.length - 1] : links[0])?.focus();
        }
    }
    function navigate(event: KeyboardEvent) {
        const links = [...panel.querySelectorAll<HTMLElement>('[role="menuitem"]')];
        const index = links.indexOf(document.activeElement as HTMLElement);
        if (event.key === 'Escape') {
            event.preventDefault();
            close(true);
        }
        if (['ArrowDown', 'ArrowUp', 'Home', 'End'].includes(event.key)) {
            event.preventDefault();
            const next =
                event.key === 'Home'
                    ? 0
                    : event.key === 'End'
                      ? links.length - 1
                      : (index + (event.key === 'ArrowDown' ? 1 : -1) + links.length) % links.length;
            links[next]?.focus();
        }
    }
</script>

{#if actions.length}
    <button
        bind:this={trigger}
        class="row-actions__trigger"
        type="button"
        popovertarget={id}
        aria-label={`Actions for ${label}`}
        aria-expanded={open}
        aria-controls={id}
        onkeydown={enter}
    >
        <svg width="20" height="20" viewBox="0 0 20 20" aria-hidden="true"
            ><circle cx="4" cy="10" r="1.5" /><circle cx="10" cy="10" r="1.5" /><circle cx="16" cy="10" r="1.5" /></svg
        >
    </button>
    <div
        bind:this={panel}
        class="row-actions__menu"
        {id}
        popover="auto"
        role="menu"
        tabindex="-1"
        aria-label={`Actions for ${label}`}
        ontoggle={(event) => {
            open = event.newState === 'open';
            if (open) panel.querySelector<HTMLElement>('[role="menuitem"]')?.focus();
        }}
        onkeydown={navigate}
        onfocusout={(event) => {
            if (!event.currentTarget.contains(event.relatedTarget as Node) && event.relatedTarget !== trigger) close();
        }}
    >
        {#each actions as action (action.label)}
            {#if action.href}<a
                    class:row-actions__link--danger={action.tone === 'danger'}
                    class="row-actions__link"
                    role="menuitem"
                    tabindex="-1"
                    href={action.href}
                    onclick={() => close()}>{action.label}</a
                >
            {:else}<button
                    type="button"
                    class:row-actions__link--danger={action.tone === 'danger'}
                    class="row-actions__link"
                    role="menuitem"
                    tabindex="-1"
                    onclick={() => {
                        action.onselect?.();
                        close();
                    }}>{action.label}</button
                >{/if}
        {/each}
    </div>
{/if}
