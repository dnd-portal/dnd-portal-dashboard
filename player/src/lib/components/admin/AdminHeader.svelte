<script lang="ts">
    import { adminPageMeta, type AdminPageKey } from '#lib/adminPageMeta.js';

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

<header class="admin-header">
    <p class="admin-header__eyebrow">{displayEyebrow}</p>
    <h1 class="admin-header__title">{displayTitle}</h1>
    <p class="admin-header__subtitle">{displayDescription}</p>
</header>
