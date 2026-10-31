<script lang="ts">
    import { onMount } from "svelte";

    const features = [
        {
            number: "01",
            title: "Your campaign, connected.",
            description:
                "A home for the characters, locations, and stories that make your world worth exploring.",
            tag: "Campaign workspace",
        },
        {
            number: "02",
            title: "Less searching. More playing.",
            description:
                "Bring your session preparation and reference material closer together, so the adventure keeps moving.",
            tag: "Session preparation",
        },
        {
            number: "03",
            title: "Knowledge within reach.",
            description:
                "Keep D&D Portal Wiki a click away whenever a spell, rule, or unexpected player decision needs an answer.",
            tag: "Wiki companion",
        },
    ];

    let diceValue = $state(20);
    let isRolling = $state(false);

    onMount(() => {
        const motionPreference = window.matchMedia(
            "(prefers-reduced-motion: reduce)",
        );

        let timer: ReturnType<typeof setTimeout> | undefined;

        function scheduleRoll() {
            timer = setTimeout(() => {
                isRolling = true;

                // Reveal the new number after both flickers.
                timer = setTimeout(() => {
                    diceValue = Math.floor(Math.random() * 20) + 1;
                    isRolling = false;
                    scheduleRoll();
                }, 600);
            }, 2400);
        }

        function updateAnimation() {
            if (timer !== undefined) clearTimeout(timer);

            isRolling = false;

            if (motionPreference.matches) {
                diceValue = 20;
                return;
            }

            scheduleRoll();
        }

        updateAnimation();
        motionPreference.addEventListener("change", updateAnimation);

        return () => {
            if (timer !== undefined) clearTimeout(timer);
            motionPreference.removeEventListener("change", updateAnimation);
        };
    });
</script>

<svelte:head>
    <title>D&D Portal Dashboard — Your next adventure starts here</title>
    <meta
        name="description"
        content="Meet D&D Portal Dashboard: a campaign workspace in development by Toon van Berkel, inspired by D&D Portal Wiki."
    />
</svelte:head>

