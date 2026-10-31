<script lang="ts">
    import type { Snippet } from 'svelte';

    import Icon from './Icon.svelte';
    import { reveal } from '../animations/reveal';
    import type { IconName } from '../icons/index';

    type CardAction = {
        label: string;
        href: string;
        variant?: 'primary' | 'secondary';
        target?: '_blank' | '_self';
    };

    type Props = {
        eyebrow?: string;
        title: string;
        description?: string;
        icon?: IconName;
        imageSrc?: string;
        imageAlt?: string;
        logoSrc?: string;
        logoAlt?: string;
        compact?: boolean;
        reveal?: boolean;
        revealDelay?: number;
        actions?: CardAction[];
        children?: Snippet;
    };

    let {
        eyebrow,
        title,
        description,
        icon,
        imageSrc,
        imageAlt = '',
        logoSrc,
        logoAlt = '',
        compact = false,
        reveal: shouldReveal = false,
        revealDelay = 0,
        actions = [],
        children
    }: Props = $props();

    const visibleActions = $derived(actions.slice(0, 2));
</script>

<article
    class={compact ? 'card card--compact' : 'card'}
    use:reveal={shouldReveal ? { delay: revealDelay } : undefined}
>
    {#if imageSrc}
        <img class="card__image" src={imageSrc} alt={imageAlt} />
    {/if}

    <div class="card__content">
        {#if logoSrc}
            <div class="card__logo-area">
                <img class="card__logo" src={logoSrc} alt={logoAlt} />
            </div>
        {/if}

        {#if icon}
            <div class="card__icon">
                <Icon name={icon} size={compact ? 40 : 50} />
            </div>
        {/if}

        {#if eyebrow}
            <p class="card__eyebrow eyebrow">{eyebrow}</p>
        {/if}

        <h2 class="card__title">{title}</h2>

        {#if description}
            <p class="card__description">{description}</p>
        {/if}

        {#if children}
            <div class="card__details">
                {@render children()}
            </div>
        {/if}

        {#if visibleActions.length > 0}
            <div class="card__actions">
                {#each visibleActions as action (action.href)}
                    <a
                        class={`button button-${action.variant ?? 'primary'}`}
                        href={action.href}
                        target={action.target}
                        rel={action.target === '_blank' ? 'noopener noreferrer' : undefined}
                    >
                        {action.label}
                    </a>
                {/each}
            </div>
        {/if}
    </div>
</article>
