<script lang="ts">
    import { page } from '$app/state';

    type Props = {
        labels?: Record<string, string>;
    };

    type Breadcrumb = {
        label:  string;
        href:   string;
    };

    let {
        labels = {}
    }: Props = $props();

    function formatLabel(segment: string): string {
        let decoded = segment;

        try {
            decoded = decodeURIComponent(segment);
        } catch {
            // Keep the original segment if URL encoding is invalid.
        }

        return decoded
            .replace(/[-_]+/g, ' ')
            .replace(/\b\w/g, (letter) => letter.toUpperCase());
    }

    const breadcrumbs = $derived.by(() => {
        const segments = page.url.pathname.split('/').filter(Boolean);

        const items: Breadcrumb[] = [
            {
                label: labels['/'] ?? 'Home',
                href: '/'
            }
        ];

        segments.forEach((segment, index) => {
            const href = '/' + segments.slice(0, index + 1).join('/');

            items.push({
                label: labels[href] ?? formatLabel(segment),
                href
            });
        });

        return items;
    });
</script>

<nav class="breadcrumbs" aria-label="Breadcrumb">
    <ol>
        {#each breadcrumbs as breadcrumb, index (breadcrumb.href)}
            <li>
                {#if index > 0}
                    <span class="breadcrumbs__separator" aria-hidden="true">/</span>
                {/if}

                {#if index === breadcrumbs.length - 1}
                    <span class="breadcrumbs__current" aria-current="page">{breadcrumb.label}</span>
                {:else}
                    <a href={breadcrumb.href}>{breadcrumb.label}</a>
                {/if}
            </li>
        {/each}
    </ol>
</nav>