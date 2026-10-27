<script lang="ts">
    import { goto } from '$app/navigation';
    import { onMount } from 'svelte';
    import { api, ApiRequestError } from '#lib/api/index.js';

    type User = { display_name: string; email: string | null; username: string | null };
    type Campaign = { id: number; name: string; membership_role: string | null };
    type Session = {
        id: number;
        title: string;
        session_date: string;
        starts_at: string | null;
        ends_at: string | null;
        location: string | null;
        status: string;
    };
    type Member = { user_id: number; display_name: string; membership_role: string };
    type Dashboard = {
        next_session: (Session & { participants: number; rsvp_accepted: number }) | null;
        open_contributions_cents: number;
        open_contributions_by_currency: Record<string, number>;
    };

    let user = $state<User | null>(null);
    let campaigns = $state<Campaign[]>([]);
    let campaignId = $state<number | null>(null);
    let sessions = $state<Session[]>([]);
    let members = $state<Member[]>([]);
    let dashboard = $state<Dashboard | null>(null);
    let activeNav = $state('Overview');
    let loading = $state(true);
    let error = $state('');
    let loginIdentifier = $state('');
    let loginPassword = $state('');
    let loginBusy = $state(false);
    let loginError = $state('');

    async function loadPortal() {
        loading = true;
        error = '';
        try {
            user = await api.get<User>('/api/v1/auth/me');
            campaigns = await api.get<Campaign[]>('/api/v1/campaigns');
            campaignId = campaigns[0]?.id ?? null;
            dashboard = await api.get<Dashboard>('/api/v1/dashboard');
            if (campaignId) {
                [sessions, members] = await Promise.all([
                    api.get<Session[]>(`/api/v1/campaigns/${campaignId}/sessions`),
                    api.get<Member[]>(`/api/v1/campaigns/${campaignId}/members`)
                ]);
            }
        } catch (cause) {
            if (cause instanceof ApiRequestError && cause.status === 401) user = null;
            else error = cause instanceof ApiRequestError ? cause.message : 'Unable to load the portal.';
        } finally {
            loading = false;
        }
    }

    async function login() {
        loginBusy = true;
        loginError = '';
        try {
            await api.post('/api/v1/auth/login', { identifier: loginIdentifier, password: loginPassword });
            api.invalidateCsrf();
            await loadPortal();
        } catch (cause) {
            loginError = cause instanceof ApiRequestError ? cause.message : 'Unable to sign in.';
        } finally {
            loginBusy = false;
        }
    }

    async function logout() {
        await api.post('/api/v1/auth/logout', {});
        user = null;
        campaigns = [];
        sessions = [];
        members = [];
        dashboard = null;
    }

    function openSessions() {
        activeNav = 'Sessions';
        void goto('/sessions');
    }

    function openParty() {
        activeNav = 'The party';
        void goto('/party');
    }

    onMount(() => void loadPortal());
</script>

<svelte:head
    ><title>D&amp;D Portal Player Dashboard</title><meta
        name="description"
        content="Your D&D Portal campaign dashboard."
    /></svelte:head
>

