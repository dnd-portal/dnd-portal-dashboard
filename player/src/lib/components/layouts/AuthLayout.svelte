<script lang="ts">
    import type { Snippet } from 'svelte';
    let { title, description, children }: { title: string; description: string; children: Snippet } = $props();
</script>

<main class="p-auth">
    <section class="p-card p-auth__panel">
        <a class="p-auth__brand" href="/" aria-label="D&D Portal"
            ><img src="/dashboard-dnd-portal-logo-wide.svg" alt="D&D Portal" width="110" height="48" /></a
        >
        <header class="p-auth__header">
            <p class="p-eyebrow">D&amp;D PORTAL PLAYER DASHBOARD</p>
            <h1>{title}</h1>
            <p>{description}</p>
        </header>
        {@render children()}
    </section>
</main>
