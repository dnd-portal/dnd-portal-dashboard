<script lang="ts">
    import { onMount } from 'svelte';
    import { goto } from '$app/navigation';
    import { api } from '#lib/api/index.js';
    import AuthLayout from '#lib/components/layouts/AuthLayout.svelte';
    import type { LayoutProps } from './$types';

    let { children }: LayoutProps = $props();
    let checking = $state(true);
    onMount(async () => {
        if (
            await api
                .get('/api/v1/auth/me')
                .then(() => true)
                .catch(() => false)
        )
            await goto('/dashboard');
        checking = false;
    });
</script>

{#if checking}<p class="content-state">Checking access…</p>{:else}<AuthLayout>{@render children()}</AuthLayout>{/if}
