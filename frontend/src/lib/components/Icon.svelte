<script lang="ts">
    import { icons, type IconName } from '../icons/index';

    type Props = {
        name: IconName;
        size?: number;
        label?: string;
    };

    let { name, size = 24, label }: Props = $props();

    const paths = $derived(icons[name]);

    // Assets are normalized at source; the loader must not rescale them again.
    const normalizedPaths = $derived(paths);
</script>

<span
    class="icon"
    style={`width: ${size}px; height: ${size}px`}
    aria-hidden={label ? undefined : 'true'}
    aria-label={label}
    role={label ? 'img' : undefined}
>
    {@html normalizedPaths}
</span>

<style>
    .icon {
        display: inline-block;
        flex: 0 0 auto;
        line-height: 0;
    }

    .icon :global(svg) {
        display: block;
        width: 100%;
        height: 100%;
    }
</style>
