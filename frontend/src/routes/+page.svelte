<script lang="ts">
    import Card from "#lib/components/Card.svelte";
    import FAQ from "#lib/components/FAQ.svelte";
    import SectionHeading from "#lib/components/SectionHeading.svelte";
    import Steps from "#lib/components/Steps.svelte";
    import type { IconName } from "../lib/icons/index";
    import { reveal } from "../lib/animations/reveal";

    const homepageFAQs = [
        { question: "What is D&D Portal Dashboard?", answer: "It is a private campaign hub for organizing players, sessions, polls, availability and practical campaign information." },
        { question: "Do I need an invitation?", answer: "Yes. New accounts can only be created using a personal invitation provided by an organizer." },
        { question: "I already had an account. Do I need to register again?", answer: "No. Existing accounts can continue to use their transferred login details." },
        { question: "What should I do if I forgot my password?", answer: "Ask your organizer for a personal password-reset link. Reset codes are temporary and should not be shared." },
        { question: "Can anyone view my campaign?", answer: "Campaign information is available only through authenticated accounts with access to the relevant portal features." },
        { question: "What is the difference between Dashboard and Wiki?", answer: "The Dashboard contains campaign tools and private player information. The Wiki contains public rules, classes, spells, homebrew and reference material." },
        { question: "Does the dashboard work on mobile?", answer: "Yes. The player dashboard and administration interfaces are designed for desktop and mobile devices." }
    ];

    const homepageSteps = [
        {
            title: "Receive your invitation",
            description: "Your organizer sends you a personal registration link or directs you to the login page if you already have an account."
        },
        {
            title: "Join your campaign",
            description: "Create your account, open your dashboard and find the campaigns and information connected to you."
        },
        {
            title: "Prepare together",
            description: "Answer polls, share your availability and stay informed about upcoming sessions."
        }
    ];

    const featureCards: { icon: IconName; title: string; description: string }[] = [
        {
            icon: "calendar",
            title: "Plan your next session",
            description: "Compare everyone’s availability through polls and keep upcoming session details in one place."
        },
        {
            icon: "users",
            title: "Keep your party connected",
            description: "Find your campaigns, party members and shared information without searching through old messages."
        },
        {
            icon: "wallet",
            title: "Keep track of shared costs",
            description: "See payment requests and what you still owe, so practical arrangements stay clear."
        },
        {
            icon: "settings",
            title: "Organize your campaign",
            description: "Give organizers a central place to invite players, manage campaigns and sessions, and review poll responses."
        }
    ];
</script>

<svelte:head>
    <title>D&amp;D Portal Dashboard — Your campaign, connected</title>
    <meta
        name="description"
        content="A private campaign hub for sessions, polls, availability, campaign information and shared expenses."
    />
</svelte:head>

