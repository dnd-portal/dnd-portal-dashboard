<script lang="ts">
    import { onMount } from 'svelte';

    let { message, onDismiss }: { message: string; onDismiss: () => void } = $props();

    onMount(() => {
        const timeout = window.setTimeout(onDismiss, 5000);
        return () => window.clearTimeout(timeout);
    });
</script>

<div class="p-notification p-notification--error" role="alert" aria-live="assertive">
    {message}
</div>
