<script lang="ts">
    import { onMount } from 'svelte';
    import type { Snippet } from 'svelte';
    let { children }: { children: Snippet } = $props();

    onMount(() => {
        document.body.classList.add('auth-page');

        return () => document.body.classList.remove('auth-page');
    });
</script>

<main class="p-auth">
    <section class="p-card p-auth__panel">
        <a class="p-auth__brand" href="/" aria-label="D&D Portal"
            ><img src="/dashboard-dnd-portal-logo-wide.svg" alt="D&D Portal" width="110" height="48" /></a
        >
        <p class="p-eyebrow">D&amp;D PORTAL PLAYER DASHBOARD</p>
        {@render children()}
    </section>
</main>
