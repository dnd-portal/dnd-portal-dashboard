<script lang="ts">
    import { onMount } from 'svelte';
    import { api } from '#lib/api/index.js';
    let user = $state<{ display_name: string } | null>(null);
    onMount(async () => {
        user = await api.get('/api/v1/auth/me');
    });
</script>

<svelte:head><title>Dashboard · D&amp;D Portal</title></svelte:head>
<section class="p-page-header">
    <p class="p-eyebrow">D&amp;D PORTAL</p>
    <h1>Welcome{user ? `, ${user.display_name}` : ''}</h1>
    <p>Your global portal workspace.</p>
</section>
<section class="p-card">
    <h2>Current global poll</h2>
    <p>Share your availability or preference with the group.</p>
    <a class="p-button" href="/dashboard/polls/current">Open current poll <span aria-hidden="true">→</span></a>
</section>
