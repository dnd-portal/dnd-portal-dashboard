<script lang="ts">
    import { onMount } from 'svelte';
    import { api, ApiRequestError } from '#lib/api/index.js';
    import AuthLayout from '#lib/components/player/AuthLayout.svelte';
    import PlayerShell from '#lib/components/player/PlayerShell.svelte';
    import { formatPortalDate, formatPortalTime } from '#lib/player/date.js';

    type User = { display_name: string; email: string | null; username: string | null; roles?: string[] };
    type Campaign = { id: number; name: string; membership_role: string | null };
    type Session = {
        id: number;
        title: string;
        session_date: string;
        starts_at: string | null;
        location: string | null;
    };
    type Member = { user_id: number; display_name: string; membership_role: string };
    type Dashboard = {
        next_session: (Session & { participants: number; rsvp_accepted: number }) | null;
        open_contributions_cents: number;
    };

    let user = $state<User | null>(null);
    let campaigns = $state<Campaign[]>([]);
    let sessions = $state<Session[]>([]);
    let members = $state<Member[]>([]);
    let dashboard = $state<Dashboard | null>(null);
    let loading = $state(true);
    let error = $state('');
    let identifier = $state('');
    let password = $state('');
    let loginError = $state('');
    let busy = $state(false);
    const nextSession = $derived(
        sessions
            .filter((session) => session.session_date >= new Date().toISOString().slice(0, 10))
            .sort((a, b) =>
                `${a.session_date}${a.starts_at ?? ''}`.localeCompare(`${b.session_date}${b.starts_at ?? ''}`)
            )[0] ?? null
    );

    async function load() {
        loading = true;
        error = '';
        try {
            user = await api.get<User>('/api/v1/auth/me');
            campaigns = await api.get<Campaign[]>('/api/v1/campaigns');
            dashboard = await api.get<Dashboard>('/api/v1/dashboard');
            if (campaigns[0]) {
                [sessions, members] = await Promise.all([
                    api.get<Session[]>(`/api/v1/campaigns/${campaigns[0].id}/sessions`),
                    api.get<Member[]>(`/api/v1/campaigns/${campaigns[0].id}/members`)
                ]);
            }
        } catch (caught) {
            if (caught instanceof ApiRequestError && caught.status === 401) user = null;
            else error = caught instanceof ApiRequestError ? caught.message : 'Unable to load the portal.';
        } finally {
            loading = false;
        }
    }

    async function login() {
        busy = true;
        loginError = '';
        try {
            await api.post('/api/v1/auth/login', { identifier, password });
            api.invalidateCsrf();
            await load();
        } catch (caught) {
            loginError = caught instanceof ApiRequestError ? caught.message : 'Unable to sign in.';
        } finally {
            busy = false;
        }
    }

    onMount(() => void load());
</script>

<svelte:head><title>D&amp;D Portal Player Dashboard</title></svelte:head>

