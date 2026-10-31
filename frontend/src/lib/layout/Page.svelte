<script lang="ts">
    import type { Snippet } from 'svelte';
    import Breadcrumbs from '#lib/components/Breadcrumbs.svelte';

    type BannerLink = {
        ariaLabel?:     string;
        goto:           string;
        variant?:       'primary' | 'secondary';
        name:           string;
    };

    type Props = {
        bannerIsImageBased?:        boolean;
        hasAnImageOnTheRight?:      boolean;
        showBreadcrumbs?:           boolean;

        eyebrow?:                   string;
        title:                      string;
        description?:               string;
        buttons?:                   BannerLink[];

        backgroundImage?:           string;
        imageSrc?:                  string;
        imageAlt?:                  string;

        children?:                  Snippet;
    };

    let {
        bannerIsImageBased      = false,
        hasAnImageOnTheRight    = false,
        showBreadcrumbs         = false,

        eyebrow,
        title,
        description,
        buttons = [],

        backgroundImage,
        imageSrc,
        imageAlt = '',

        children
    }: Props = $props();
</script>

<div class="page">
    <header 
        class="page-banner" 
        class:page-banner--image-based={bannerIsImageBased && !!backgroundImage} 
        class:page-banner--split={hasAnImageOnTheRight && !!imageSrc}
    >
        {#if bannerIsImageBased && backgroundImage}
            <img class="page-banner__background" src={backgroundImage} alt="" />
        {/if}

        <div class="page-banner__content">
            {#if showBreadcrumbs}
                <Breadcrumbs />
            {/if}

            {#if eyebrow}
                <p class="page-banner__eyebrow">{eyebrow}</p>
            {/if}

            <h1 class="page-banner__title">{title}</h1>

            {#if description}
                <p class="page-banner__description">{description}</p>
            {/if}

            {#if buttons.length > 0}
                <div class="page-banner__actions">
                    {#each buttons as button}
                        <a 
                            class="button" 
                            class:button--primary={button.variant !== 'secondary'} 
                            class:button--secondary={button.variant === 'secondary'} 
                            href={button.goto} 
                            aria-label={button.ariaLabel}
                        >
                            {button.name}
                        </a>
                    {/each}
                </div>
            {/if}
        </div>

        {#if hasAnImageOnTheRight && imageSrc}
            <img class="page-banner__image" src={imageSrc} alt={imageAlt} />
        {/if}
    </header>

    {#if children}
        <div class="page-content">
            {@render children()}
        </div>
    {/if}
</div>