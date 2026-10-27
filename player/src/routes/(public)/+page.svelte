<script lang="ts">
    import { onMount } from 'svelte';
    import { api } from '#lib/api/index.js';

    let signedIn = $state(false);
    onMount(async () => {
        signedIn = await api
            .get('/api/v1/auth/me')
            .then(() => true)
            .catch(() => false);
    });
</script>

<svelte:head>
    <title>D&amp;D Portal</title>
    <meta name="description" content="A calm home for your D&D campaign sessions, party and shared expenses." />
</svelte:head>

<main class="p-auth p-public-home">
    <a class="p-auth__brand" href="/" aria-label="D&D Portal">
        <img src="/dashboard-dnd-portal-logo-wide.svg" alt="D&D Portal" width="176" height="48" />
    </a>
    <section class="p-card p-public-home__panel">
        <p class="p-eyebrow">D&amp;D PORTAL</p>
        <h1>Everything your campaign needs, in one place.</h1>
        <p class="p-card__description">
            Keep sessions, party coordination, polls and shared expenses together without losing the story.
        </p>
        <div class="p-public-home__actions">
            <a class="p-button" href={signedIn ? '/dashboard' : '/login'}
                >{signedIn ? 'Open dashboard' : 'Sign in'} <span aria-hidden="true">→</span></a
            >
            <a class="p-button p-button--secondary" href="/register">Join with an invite</a>
        </div>
    </section>
</main>
