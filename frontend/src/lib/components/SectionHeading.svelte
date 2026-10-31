<script lang="ts">
    import { reveal } from '../animations/reveal';
    type Props = {
        eyebrow: string;
        title: string;
        description: string;
        id: string;
        reveal?: boolean;
    };

    let { eyebrow, title, description, id, reveal: shouldReveal = false }: Props = $props();
</script>

<div class="section-heading" use:reveal={shouldReveal ? {} : undefined}>
    <p class="section-heading__eyebrow eyebrow">{eyebrow}</p>
    <h2 id={id}>{title}</h2>
    <p class="section-heading__description">{description}</p>
</div>
