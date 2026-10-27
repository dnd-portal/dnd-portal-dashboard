<script lang="ts">
    import { page } from '$app/state';
    import AuthLayout from '#lib/components/player/AuthLayout.svelte';

    const copy: Record<number, { title: string; description: string }> = {
        400: { title: 'Request could not be understood', description: 'Check the information and try again.' },
        401: { title: 'Please sign in again', description: 'Your session is no longer active.' },
        403: { title: 'Access denied', description: 'You do not have permission to view this page.' },
        404: { title: 'Page not found', description: 'The page may have moved or the link may be outdated.' },
        405: { title: 'Action not available', description: 'That action is not available here.' },
        409: {
            title: 'This changed while you were working',
            description: 'Reload the page to see the current version before trying again.'
        },
        419: { title: 'Security check expired', description: 'Sign in again before repeating the action.' },
        422: { title: 'Some information needs attention', description: 'Review the highlighted fields and try again.' },
        429: { title: 'Too many requests', description: 'Please wait a moment before trying again.' },
        500: { title: 'Something went wrong', description: 'The portal could not complete this request.' },
        502: {
            title: 'Service temporarily unavailable',
            description: 'The connected service did not respond correctly.'
        },
        503: { title: 'Portal temporarily unavailable', description: 'Please try again shortly.' }
    };
    const status = $derived(page.status || 500);
    const message = $derived(
        copy[status] ?? { title: 'Something went wrong', description: 'Please try again or return to the dashboard.' }
    );
</script>

<svelte:head><title>{message.title} | D&D Portal</title></svelte:head>

<AuthLayout title={message.title} description={message.description}>
    <div class="error-actions">
        {#if status === 401 || status === 419}<a class="primary-button" href="/login">Sign in again</a>{:else}<button
                class="primary-button"
                type="button"
                onclick={() => location.reload()}>Try again</button
            >{/if}
        <a class="secondary-button" href="/">Return to the portal</a>
    </div>
</AuthLayout>
