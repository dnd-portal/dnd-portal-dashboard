<script lang="ts">
    import { tick } from 'svelte';

    type Option = { value: string; label: string; disabled?: boolean };

    let {
        id,
        label,
        options,
        value,
        onchange,
        disabled = false
    }: {
        id: string;
        label: string;
        options: Option[];
        value: string;
        onchange: (value: string) => void;
        disabled?: boolean;
    } = $props();

    const popoverId = $derived(`${id}-menu`);
    let trigger = $state<HTMLButtonElement>(null!);
    let menu = $state<HTMLDivElement>(null!);
    let open = $state(false);
    let activeIndex = $state(0);
    let search = '';
    let searchTimer: ReturnType<typeof setTimeout>;

    const selectedOption = $derived(options.find((option) => option.value === value) ?? options[0]);
    const availableIndexes = $derived(
        options.map((option, index) => (option.disabled ? -1 : index)).filter((index) => index >= 0)
    );

    function firstAvailable() {
        return availableIndexes[0] ?? 0;
    }

    function lastAvailable() {
        return availableIndexes.at(-1) ?? 0;
    }

    function nextAvailable(index: number, direction: 1 | -1) {
        const current = availableIndexes.indexOf(index);
        if (current === -1) return firstAvailable();
        return availableIndexes[(current + direction + availableIndexes.length) % availableIndexes.length];
    }

    function updatePosition() {
        if (!trigger || !menu) return;
        const rect = trigger.getBoundingClientRect();
        const menuHeight = Math.min(menu.scrollHeight, 280);
        const gap = 4;
        const top =
            rect.bottom + gap + menuHeight <= window.innerHeight ? rect.bottom + gap : rect.top - gap - menuHeight;
        const left = Math.min(Math.max(8, rect.left), window.innerWidth - rect.width - 8);
        menu.style.setProperty('--dropdown-top', `${top}px`);
        menu.style.setProperty('--dropdown-left', `${left}px`);
        menu.style.setProperty('--dropdown-width', `${rect.width}px`);
    }

    function scrollActiveIntoView() {
        menu?.querySelector<HTMLElement>('[aria-selected="true"], [data-active="true"]')?.scrollIntoView({
            block: 'nearest'
        });
    }

    async function openMenu() {
        if (disabled || !menu) return;
        activeIndex = Math.max(
            0,
            options.findIndex((option) => option.value === value)
        );
        if (options[activeIndex]?.disabled) activeIndex = firstAvailable();
        menu.showPopover();
        await tick();
        updatePosition();
        menu.focus();
        scrollActiveIntoView();
    }

    function closeMenu(restoreFocus = true) {
        menu?.hidePopover();
        if (restoreFocus) trigger?.focus();
    }

    function confirm() {
        const option = options[activeIndex];
        if (!option || option.disabled) return;
        if (option.value !== value) onchange(option.value);
        closeMenu();
    }

    function move(direction: 1 | -1) {
        activeIndex = nextAvailable(activeIndex, direction);
        scrollActiveIntoView();
    }

    function typeahead(character: string) {
        search += character.toLowerCase();
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => (search = ''), 500);
        const match = options.findIndex((option) => !option.disabled && option.label.toLowerCase().startsWith(search));
        if (match >= 0) {
            activeIndex = match;
            scrollActiveIntoView();
        }
    }
</script>

<svelte:window onresize={() => open && updatePosition()} onscroll={() => open && updatePosition()} />

<div class="dropdown-select">
    <span class="dropdown-select__label" id={`${id}-label`}>{label}</span>
    <button
        bind:this={trigger}
        class="dropdown-select__trigger"
        type="button"
        aria-labelledby={`${id}-label ${id}-value`}
        aria-haspopup="listbox"
        aria-controls={popoverId}
        aria-expanded={open}
        aria-disabled={disabled}
        {disabled}
        onclick={openMenu}
        onkeydown={(event) => {
            if (['ArrowDown', 'ArrowUp', 'Enter', ' '].includes(event.key)) {
                event.preventDefault();
                if (!open) openMenu();
                else if (event.key === 'ArrowDown') move(1);
                else if (event.key === 'ArrowUp') move(-1);
                else confirm();
            }
            if (event.key === 'Home' && open) {
                event.preventDefault();
                activeIndex = firstAvailable();
                scrollActiveIntoView();
            }
            if (event.key === 'End' && open) {
                event.preventDefault();
                activeIndex = lastAvailable();
                scrollActiveIntoView();
            }
            if (event.key.length === 1 && /\S/.test(event.key) && open) typeahead(event.key);
        }}
    >
        <span id={`${id}-value`} class="dropdown-select__value">{selectedOption?.label}</span>
        <span class="dropdown-select__chevron" aria-hidden="true"></span>
    </button>
    <div
        bind:this={menu}
        class="dropdown-select__menu"
        id={popoverId}
        popover="auto"
        role="listbox"
        tabindex="-1"
        aria-labelledby={`${id}-label`}
        ontoggle={(event) => {
            open = event.newState === 'open';
            if (open) {
                updatePosition();
                scrollActiveIntoView();
            }
        }}
        onkeydown={(event) => {
            if (event.key === 'ArrowDown') {
                event.preventDefault();
                move(1);
            } else if (event.key === 'ArrowUp') {
                event.preventDefault();
                move(-1);
            } else if (event.key === 'Home') {
                event.preventDefault();
                activeIndex = firstAvailable();
                scrollActiveIntoView();
            } else if (event.key === 'End') {
                event.preventDefault();
                activeIndex = lastAvailable();
                scrollActiveIntoView();
            } else if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                confirm();
            } else if (event.key.length === 1 && /\S/.test(event.key)) {
                typeahead(event.key);
            }
        }}
        onfocusout={(event) => {
            if (!menu.contains(event.relatedTarget as Node) && event.relatedTarget !== trigger) closeMenu(false);
        }}
    >
        {#each options as option, index (option.value)}
            <button
                class="dropdown-select__option"
                class:dropdown-select__option--active={index === activeIndex}
                class:dropdown-select__option--selected={option.value === value}
                type="button"
                role="option"
                aria-selected={option.value === value}
                aria-disabled={option.disabled || undefined}
                data-active={index === activeIndex}
                disabled={option.disabled}
                onclick={() => {
                    activeIndex = index;
                    confirm();
                }}
                onmouseenter={() => {
                    if (!option.disabled) activeIndex = index;
                }}
            >
                <span>{option.label}</span>
                {#if option.value === value}<span class="dropdown-select__check" aria-hidden="true">✓</span>{/if}
            </button>
        {/each}
    </div>
</div>
