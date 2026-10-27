<script lang="ts">
    import { page } from '$app/state';
    import { goto } from '$app/navigation';
    import { onMount } from 'svelte';
    import { api, ApiRequestError } from '#lib/api/index.js';

    let activeNav = $state('Overview');
    let showDetails = $state(false);
    let showCampaigns = $state(false);
    let selectedCampaign = $state('Campaign 1');
    let userName = $state('Organizer');
    let userEmail = $state('');
    let userUsername = $state('');
    let loginIdentifier = $state('');
    let loginPassword = $state('');
    let loginError = $state('');
    let loginBusy = $state(false);
    let authenticated = $state(false);
    let hydrated = $state(false);
    let campaignNames = $state<string[]>([]);
    let dashboard = $state<{
        next_session: { title: string; session_date: string; participants: number; rsvp_accepted: number } | null;
        open_polls: number;
        open_contributions_cents: number;
    } | null>(null);
    let dashboardError = $state(false);
    const role = $derived(page.url.searchParams.get('role'));
    const isPlayer = $derived(authenticated || role === 'player' || role === 'user');
    const isDm = $derived(role === 'dm');
    const campaigns = $derived(campaignNames);

    async function login() {
        loginBusy = true;
        loginError = '';
        try {
            const user = await api.post<{ display_name: string; email: string | null; username: string | null }>(
                '/api/v1/auth/login',
                { identifier: loginIdentifier, password: loginPassword }
            );
            userName = user.display_name;
            userEmail = user.email ?? '';
            userUsername = user.username ?? '';
            authenticated = true;
            api.invalidateCsrf();
            await goto('/?role=player');
        } catch (error) {
            loginError = error instanceof ApiRequestError ? error.message : 'Unable to sign in.';
        } finally {
            loginBusy = false;
        }
    }

    async function logout() {
        try {
            await api.post('/api/v1/auth/logout', {});
            authenticated = false;
            window.location.href = '/';
        } catch (error) {
            loginError = error instanceof ApiRequestError ? error.message : 'Unable to sign out.';
        }
    }

    onMount(async () => {
        hydrated = true;
        try {
            const current = await api.get<{ display_name: string; email: string | null; username: string | null }>(
                '/api/v1/auth/me'
            );
            userName = current.display_name;
            userEmail = current.email ?? '';
            userUsername = current.username ?? '';
            authenticated = true;
            const [campaignsResponse, dashboardResponse] = await Promise.all([
                api.get<{ name: string }[]>('/api/v1/campaigns'),
                api.get<typeof dashboard>('/api/v1/dashboard')
            ]);
            campaignNames = campaignsResponse.map((campaign) => campaign.name);
            dashboard = dashboardResponse;
        } catch {
            dashboardError = true;
            // Anonymous visitors remain on the login screen.
        }
    });
    const navItems = ['Overview', 'Sessions', 'The party', 'Treasury'];
    const characters = [
        { name: 'Ari', role: 'Bard', initials: 'AR', color: 'violet' },
        { name: 'Milo', role: 'Ranger', initials: 'MI', color: 'amber' },
        { name: 'Tess', role: 'Cleric', initials: 'TE', color: 'rose' },
        { name: 'You', role: 'Rogue', initials: 'YO', color: 'blue' }
    ];
    const sessions = [
        {
            date: '18',
            month: 'OCT',
            title: 'The Sunken Archive',
            meta: 'Fri · 19:30 – 23:30',
            tag: 'Next up',
            next: true
        },
        {
            date: '01',
            month: 'NOV',
            title: 'Ashes of the Old Road',
            meta: 'Fri · 19:30 – 23:30',
            tag: 'In 2 weeks',
            next: false
        },
        {
            date: '15',
            month: 'NOV',
            title: 'The Ember Crown',
            meta: 'Fri · 19:30 – 23:30',
            tag: 'In 1 month',
            next: false
        }
    ];
</script>

<svelte:head>
    <title>D&D Portal Player Dashboard · {isPlayer ? 'Player' : isDm ? 'Organizer' : 'Sign in'}</title>
    <meta name="description" content="Your D&D Portal Player Dashboard for campaign sessions and shared expenses." />
</svelte:head>