{#if !user}<AuthLayout
        title="Welcome back"
        description="Sign in to see your campaign sessions, party and shared expenses."
    >
        <form
            class="p-form auth-login-form"
            onsubmit={(event) => {
                event.preventDefault();
                void login();
            }}
        >
            <label for="login-identifier">Email or username</label><input
                class="p-input login-input"
                id="login-identifier"
                bind:value={identifier}
                autocomplete="username"
                required
            /><label for="login-password">Password</label><input
                class="p-input login-input"
                id="login-password"
                type="password"
                bind:value={password}
                autocomplete="current-password"
                required
            />{#if loginError}<p class="error-message" role="alert">{loginError}</p>{/if}<button
                class="p-button login-button"
                type="submit"
                disabled={busy}>{busy ? 'Signing in…' : 'Continue with email'} <span>→</span></button
            >
        </form>
        <p class="footer-note"><a href="/register">New here?</a> Ask your organizer for an invitation.</p>
    </AuthLayout>
{:else}<PlayerShell {user} campaignName={campaigns[0]?.name}
        >{#if loading}<section class="login-screen" aria-live="polite">
                <h1>Loading dashboard…</h1>
            </section>{:else if error}<section class="login-screen" role="alert">
                <h1>Dashboard unavailable</h1>
                <p class="login-copy">{error}</p>
                <button class="secondary-button" onclick={() => void load()}>Try again</button>
            </section>{:else if !campaigns.length}<section class="player-empty-state">
                <h1>No campaign yet</h1>
                <p class="login-copy">Your organizer has not added you to a campaign.</p>
            </section>{:else}<section class="p-page-header welcome">
                <div>
                    <p class="p-eyebrow eyebrow">YOUR CAMPAIGN</p>
                    <h1>Welcome, {user.display_name} <span>✦</span></h1>
                    <p class="subcopy">Your campaign information comes from the portal.</p>
                </div>
            </section>
            {#if nextSession}<section class="p-card p-dashboard__hero hero-card">
                    <div class="hero-copy">
                        <div class="session-status"><span class="live-dot"></span> NEXT SESSION</div>
                        <h2>{nextSession.title}</h2>
                        <p class="p-card__description">{nextSession.location ?? 'Location to be announced.'}</p>
                        <div class="p-dashboard__hero-meta hero-meta">
                            <span>◷ <b>{formatPortalDate(nextSession.session_date)}</b></span><span
                                >◷ <b>{formatPortalTime(nextSession.starts_at)}</b></span
                            >
                        </div>
                        <a class="p-button p-button--secondary" href={`/sessions/${nextSession.id}`}
                            >View session details <span>→</span></a
                        >
                    </div>
                </section>{:else}<section class="panel">
                    <h2>No upcoming sessions</h2>
                    <p>Your organizer has not scheduled another session.</p>
                </section>{/if}
            <section class="p-dashboard__stats stats-grid">
                <div class="p-card p-stat stat-card">
                    <div class="p-eyebrow stat-top"><span>UPCOMING SESSIONS</span></div>
                    <strong class="p-stat__value">{sessions.length}</strong>
                    <p class="p-stat__description">
                        {sessions.length ? 'Scheduled in this campaign' : 'None scheduled'}
                    </p>
                </div>
                <div class="p-card p-stat stat-card">
                    <div class="p-eyebrow stat-top"><span>YOUR BALANCE</span></div>
                    <strong class="p-stat__value"
                        >{dashboard?.open_contributions_cents
                            ? `${(dashboard.open_contributions_cents / 100).toFixed(2)} EUR`
                            : 'Paid'}</strong
                    >
                    <p class="p-stat__description">Own open obligations</p>
                </div>
                <div class="p-card p-stat stat-card">
                    <div class="p-eyebrow stat-top"><span>PARTY ROSTER</span></div>
                    <strong class="p-stat__value">{members.length}</strong>
                    <p class="p-stat__description">Campaign members</p>
                </div>
            </section>
            <div class="p-dashboard__columns dashboard-grid">
                <section class="p-card panel sessions-panel">
                    <div class="p-card__header panel-header">
                        <div>
                            <p class="p-eyebrow eyebrow">THE ROAD AHEAD</p>
                            <h3>Upcoming sessions</h3>
                        </div>
                        <a class="p-button p-button--secondary" href="/sessions">View calendar <span>→</span></a>
                    </div>
                    {#if sessions.length}<div class="p-list session-list">
                            {#each sessions as session}<a
                                    class="p-list__row session-row"
                                    href={`/sessions/${session.id}`}
                                    ><div class="date-block">
                                        <strong>{session.session_date.slice(8, 10)}</strong><span
                                            >{session.session_date.slice(5, 7)}</span
                                        >
                                    </div>
                                    <div class="session-info">
                                        <strong class="p-list__title">{session.title}</strong><span class="p-list__meta"
                                            >{formatPortalTime(session.starts_at)} · {session.location ??
                                                'Location to be announced'}</span
                                        >
                                    </div>
                                    <span class="row-arrow">→</span></a
                                >{/each}
                        </div>{:else}<p role="status">No sessions available.</p>{/if}
                </section>
                <section class="p-card panel party-panel">
                    <div class="p-card__header panel-header">
                        <div>
                            <p class="p-eyebrow eyebrow">YOUR PARTY</p>
                            <h3>Fellow adventurers</h3>
                        </div>
                        <a class="p-button p-button--secondary" aria-label="Open party roster" href="/party">＋</a>
                    </div>
                    {#if members.length}<div class="p-list party-list">
                            {#each members.slice(0, 5) as member}<div class="p-list__row party-row">
                                    <div class="avatar avatar-blue">
                                        {member.display_name.slice(0, 2).toUpperCase()}
                                    </div>
                                    <div>
                                        <strong class="p-list__title">{member.display_name}</strong><span
                                            class="p-list__meta">{member.membership_role}</span
                                        >
                                    </div>
                                </div>{/each}
                        </div>{:else}<p role="status">No party members available.</p>{/if}<a
                        class="p-button p-button--secondary p-button--full"
                        href="/party">Open party roster <span>→</span></a
                    >
                </section>
            </div>{/if}</PlayerShell
    >{/if}
