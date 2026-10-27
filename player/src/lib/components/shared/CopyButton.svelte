<script lang="ts">
    let { value, label = 'Copy code' }: { value: string; label?: string } = $props();
    let copied = $state(false);
    async function copy() {
        try {
            await navigator.clipboard.writeText(value);
            copied = true;
            setTimeout(() => (copied = false), 1500);
        } catch {
            copied = false;
        }
    }
</script>

<button type="button" class="secondary-button" disabled={!value} onclick={copy}>{copied ? 'Copied' : label}</button>
