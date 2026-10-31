<script lang="ts">
    import { reveal } from '../animations/reveal';
    export type StepItem = {
        title: string;
        description: string;
    };

    type Props = {
        items: StepItem[];
        reveal?: boolean;
    };

    let { items, reveal: shouldReveal = false }: Props = $props();
</script>

<ol class="steps">
    {#each items as item, index (item.title)}
        <li class="steps__item" use:reveal={shouldReveal ? { delay: index * 60 } : undefined}>
            <div class="steps__content">
                <h3>{item.title}</h3>
                <p>{item.description}</p>
            </div>
        </li>
    {/each}
</ol>
