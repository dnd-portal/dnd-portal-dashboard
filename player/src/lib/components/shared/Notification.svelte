<script lang="ts">
    import { onMount } from 'svelte';
    import Icon from './Icon.svelte';

    type NotificationVariant = 'error' | 'success' | 'warning';
    let {
        message,
        variant = 'error',
        onDismiss
    }: { message: string; variant?: NotificationVariant; onDismiss: () => void } = $props();

    onMount(() => {
        const timeout = window.setTimeout(onDismiss, 5000);
        return () => window.clearTimeout(timeout);
    });
</script>

<div class={`p-notification p-notification--${variant}`} role="alert" aria-live="assertive">
    <Icon name={variant === 'error' ? 'error' : variant === 'success' ? 'success' : 'warning'} size={20} />
    {message}
</div>
