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
            class="auth-login-form"
            onsubmit={(event) => {
                event.preventDefault();
                void login();
            }}
        >
            <label for="login-identifier">Email or username</label><input
                class="login-input"
                id="login-identifier"
                bind:value={identifier}
                autocomplete="username"
                required
            /><label for="login-password">Password</label><input
                class="login-input"
                id="login-password"
                type="password"
                bind:value={password}
                autocomplete="current-password"
                required
            />{#if loginError}<p class="error-message" role="alert">{loginError}</p>{/if}<button
                class="login-button"
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
            </section>{:else}<section class="welcome">
                <div>
                    <p class="eyebrow">YOUR CAMPAIGN</p>
                    <h1>Welcome, {user.display_name} <span>✦</span></h1>
                    <p class="subcopy">Your campaign information comes from the portal.</p>
                </div>
            </section>
            {#if nextSession}<section class="hero-card">
                    <div class="hero-copy">
                        <div class="session-status"><span class="live-dot"></span> NEXT SESSION</div>
                        <h2>{nextSession.title}</h2>
                        <p>{nextSession.location ?? 'Location to be announced.'}</p>
                        <div class="hero-meta">
                            <span>◷ <b>{formatPortalDate(nextSession.session_date)}</b></span><span
                                >◷ <b>{formatPortalTime(nextSession.starts_at)}</b></span
                            >
                        </div>
                        <a class="secondary-button" href={`/sessions/${nextSession.id}`}
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
                        <a class="text-button" href="/sessions">View calendar <span>→</span></a>
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
                                            >{formatPortalTime(session.starts_at)} · {session.location ??
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
                        <a class="round-button" aria-label="Open party roster" href="/party">＋</a>
                    </div>
                    {#if members.length}<div class="party-list">
                            {#each members.slice(0, 5) as member}<div class="party-row">
                                    <div class="avatar avatar-blue">
                                        {member.display_name.slice(0, 2).toUpperCase()}
                                    </div>
                                    <div>
                                        <strong>{member.display_name}</strong><span>{member.membership_role}</span>
                                    </div>
                                </div>{/each}
                        </div>{:else}<p role="status">No party members available.</p>{/if}<a
                        class="full-button"
                        href="/party">Open party roster <span>→</span></a
                    >
                </section>
            </div>{/if}</PlayerShell
    >{/if}
