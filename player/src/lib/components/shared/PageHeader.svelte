<script lang="ts">
    let { eyebrow, title, description }: { eyebrow: string; title: string; description: string } = $props();
</script>

<header class="p-page-header">
    <p class="p-eyebrow">{eyebrow}</p>
    <h1>{title}</h1>
    <p>{description}</p>
</header>
