<script lang="ts">
    import FAQ from '#lib/components/FAQ.svelte';

    const helpFAQs = [
        { question: 'How do I get started?', answer: 'Start with your personal invitation, create your account and open the dashboard to see the campaigns connected to you.' },
        { question: 'How do invitations and registration work?', answer: 'New accounts require a personal invitation. Do not share invitation codes. If your link has expired or does not work, contact your organizer.' },
        { question: 'How do I sign in or reset my password?', answer: 'Existing users should sign in with their existing account. If you forgot your password, ask your organizer for a temporary personal reset link.' },
        { question: 'Where can I find campaign, session and poll information?', answer: 'Your organizer manages campaign access, sessions and polls. Use the dashboard to view information, answer polls and share your availability.' },
        { question: 'How do payments and mobile use work?', answer: 'Payment requests and personal obligations are shown when they are connected to your account. The dashboard is designed to work on desktop and mobile devices.' },
        { question: 'Who should I contact for help?', answer: 'Contact the organizer of your campaign for account access, invitation, reset or campaign-specific questions.' }
    ];
</script>

<svelte:head>
    <title>Help — D&amp;D Portal Dashboard</title>
    <meta name="description" content="Find help with invitations, accounts, campaigns, sessions and polls in D&D Portal Dashboard." />
</svelte:head>

<main class="info-page">
    <header class="info-page__header">
        <p class="eyebrow">HELP &amp; FAQ</p>
        <h1>Find your way back to the adventure.</h1>
        <p>Most account, invitation and reset problems are handled by your own organizer, who can check your access and provide a new personal link when needed.</p>
    </header>

    <section class="info-section" aria-labelledby="help-faq-title">
        <p class="eyebrow">COMMON QUESTIONS</p>
        <h2 id="help-faq-title">Answers for common campaign questions.</h2>
        <FAQ items={helpFAQs} />
        <a class="text-link" href="mailto:organizer@example.com">Contact your organizer →</a>
    </section>
</main>