<div class="app-shell" class:login-shell={!isPlayer && !isDm}>
    <aside class="sidebar">
        <div class="brand"><span class="brand-mark">✦</span><span>D&amp;D PORTAL<br />PLAYER DASHBOARD</span></div>
        <div class="campaign-switcher-wrap">
            <button
                class="campaign-switcher"
                onclick={() => (showCampaigns = !showCampaigns)}
                aria-expanded={showCampaigns}
                ><div class="campaign-icon">D&amp;D</div>
                <div>
                    <strong>D&amp;D Portal</strong><span
                        >{selectedCampaign} · {isPlayer ? 'Player' : isDm ? 'Organizer' : 'Welcome'}</span
                    >
                </div>
                <span class="chevron">⌄</span></button
            >{#if showCampaigns}<div class="campaign-menu">
                    {#each campaigns as campaign}<button
                            class:chosen={selectedCampaign === campaign}
                            onclick={() => {
                                selectedCampaign = campaign;
                                showCampaigns = false;
                            }}
                            ><span class="campaign-menu-icon">{campaign === 'One-shot' ? '◇' : '◆'}</span
                            >{campaign}{#if selectedCampaign === campaign}<span class="campaign-check">✓</span
                                >{/if}</button
                        >{/each}
                </div>{/if}
        </div>
        <nav aria-label="Main navigation">
            <p class="nav-label">CAMPAIGN</p>
            {#each navItems as item, i}<button
                    class:active={activeNav === item}
                    class="nav-item"
                    onclick={() => (activeNav = item)}
                    ><span class="nav-icon">{['⌗', '▣', '♧', '◈'][i]}</span>{item}</button
                >{/each}
            <p class="nav-label secondary-label">WORLD</p>
            <button class="nav-item"><span class="nav-icon">◒</span>Chronicle <span class="new-dot"></span></button>
            <button class="nav-item"><span class="nav-icon">⌁</span>Map room</button>
        </nav>
        <div class="sidebar-bottom">
            <div class="dm-card">
                <span class="status-dot"></span>
                <div><strong>Organizer online</strong><span>{userEmail || 'Loading user...'}</span></div>
                <button aria-label="More options">···</button>
            </div>
            <div class="profile">
                <div class="avatar avatar-blue">TV</div>
                <div><strong>{userName}</strong><span>@{userUsername || 'loading'}</span></div>
                <span class="chevron">⌄</span>
            </div>
            {#if isPlayer || isDm}<button class="nav-item" type="button" onclick={logout}>Sign out</button>{/if}
        </div>
    </aside>

    <main class="content">
        <header class="topbar">
            <div class="breadcrumb"><span>Campaign</span><b>/</b><strong>{activeNav}</strong></div>
            <div class="top-actions">
                <button class="icon-button" aria-label="Notifications">♧<span class="notification"></span></button
                ><button class="help-button">?</button>
                <div class="avatar avatar-blue">TV</div>
            </div>
        </header>
        <div class="page-wrap">
            {#if !isPlayer && !isDm}
                <section class="login-screen">
                    <div class="login-mark">✦</div>
                    <p class="eyebrow">D&amp;D PORTAL PLAYER DASHBOARD</p>
                    <h1>Welcome back</h1>
                    <p class="login-copy">
                        Sign in to see your upcoming sessions, shared expenses, and campaign updates.
                    </p>
                    <form
                        onsubmit={(event) => {
                            event.preventDefault();
                            void login();
                        }}
                    >
                        <label for="login-identifier">Email or username</label>
                        <input
                            class="login-input"
                            id="login-identifier"
                            bind:value={loginIdentifier}
                            autocomplete="username"
                            required
                        />
                        <label for="login-password">Password</label>
                        <input
                            class="login-input"
                            id="login-password"
                            type="password"
                            bind:value={loginPassword}
                            autocomplete="current-password"
                            required
                        />
                        {#if loginError}<p class="error-message" role="alert">{loginError}</p>{/if}
                        <button
                            class="login-button"
                            type="submit"
                            disabled={!hydrated || loginBusy}
                            aria-busy={!hydrated || loginBusy}
                            >{loginBusy ? 'Signing in…' : 'Continue with email'} <span>→</span></button
                        >
                    </form>
                    <div class="login-divider"><span>or</span></div>
                    <button class="login-secondary"><span class="google-mark">G</span> Continue with Google</button>
                    <p class="login-footnote">
                        <a href="/register">New here?</a> Ask your organizer for an invitation.
                    </p>
                </section>
            {:else if isPlayer}
                <section class="welcome">
                    <div>
                        <p class="eyebrow">FRIDAY, 11 OCTOBER 2026</p>
                        <h1>Good evening, Yori <span>✦</span></h1>
                        <p class="subcopy">The next chapter is waiting for you.</p>
                    </div>
                </section>
                <section class="hero-card">
                    <div class="hero-copy">
                        <div class="session-status">
                            <span class="live-dot"></span> YOUR NEXT SESSION <span class="session-divider"></span> IN 7 DAYS
                        </div>
                        <h2>The Sunken Archive</h2>
                        <p>Deep beneath the drowned library, something is still turning the pages.</p>
                        <div class="hero-meta">
                            <span>◷ <b>Friday, 18 October</b></span><span>◷ <b>19:30 – 23:30</b></span><span
                                >⌖ <b>The Old Keep</b></span
                            >
                        </div>
                        <button class="secondary-button" onclick={() => (showDetails = !showDetails)}
                            >{showDetails ? 'Hide details' : 'View session details'} <span>→</span></button
                        >
                    </div>
                    <div class="hero-orbit">
                        <div class="orbit-ring ring-one"></div>
                        <div class="orbit-ring ring-two"></div>
                        <div class="moon">☾</div>
                        <span class="orbit-star star-one">✦</span><span class="orbit-star star-two">·</span><span
                            class="orbit-star star-three">✦</span
                        >
                    </div>
                </section>
                {#if showDetails}<div class="details-banner">
                        <strong>Session details unlocked.</strong><span
                            >Bring your character sheet, a d20, and 12 silver for the tavern tab.</span
                        ><button onclick={() => (showDetails = false)}>×</button>
                    </div>{/if}
                <section class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-top">
                            <span>UPCOMING SESSIONS</span><span class="stat-icon blue-icon">▣</span>
                        </div>
                        <strong>03</strong>
                        <p>Next one in 7 days</p>
                    </div>
                    <div class="stat-card">
                        <div class="stat-top">
                            <span>SNACKS & DRINKS</span><span class="stat-icon green-icon">◈</span>
                        </div>
                        <strong>Paid <span class="check">✓</span></strong>
                        <p>You're all settled up</p>
                    </div>
                    <div class="stat-card">
                        <div class="stat-top">
                            <span>PARTY ROSTER</span><span class="stat-icon purple-icon">♧</span>
                        </div>
                        <strong>04 <small>/ 05</small></strong>
                        <p>One adventurer away</p>
                    </div>
                    <div class="stat-card">
                        <div class="stat-top">
                            <span>CAMPAIGN PROGRESS</span><span class="stat-icon amber-icon">◒</span>
                        </div>
                        <strong>68%</strong>
                        <p>Chapter 4 of 6</p>
                        <div class="progress"><span class="progress__fill progress__fill--68"></span></div>
                    </div>
                </section>
                <div class="dashboard-grid">
                    <section class="panel sessions-panel">
                        <div class="panel-header">
                            <div>
                                <p class="eyebrow">THE ROAD AHEAD</p>
                                <h3>Upcoming sessions</h3>
                            </div>
                            <button class="text-button">View calendar <span>→</span></button>
                        </div>
                        <div class="session-list">
                            {#each sessions as session}<div class="session-row">
                                    <div class="date-block">
                                        <strong>{session.date}</strong><span>{session.month}</span>
                                    </div>
                                    <div class="session-info">
                                        <strong>{session.title}</strong><span>{session.meta}</span>
                                    </div>
                                    <span class:next={session.next} class="session-tag">{session.tag}</span><button
                                        class="row-arrow"
                                        aria-label="Open session">→</button
                                    >
                                </div>{/each}
                        </div>
                    </section>
                    <section class="panel party-panel">
                        <div class="panel-header">
                            <div>
                                <p class="eyebrow">YOUR PARTY</p>
                                <h3>Fellow adventurers</h3>
                            </div>
                            <button class="round-button" aria-label="Add adventurer">＋</button>
                        </div>
                        <div class="party-list">
                            {#each characters as character}<div class="party-row">
                                    <div class="avatar avatar-{character.color}">{character.initials}</div>
                                    <div><strong>{character.name}</strong><span>{character.role}</span></div>
                                    {#if character.name === 'You'}<span class="you-label">YOU</span>{:else}<span
                                            class="online-label"><i></i> Online</span
                                        >{/if}
                                </div>{/each}
                        </div>
                        <button class="full-button">Open party roster <span>→</span></button>
                    </section>
                </div>
            {:else if isDm}
                <section class="welcome dm-welcome">
                    <div>
                        <p class="eyebrow">ORGANIZER DASHBOARD · {userName.toUpperCase()}</p>
                        <h1>Campaign command center - {selectedCampaign} <span>✦</span></h1>
                        <p class="subcopy">Keep everyone informed and the shared expenses settled.</p>
                    </div>
                    <button class="dm-primary" onclick={() => (showDetails = !showDetails)}>＋ Create session</button>
                </section>
                <section class="dm-hero">
                    <div>
                        <div class="session-status"><span class="live-dot"></span> NEXT SESSION · 18 OCTOBER</div>
                        <h2>The Sunken Archive</h2>
                        <p>Your next session is ready to run. 4 of 5 participants have confirmed.</p>
                        <div class="dm-hero-meta">
                            <span>◷ Friday, 19:30 – 23:30</span><span>⌖ The Old Keep</span><span>◈ 4 / 5 confirmed</span
                            >
                        </div>
                    </div>
                    <div class="dm-hero-actions">
                        <button class="dm-outline">Edit session</button><button class="dm-primary"
                            >Open session details →</button
                        >
                    </div>
                </section>
                {#if showDetails}<div class="details-banner">
                        <strong>New session flow ready.</strong><span
                            >Choose a date, set the snack budget, and invite your party.</span
                        ><button onclick={() => (showDetails = false)}>×</button>
                    </div>{/if}
                <section class="stats-grid dm-stats">
                    <div class="stat-card">
                        <div class="stat-top">
                            <span>PARTY CONFIRMED</span><span class="stat-icon green-icon">✓</span>
                        </div>
                        <strong>04 <small>/ 05</small></strong>
                        <p>One player needs to confirm</p>
                    </div>
                    <div class="stat-card">
                        <div class="stat-top"><span>LAST SESSION</span><span class="stat-icon amber-icon">◈</span></div>
                        <strong>€84.50 received</strong>
                        <p>16 / 20 players paid</p>
                    </div>
                    <div class="stat-card">
                        <div class="stat-top">
                            <span>UPCOMING SESSIONS</span><span class="stat-icon blue-icon">▣</span>
                        </div>
                        <strong>03</strong>
                        <p>Scheduled this campaign</p>
                    </div>
                    <div class="stat-card">
                        <div class="stat-top">
                            <span>CAMPAIGN PROGRESS</span><span class="stat-icon purple-icon">◒</span>
                        </div>
                        <strong>68%</strong>
                        <p>Chapter 4 of 6</p>
                        <div class="progress"><span class="progress__fill progress__fill--68"></span></div>
                    </div>
                </section>
                <div class="dashboard-grid">
                    <section class="panel">
                        <div class="panel-header">
                            <div>
                                <p class="eyebrow">SESSION MANAGEMENT</p>
                                <h3>Upcoming sessions</h3>
                            </div>
                            <button class="text-button">View calendar <span>→</span></button>
                        </div>
                        <div class="session-list">
                            {#each sessions as session}<div class="session-row">
                                    <div class="date-block">
                                        <strong>{session.date}</strong><span>{session.month}</span>
                                    </div>
                                    <div class="session-info">
                                        <strong>{session.title}</strong><span>{session.meta}</span>
                                    </div>
                                    <span class:next={session.next} class="session-tag">{session.tag}</span><button
                                        class="row-arrow"
                                        aria-label="Edit session">✎</button
                                    >
                                </div>{/each}
                        </div>
                    </section>
                    <section class="panel">
                        <div class="panel-header">
                            <div>
                                <p class="eyebrow">PARTICIPANT READINESS</p>
                                <h3>Participant check-in</h3>
                            </div>
                            <button class="text-button">Manage <span>→</span></button>
                        </div>
                        <div class="party-list">
                            {#each characters as character}<div class="party-row">
                                    <div class="avatar avatar-{character.color}">{character.initials}</div>
                                    <div><strong>{character.name}</strong><span>{character.role}</span></div>
                                    {#if character.name === 'You'}<span class="online-label"><i></i> Confirmed</span
                                        >{:else}<span class="you-label"
                                            >{character.name === 'Tess' ? 'PENDING' : 'PAID'}</span
                                        >{/if}
                                </div>{/each}
                        </div>
                        <button class="full-button">Send reminder to Tess <span>→</span></button>
                    </section>
                </div>
            {/if}
        </div>
    </main>
</div>
