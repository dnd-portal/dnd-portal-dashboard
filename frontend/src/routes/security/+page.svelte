<svelte:head
    ><title>Security — D&amp;D Portal Dashboard</title><meta
        name="description"
        content="Understand the security-related protections documented for D&D Portal Dashboard."
    /></svelte:head
>
<main class="info-page">
    <header class="info-page__header">
        <p class="eyebrow">PRIVATE BY DESIGN</p>
        <h1>Security and responsible use.</h1>
        <p>
            D&amp;D Portal Dashboard uses invitation-based access and controlled
            account flows. This page describes the protections documented by the
            project without making claims beyond the available implementation.
        </p>
    </header>
    <section class="info-section">
        <h2>Access and account protection</h2>
        <ul>
            <li>New registration is controlled by personal invitations.</li>
            <li>
                Session cookies are used to keep authenticated users signed in.
            </li>
            <li>
                Passwords are handled through the project’s password
                authentication flow.
            </li>
            <li>Password-reset links are personal and temporary.</li>
        </ul>
    </section>
    <section class="info-section">
        <h2>Operational protections</h2>
        <ul>
            <li>
                Authentication-sensitive operations include rate-limiting
                protections.
            </li>
            <li>
                Production error handling avoids exposing internal application
                details to visitors.
            </li>
            <li>
                Users should never share account, invitation or reset
                credentials.
            </li>
        </ul>
    </section>
    <section class="info-section">
        <h2>Responsible disclosure</h2>
        <p>
            If you believe you have found a security issue, use the project’s
            private reporting channel rather than sharing sensitive details
            publicly.
        </p>
        <a
            class="text-link"
            href="https://github.com/dnd-portal/dnd-portal-dashboard/blob/main/SECURITY.md"
            target="_blank"
            rel="noopener noreferrer">Read the security policy ↗</a
        >
    </section>
</main>