{#if !user}
    <main class="login-screen">
        <div class="login-mark">✦</div>
        <p class="eyebrow">D&amp;D PORTAL PLAYER DASHBOARD</p>
        <h1>Welcome back</h1>
        <p class="login-copy">Sign in to see your sessions, party and shared expenses.</p>
        <form
            onsubmit={(event) => {
                event.preventDefault();
                void login();
            }}
        >
            <label for="login-identifier">Email or username</label><input
                id="login-identifier"
                bind:value={loginIdentifier}
                autocomplete="username"
                required
            />
            <label for="login-password">Password</label><input
                id="login-password"
                type="password"
                bind:value={loginPassword}
                autocomplete="current-password"
                required
            />
            {#if loginError}<p class="error-message" role="alert">{loginError}</p>{/if}
            <button class="login-button" type="submit" disabled={loginBusy}
                >{loginBusy ? 'Signing in…' : 'Continue with email'} <span>→</span></button
            >
        </form>
        <p class="login-footnote"><a href="/register">New here?</a> Ask your organizer for an invitation.</p>
    </main>
{:else}
    <div class="app-shell">
        <aside class="sidebar">
            <div class="brand"><span class="brand-mark">✦</span><span>D&amp;D PORTAL<br />PLAYER DASHBOARD</span></div>
            <div class="campaign-switcher-wrap">
                <div class="campaign-switcher">
                    <div class="campaign-icon">D&amp;D</div>
                    <div><strong>{campaigns[0]?.name ?? 'No campaign'}</strong><span>Player</span></div>
                </div>
            </div>
            <nav aria-label="Main navigation">
                <p class="nav-label">CAMPAIGN</p>
                <a
                    class:active={activeNav === 'Overview'}
                    class="nav-item"
                    href="/"
                    onclick={() => (activeNav = 'Overview')}>⌗ Overview</a
                >
                <a
                    class:active={activeNav === 'Sessions'}
                    class="nav-item"
                    href="/sessions"
                    onclick={() => (activeNav = 'Sessions')}>▣ Sessions</a
                >
                <a
                    class:active={activeNav === 'The party'}
                    class="nav-item"
                    href="/party"
                    onclick={() => (activeNav = 'The party')}>♧ The party</a
                >
                <a
                    class:active={activeNav === 'Treasury'}
                    class="nav-item"
                    href="/payments"
                    onclick={() => (activeNav = 'Treasury')}>◈ Treasury</a
                >
                <p class="nav-label secondary-label">WORLD</p>
                <span class="nav-item is-disabled" title="Chronicle is not available for this campaign"
                    >◒ Chronicle</span
                ><span class="nav-item is-disabled" title="Map room is not available for this campaign">⌁ Map room</span
                >
            </nav>
            <div class="sidebar-bottom">
                <div class="profile">
                    <div class="avatar avatar-blue">{user.display_name.slice(0, 2).toUpperCase()}</div>
                    <div><strong>{user.display_name}</strong><span>@{user.username ?? 'player'}</span></div>
                </div>
                <button class="nav-item" type="button" onclick={logout}>Sign out</button>
            </div>
        </aside>
        <main class="content">
            <header class="topbar">
                <div class="breadcrumb"><span>Campaign</span><b>/</b><strong>{activeNav}</strong></div>
                <div class="avatar avatar-blue">{user.display_name.slice(0, 2).toUpperCase()}</div>
            </header>
            <div class="page-wrap">
                {#if loading}<section class="login-screen" aria-live="polite"><h1>Loading dashboard…</h1></section>
                {:else if error}<section class="login-screen" role="alert">
                        <h1>Dashboard unavailable</h1>
                        <p class="login-copy">{error}</p>
                        <button class="secondary-button" onclick={() => void loadPortal()}>Try again</button>
                    </section>
                {:else if !campaignId}<section class="login-screen">
                        <h1>No campaign yet</h1>
                        <p class="login-copy">Your organizer has not added you to a campaign.</p>
                    </section>
                {:else}<section class="welcome">
                        <div>
                            <p class="eyebrow">YOUR CAMPAIGN</p>
                            <h1>Welcome, {user.display_name} <span>✦</span></h1>
                            <p class="subcopy">Your campaign information comes from the portal.</p>
                        </div>
                    </section>
                    {#if dashboard?.next_session}<section class="hero-card">
                            <div class="hero-copy">
                                <div class="session-status"><span class="live-dot"></span> NEXT SESSION</div>
                                <h2>{dashboard.next_session.title}</h2>
                                <p>{dashboard.next_session.location ?? 'Location to be announced.'}</p>
                                <div class="hero-meta">
                                    <span>◷ <b>{dashboard.next_session.session_date}</b></span><span
                                        >◷ <b>{dashboard.next_session.starts_at ?? 'Time to be announced'}</b></span
                                    >
                                </div>
                                <a class="secondary-button" href={`/sessions/${dashboard.next_session.id}`}
                                    >View session details <span>→</span></a
                                >
                            </div>
                        </section>{:else}<section class="panel">
                            <h2>No upcoming sessions</h2>
                            <p>Your organizer has not scheduled another session.</p>
                        </section>{/if}
                    <section class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-top"><span>UPCOMING SESSIONS</span></div>
                            <strong>{sessions.length}</strong>
                            <p>{sessions.length ? 'Scheduled in this campaign' : 'None scheduled'}</p>
                        </div>
                        <div class="stat-card">
                            <div class="stat-top"><span>YOUR BALANCE</span></div>
                            <strong
                                >{dashboard?.open_contributions_cents
                                    ? `${(dashboard.open_contributions_cents / 100).toFixed(2)} EUR`
                                    : 'Paid'}</strong
                            >
                            <p>Own open obligations</p>
                        </div>
                        <div class="stat-card">
                            <div class="stat-top"><span>PARTY ROSTER</span></div>
                            <strong>{members.length}</strong>
                            <p>Campaign members</p>
                        </div>
                    </section>
                    <div class="dashboard-grid">
                        <section class="panel sessions-panel">
                            <div class="panel-header">
                                <div>
                                    <p class="eyebrow">THE ROAD AHEAD</p>
                                    <h3>Upcoming sessions</h3>
                                </div>
                                <button class="text-button" onclick={openSessions}>View calendar <span>→</span></button>
                            </div>
                            {#if sessions.length}<div class="session-list">
                                    {#each sessions as session}<a class="session-row" href={`/sessions/${session.id}`}
                                            ><div class="date-block">
                                                <strong>{session.session_date.slice(8, 10)}</strong><span
                                                    >{session.session_date.slice(5, 7)}</span
                                                >
                                            </div>
                                            <div class="session-info">
                                                <strong>{session.title}</strong><span
                                                    >{session.starts_at ?? 'Time to be announced'} · {session.location ??
                                                        'Location to be announced'}</span
                                                >
                                            </div>
                                            <span class="row-arrow">→</span></a
                                        >{/each}
                                </div>{:else}<p role="status">No sessions available.</p>{/if}
                        </section>
                        <section class="panel party-panel">
                            <div class="panel-header">
                                <div>
                                    <p class="eyebrow">YOUR PARTY</p>
                                    <h3>Fellow adventurers</h3>
                                </div>
                                <button class="round-button" aria-label="Open party roster" onclick={openParty}
                                    >＋</button
                                >
                            </div>
                            {#if members.length}<div class="party-list">
                                    {#each members.slice(0, 5) as member}<div class="party-row">
                                            <div class="avatar avatar-blue">
                                                {member.display_name.slice(0, 2).toUpperCase()}
                                            </div>
                                            <div>
                                                <strong>{member.display_name}</strong><span
                                                    >{member.membership_role}</span
                                                >
                                            </div>
                                        </div>{/each}
                                </div>{:else}<p role="status">No party members available.</p>{/if}<button
                                class="full-button"
                                onclick={openParty}>Open party roster <span>→</span></button
                            >
                        </section>
                    </div>
                {/if}
            </div>
        </main>
    </div>
{/if}
