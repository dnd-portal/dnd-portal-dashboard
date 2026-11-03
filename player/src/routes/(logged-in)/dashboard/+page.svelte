<script lang="ts">
    import { goto } from '$app/navigation';
    import { page } from '$app/state';
    import { onMount } from 'svelte';
    import { api, ApiRequestError } from '#lib/api/index.js';
    import { redirectToLogin } from '#lib/auth/redirect.js';
    let user = $state<{ display_name: string } | null>(null);
    let error = $state('');
    onMount(async () => {
        try {
            user = await api.get('/api/v1/auth/me');
        } catch (cause) {
            if (cause instanceof ApiRequestError && cause.status === 401) {
                error = (await redirectToLogin(goto, page.url.pathname, page.url.search)) ?? '';
                return;
            }
            error = cause instanceof ApiRequestError ? cause.message : 'Unable to load your account.';
        }
    });
</script>

<svelte:head><title>Dashboard · D&amp;D Portal</title></svelte:head>
<section class="p-page-header">
    <p class="p-eyebrow">D&amp;D PORTAL</p>
    <h1>Welcome back</h1>
    {#if user}<p class="p-dashboard__welcome-user">{user.display_name}</p>{/if}
    <p>Your global portal workspace.</p>
</section>
{#if error}<p class="error-message" role="alert">{error}</p>{/if}
<section class="p-card dashboard-poll-card">
    <div>
        <h2>Current global poll</h2>
        <p>Share your availability or preference with the group.</p>
    </div>
    <footer class="dashboard-poll-card__footer">
        <a class="p-button" href="/dashboard/polls">View open polls <span aria-hidden="true">→</span></a>
    </footer>
</section>
