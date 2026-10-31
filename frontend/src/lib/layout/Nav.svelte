<script lang="ts">
    import { afterNavigate } from "$app/navigation";

    let menuOpen = $state(false);

    const links = [
        { label: "Features", href: "/features" },
        { label: "How it works", href: "/how-it-works" },
        { label: "About", href: "/about" },
        { label: "Help", href: "/help" },
    ];

    function closeMenu() {
        menuOpen = false;
    }

    afterNavigate(closeMenu);
</script>

<svelte:window onkeydown={(event) => event.key === "Escape" && closeMenu()} />

<header class="site-header">
    <div class="site-header__inner">
        <a
            class="site-header__brand"
            href="/"
            aria-label="D&D Portal Dashboard home"
        >
            <img
                src="/dashboard-dnd-portal-logo-wide.svg"
                alt="D&D Portal Dashboard"
            />
        </a>

        <button
            class="site-header__menu-toggle"
            type="button"
            aria-expanded={menuOpen}
            aria-controls="public-navigation"
            onclick={() => (menuOpen = !menuOpen)}
        >
            <span class="sr-only">Toggle navigation</span>
            <span aria-hidden="true">☰</span>
        </button>

        <nav
            id="public-navigation"
            class:site-header__nav--open={menuOpen}
            class="site-header__nav"
            aria-label="Main navigation"
        >
            <ul class="site-header__links">
                {#each links as link (link.href)}
                    <li>
                        <a class="site-header__link" href={link.href}
                            >{link.label}</a
                        >
                    </li>
                {/each}
                <li>
                    <a
                        class="site-header__link"
                        href="https://dnd-portal.com/"
                        target="_blank"
                        rel="noopener noreferrer"
                    >
                        Wiki <span aria-hidden="true">↗</span>
                    </a>
                </li>
            </ul>

            <div class="site-header__actions">
                <a class="button button-secondary" href="/login">Log in</a>
                <a class="button button-primary" href="/register"
                    >Use invitation</a
                >
            </div>
        </nav>
    </div>
</header>
