<script lang="ts">
    import type { Tone } from '#lib/ui/types.js';
    let { text, tone = 'neutral' }: { text: string; tone?: Tone } = $props();
</script>

<span
    class="status-label"
    class:status-label--accent={tone === 'accent'}
    class:status-label--success={tone === 'success'}
    class:status-label--warning={tone === 'warning'}
>
    <span class="status-label__dot" aria-hidden="true"></span>{text}
</span>
