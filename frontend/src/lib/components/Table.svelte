<script lang="ts">
    import { onMount, type Snippet } from 'svelte';

    type Props = {
        caption: string;
        header: Snippet;
        children: Snippet;
    };

    let { caption, header, children }: Props = $props();

    let container: HTMLDivElement;
    let table: HTMLTableElement;

    let isScrollable = $state(false);
    let canScrollLeft = $state(false);
    let canScrollRight = $state(false);

    function updateScrollState() {
        const maxScroll = container.scrollWidth - container.clientWidth;

        isScrollable = maxScroll > 1;
        canScrollLeft = container.scrollLeft > 1;
        canScrollRight = container.scrollLeft < maxScroll - 1;
    }

    function scrollTable(direction: -1 | 1) {
        container.scrollBy({
            left: direction * container.clientWidth * 0.75,
            behavior: 'instant'
        });

        updateScrollState();
    }

    onMount(() => {
        const observer = new ResizeObserver(updateScrollState);

        observer.observe(container);
        observer.observe(table);
        updateScrollState();

        return () => observer.disconnect();
    });
</script>

<div class="table-wrapper">
    {#if isScrollable}
        <div
            class="table-scroll-controls"
            role="group"
            aria-label={`Horizontal scrolling for ${caption}`}
        >
            <p>More columns are available. Use the buttons to scroll.</p>

            <button
                type="button"
                disabled={!canScrollLeft}
                onclick={() => scrollTable(-1)}
            >
                <span aria-hidden="true">←</span>
                Scroll left
            </button>

            <button
                type="button"
                disabled={!canScrollRight}
                onclick={() => scrollTable(1)}
            >
                Scroll right
                <span aria-hidden="true">→</span>
            </button>
        </div>
    {/if}

    <div
        bind:this={container}
        class="data-table"
        onscroll={updateScrollState}
    >
        <table bind:this={table}>
            <caption>{caption}</caption>

            <thead>
                {@render header()}
            </thead>

            <tbody>
                {@render children()}
            </tbody>
        </table>
    </div>
</div>