<main>
    <section class="page-banner" aria-labelledby="hero-title">
        <div class="page-banner__content">
            <div class="page-banner__layout">
                <div class="page-banner__text">
                    <p class="eyebrow hero-reveal" use:reveal={{ delay: 0 }}>D&amp;D PORTAL DASHBOARD</p>
                    <h1 id="hero-title" class="hero-reveal" use:reveal={{ delay: 70 }}>Your campaign, connected.</h1>
                    <p class="hero-description hero-reveal" use:reveal={{ delay: 140 }}>
                        Plan sessions, answer availability polls, manage your
                        campaign and keep your party informed from one shared
                        dashboard.
                    </p>
                    <p class="hero-description hero-reveal" use:reveal={{ delay: 210 }}>
                        Keep the practical details together, so your group can
                        spend more time making decisions, discovering the world
                        and playing the adventure.
                    </p>
                    <div class="cta-row hero-reveal" use:reveal={{ delay: 280 }}>
                        <a class="button button-primary" href="/register"
                            >Use your invitation</a
                        >
                        <a class="button button-secondary" href="/login"
                            >Log in</a
                        >
                    </div>
                </div>
                <div class="page-banner__art" aria-hidden="true">
                    <div class="hero-art-reveal" use:reveal={{ delay: 120, className: 'hero-art' }}>
                        <img src="/dashboard-dnd-portal-icon.svg" alt="" />
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="page-content" aria-labelledby="intro-title">
        <SectionHeading reveal
            eyebrow="ONE SHARED PLACE"
            title="Everything your campaign needs, within reach."
            description="D&amp;D Portal Dashboard is a private campaign hub for players and organizers. It brings session planning, availability, polls, campaign information and shared expenses together in one place."
            id="intro-title"
        />
        <div class="info-grid intro-cards">
            <Card reveal revealDelay={0}
                icon="lock"
                title="Private by invitation"
                description="Only invited players can create an account and join the portal."
            />
            <Card reveal revealDelay={60}
                icon="compass"
                title="Built around campaigns"
                description="Sessions, players, polls and practical details remain connected to the campaigns they belong to."
            />
            <Card reveal revealDelay={120}
                icon="book"
                title="Connected to the wiki"
                description="Rules, classes, spells, homebrew and campaign references are always close by."
            />
        </div>
    </section>

    <section class="page-content how-it-works-section" aria-labelledby="how-title">
        <SectionHeading reveal
            eyebrow="HOW IT WORKS"
            title="From invitation to adventure."
            description="Getting started takes just a few steps, from your personal invitation to preparing for your next session."
            id="how-title"
        />
        <Steps items={homepageSteps} />
        <a class="text-link" href="/how-it-works"
            >See how D&amp;D Portal works →</a
        >
    </section>

    <section class="page-content" aria-labelledby="features-title">
        <SectionHeading reveal
            eyebrow="FEATURES"
            title="Less organizing. More adventuring."
            description="Bring planning, campaign information and practical arrangements together, so players and organizers can focus on the next session."
            id="features-title"
        />
        <div class="info-grid feature-cards">
            {#each featureCards as card, index (card.title)}
                <Card compact reveal revealDelay={index * 60} icon={card.icon} title={card.title} description={card.description} />
            {/each}
        </div>
        <a class="text-link" href="/features">Explore all features →</a>
    </section>

    <section class="page-content" aria-labelledby="products-title">
        <SectionHeading reveal
            eyebrow="TWO SIDES OF D&amp;D PORTAL"
            title="Campaign tools and campaign knowledge."
            description="Use the Dashboard to organize your campaign and the Wiki to explore the rules, character options and references that support your adventures."
            id="products-title"
        />
        <div class="info-grid">
            <Card reveal revealDelay={0}
                logoSrc="/dashboard-dnd-portal-logo-wide.svg"
                logoAlt="D&D Portal Dashboard"
                eyebrow="THE CAMPAIGN"
                title="Give your adventure a home."
                description="D&amp;D Portal Dashboard handles the living campaign: its players, sessions, planning, decisions and practical details."
                actions={[{ label: "Explore dashboard features", href: "/features", variant: "secondary" }]}
            />
            <Card reveal revealDelay={60}
                logoSrc="/dnd-portal-logo-wide.svg"
                logoAlt="D&D Portal Wiki"
                eyebrow="THE KNOWLEDGE"
                title="The wiki behind the adventure."
                description="D&amp;D Portal Wiki contains rules, classes, spells, homebrew and other references that help players prepare and keep the game moving."
                actions={[{ label: "Explore D&D Portal Wiki ↗", href: "https://dnd-portal.com/", target: "_blank", variant: "secondary" }]}
            />
        </div>
    </section>

    <section class="privacy-section" aria-labelledby="privacy-title" use:reveal={{ className: 'privacy-bg' }}>
        <div class="privacy-section__inner">
            <SectionHeading reveal
                eyebrow="PRIVATE BY DESIGN"
                title="Your campaign stays with your party."
                description="D&amp;D Portal Dashboard is invitation-only. Accounts are protected with personal invitation codes, secure sessions and controlled password-reset links."
                id="privacy-title"
            />
            <div class="cta-row">
                <a class="text-link" href="/security">Read about security →</a><a
                    class="text-link"
                    href="/privacy">Privacy information →</a
                >
            </div>
        </div>
    </section>

    <section class="page-content" aria-labelledby="faq-title">
        <SectionHeading reveal
            eyebrow="FAQ"
            title="Questions before your next adventure?"
            description="Find concise answers about invitations, accounts, campaign access, password resets and the relationship between the Dashboard and Wiki."
            id="faq-title"
        />
        <FAQ items={homepageFAQs} />
        <a class="text-link" href="/help">Visit the help centre →</a>
    </section>

    <section class="final-cta" aria-labelledby="final-title">
        <div class="final-cta__inner" use:reveal>
            <div class="section-heading">
                <p class="section-heading__eyebrow eyebrow">READY TO BEGIN</p>
                <h2 id="final-title">Ready for the next adventure?</h2>
            </div>
            <p class="final-cta__description">
                Your next session starts here. Log in to catch up with your campaign, share your availability and prepare with your party, or create your account using the personal invitation from your organizer. Looking for rules, character options or inspiration? <a href="https://dnd-portal.com/" target="_blank" rel="noopener noreferrer">Explore D&amp;D Portal Wiki</a>.
            </p>
            <div class="cta-row">
                <a class="button button-primary" href="/login">Log in</a>
                <a class="button button-secondary" href="/register">Use your invitation</a>
            </div>
        </div>
    </section>
</main>
