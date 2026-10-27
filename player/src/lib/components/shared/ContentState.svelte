<script lang="ts">
    let { title, description, loading = false }: { title: string; description?: string; loading?: boolean } = $props();
</script>

<div class="p-state content-state" role={loading ? 'status' : undefined}>
    <p class="content-state__title">{title}</p>
    {#if description}<p class="content-state__description">{description}</p>{/if}
</div>
