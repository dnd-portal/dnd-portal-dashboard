<script lang="ts">
    import type { Snippet } from 'svelte';
    let { title, description, children }: { title: string; description: string; children: Snippet } = $props();
</script>

<main class="register-shell">
    <section class="register-card">
        <a class="auth-brand" href="/" aria-label="D&D Portal"
            ><img src="/dashboard-dnd-portal-logo-wide.svg" alt="D&D Portal" width="110" height="48" /></a
        >
        <p class="eyebrow">D&amp;D PORTAL PLAYER DASHBOARD</p>
        <h1>{title}</h1>
        <p class="intro">{description}</p>
        {@render children()}
    </section>
</main>
