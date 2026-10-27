<script lang="ts">
    import { onMount } from 'svelte';
    import Icon from './Icon.svelte';

    type NotificationVariant = 'error' | 'success' | 'warning';
    let notification: HTMLDivElement;
    let {
        message,
        variant = 'error',
        onDismiss
    }: { message: string; variant?: NotificationVariant; onDismiss: () => void } = $props();

    onMount(() => {
        document.body.appendChild(notification);
        const timeout = window.setTimeout(onDismiss, 5000);
        return () => {
            window.clearTimeout(timeout);
            notification.remove();
        };
    });
</script>

<div bind:this={notification} class={`p-notification p-notification--${variant}`} role="alert" aria-live="assertive">
    <Icon name={variant === 'error' ? 'error' : variant === 'success' ? 'success' : 'warning'} size={20} />
    {message}
</div>