<div class="landing">
    <a class="skip-link" href="#main">Skip to content</a>

    <header class="site-header">
        <a class="brand" href="/" aria-label="D&D Portal Dashboard home">
            <img src="/dashboard-dnd-portal-logo-wide.svg" alt="" />
        </a>

        <nav aria-label="Main navigation">
            <a href="#features">The vision</a>
            <a href="#story">Our story</a>
            <a class="nav-cta" href="https://dnd-portal.com">
                Explore the wiki <span aria-hidden="true">↗</span>
            </a>
        </nav>
    </header>

    <main id="main">
        <section class="hero" aria-labelledby="hero-title">
            <div class="hero-copy">
                <p class="eyebrow">
                    <span class="status-dot" aria-hidden="true"></span>
                    A new chapter for D&D Portal
                </p>

                <h1 id="hero-title">
                    Great adventures.<br />
                    <span>One place to begin.</span>
                </h1>

                <p class="hero-description">
                    Your world deserves more than scattered notes. Meet D&D
                    Portal Dashboard — a workspace being built to bring your
                    campaign together, before the dice hit the table.
                </p>

                <div class="hero-actions">
                    <a class="button button-primary" href="#preview">
                        Discover the dashboard <span aria-hidden="true">↗</span
                        >
                    </a>
                    <a class="text-link" href="https://dnd-portal.com">
                        Visit D&D Portal Wiki <span aria-hidden="true">→</span>
                    </a>
                </div>
            </div>

            <div class="hero-art" aria-hidden="true">
                <div class="orbit orbit-outer"></div>
                <div class="orbit orbit-inner"></div>
                <div class="compass-axis"></div>
                <div class="diamond diamond-back"></div>
                <div class="diamond diamond-front"></div>
                <span class="dice-number" class:rolling={isRolling}>
                    {diceValue}
                </span>
                <span class="art-label label-top">IMAGINE A WORLD</span>
                <span class="art-label label-bottom">MAKE IT YOURS</span>
                <span class="star star-one">✦</span>
                <span class="star star-two">✧</span>
                <span class="star star-three">✦</span>
            </div>
        </section>

        <div class="principles" aria-label="Project principles">
            <span>Made for the table</span>
            <span aria-hidden="true">✦</span>
            <span>Inspired by the wiki</span>
            <span aria-hidden="true">✦</span>
            <span>Built by an dm</span>
        </div>

        <section
            id="preview"
            class="preview-section"
            aria-labelledby="preview-title"
        >
            <div class="section-heading">
                <div>
                    <p class="eyebrow">A glimpse of what's ahead</p>
                    <h2 id="preview-title">
                        Meet your campaign's<br />new home.
                    </h2>
                </div>
                <p class="section-description">
                    A calmer place for a world full of chaos. Here's a look at
                    the direction we're exploring.
                </p>
            </div>

            <div class="dashboard-preview">
                <div class="preview-bar">
                    <div class="window-dots" aria-hidden="true">
                        <span></span><span></span><span></span>
                    </div>
                    <span>PORTAL / CAMPAIGN OVERVIEW</span>
                    <span class="concept-badge">Concept preview</span>
                </div>

                <div class="preview-layout">
                    <aside
                        class="preview-sidebar"
                        aria-label="Preview sections"
                    >
                        <span class="mini-brand">D.</span>
                        <span class="sidebar-label">YOUR WORKSPACE</span>
                        <span class="sidebar-item selected">Overview</span>
                        <span class="sidebar-item">Campaigns</span>
                        <span class="sidebar-item">Characters</span>
                        <span class="sidebar-item">Session notes</span>
                        <span class="sidebar-label library-label"
                            >THE LIBRARY</span
                        >
                        <a
                            class="sidebar-item wiki-link"
                            href="https://dnd-portal.com"
                        >
                            D&D Portal Wiki ↗
                        </a>
                    </aside>

                    <div class="preview-content">
                        <div class="preview-welcome">
                            <div>
                                <p class="preview-kicker">
                                    THE NEXT CHAPTER AWAITS
                                </p>
                                <h3>Welcome back, adventurer.</h3>
                            </div>
                            <span class="preview-emblem" aria-hidden="true"
                                >✦</span
                            >
                        </div>

                        <div class="campaign-card">
                            <div>
                                <span class="campaign-label"
                                    >EXAMPLE CAMPAIGN</span
                                >
                                <h4>The Shattered Coast</h4>
                                <p>Every secret has a shore to wash up on.</p>
                            </div>
                            <span class="campaign-symbol" aria-hidden="true"
                                >◈</span
                            >
                        </div>

                        <div class="preview-bottom">
                            <div class="session-card">
                                <span class="preview-kicker">NEXT SESSION</span>
                                <h4>A light in the harbour</h4>
                                <p>The party returns. The mystery deepens.</p>
                                <span class="small-tag">Session 08</span>
                            </div>

                            <div class="notes-card">
                                <span class="preview-kicker"
                                    >BEFORE YOU ROLL</span
                                >
                                <p>
                                    <span aria-hidden="true">✓</span> Review the
                                    last session
                                </p>
                                <p>
                                    <span aria-hidden="true">✓</span> Prepare a few
                                    surprises
                                </p>
                                <p>
                                    <span aria-hidden="true">○</span> Expect the
                                    unexpected
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <p class="preview-caption">
                An illustrative preview of the product vision. Features and
                design will evolve during development.
            </p>
        </section>

        <section
            id="features"
            class="features-section"
            aria-labelledby="features-title"
        >
            <p class="eyebrow">More adventure. Less admin.</p>
            <h2 id="features-title">Keep your focus on the story.</h2>

            <div class="feature-grid">
                {#each features as feature}
                    <article class="feature-card">
                        <span class="feature-number">{feature.number}</span>
                        <h3>{feature.title}</h3>
                        <p>{feature.description}</p>
                        <span class="feature-tag">{feature.tag}</span>
                    </article>
                {/each}
            </div>
        </section>

        <section id="story" class="story-section" aria-labelledby="story-title">
            <div class="story-heading">
                <p class="eyebrow">The origin story</p>
                <h2 id="story-title">
                    First came the wiki.<br />
                    <span>Then came the bigger idea.</span>
                </h2>
            </div>

            <div class="story-copy">
                <p>
                    D&D Portal Dashboard grew out of
                    <a href="https://dnd-portal.com">D&D Portal Wiki</a>: a
                    place for rules, character options, homebrew, and the
                    references that support a great campaign.
                </p>
                <p>
                    The dashboard takes that inspiration a step further. The
                    ambition is simple: give the adventure itself a home,
                    alongside the knowledge that helps bring it to life.
                </p>
                <p>
                    Designed and developed by <strong>Toon van Berkel</strong>,
                    this is an independent project shaped by curiosity,
                    creativity, and a love for tabletop adventures.
                </p>

                <a class="text-link" href="https://dnd-portal.com">
                    Explore where it all started <span aria-hidden="true"
                        >↗</span
                    >
                </a>
            </div>
        </section>

        <section class="closing-section" aria-labelledby="closing-title">
            <p class="eyebrow">Still writing the first chapter</p>
            <h2 id="closing-title">Something worth<br />rolling for.</h2>
            <p>
                The dashboard is in development. Until then, your next discovery
                is waiting in the wiki.
            </p>
            <a class="button button-primary" href="https://dnd-portal.com">
                Explore D&D Portal Wiki <span aria-hidden="true">↗</span>
            </a>
        </section>
    </main>

    <footer class="site-footer">
        <div class="footer-top">
            <a class="brand" href="/" aria-label="D&D Portal Dashboard home">
                <span class="brand-mark" aria-hidden="true">D.</span>
                <span class="brand-name">D&D Portal<span>Dashboard</span></span>
            </a>
            <p>For the worlds you haven't discovered yet.</p>
            <a class="text-link" href="https://dnd-portal.com"
                >Visit the wiki ↗</a
            >
        </div>

        <div class="footer-bottom">
            <span>Created by Toon van Berkel.</span>
            <span>Independent project. Unofficial fan content.</span>
        </div>

        <p class="legal">
            Dungeons & Dragons and related trademarks belong to Wizards of the
            Coast. This project is not affiliated with or endorsed by Wizards of
            the Coast.
        </p>
    </footer>
</div>
