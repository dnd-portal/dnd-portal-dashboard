<script lang="ts">
    import { adminPageMeta, type AdminPageKey } from '#lib/adminPageMeta.js';
    import PageHeader from '#lib/components/shared/PageHeader.svelte';

    let {
        pageKey,
        eyebrow,
        title,
        subtitle
    }: {
        pageKey?: AdminPageKey;
        eyebrow?: string;
        title?: string;
        subtitle?: string;
    } = $props();

    let metadata = $derived(pageKey ? adminPageMeta[pageKey] : undefined);
    let displayEyebrow = $derived(eyebrow ?? metadata?.eyebrow ?? 'Admin - D&D Portal Player Dashboard');
    let displayTitle = $derived(title ?? metadata?.title ?? 'Admin');
    let displayDescription = $derived(subtitle ?? metadata?.description ?? 'Manage your D&D Portal workspace.');
</script>

<PageHeader eyebrow={displayEyebrow} title={displayTitle} description={displayDescription} />
