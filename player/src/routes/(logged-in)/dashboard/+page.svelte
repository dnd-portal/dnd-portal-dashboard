<script lang="ts">
    import { onMount } from 'svelte';
    import { api, ApiRequestError } from '#lib/api/index.js';
    import { formatPortalDate, formatPortalTime } from '#lib/player/date.js';
    import { getPlayerUser } from '#lib/player/context.js';

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
    const playerContext = getPlayerUser();
    let campaigns = $state<Campaign[]>([]);
    let sessions = $state<Session[]>([]);
    let members = $state<Member[]>([]);
    let dashboard = $state<Dashboard | null>(null);
    let loading = $state(true);
    let error = $state('');
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
            campaigns = await api.get<Campaign[]>('/api/v1/campaigns');
            dashboard = await api.get<Dashboard>('/api/v1/dashboard');
            if (campaigns[0])
                [sessions, members] = await Promise.all([
                    api.get<Session[]>(`/api/v1/campaigns/${campaigns[0].id}/sessions`),
                    api.get<Member[]>(`/api/v1/campaigns/${campaigns[0].id}/members`)
                ]);
        } catch (caught) {
            error = caught instanceof ApiRequestError ? caught.message : 'Unable to load the portal.';
        } finally {
            loading = false;
        }
    }
    onMount(() => void load());
</script>

<svelte:head><title>D&amp;D Portal Player Dashboard</title></svelte:head>

{#if loading}
    <section class="p-state" aria-live="polite"><h1>Loading dashboard…</h1></section>
{:else if error}
    <section class="p-state" role="alert">
        <h1>Dashboard unavailable</h1>
        <p>{error}</p>
        <button class="p-button p-button--secondary" onclick={() => void load()}>Try again</button>
    </section>
{:else if !campaigns.length}
    <section class="p-state">
        <h1>No campaign yet</h1>
        <p>Your organizer has not added you to a campaign.</p>
    </section>
{:else}
    <section class="p-page-header p-dashboard__welcome">
        <p class="p-eyebrow">YOUR CAMPAIGN</p>
        <h1>Welcome, {playerContext.user?.display_name} <span>✦</span></h1>
        <p>Your campaign information comes from the portal.</p>
    </section>
    {#if nextSession}
        <section class="p-card p-dashboard__hero">
            <div class="p-dashboard__hero-copy">
                <div class="p-dashboard__session-status"><span class="live-dot"></span> NEXT SESSION</div>
                <h2>{nextSession.title}</h2>
                <p class="p-card__description">{nextSession.location ?? 'Location to be announced.'}</p>
                <div class="p-dashboard__hero-meta">
                    <span><b>Date</b>{formatPortalDate(nextSession.session_date)}</span><span
                        ><b>Time</b>{formatPortalTime(nextSession.starts_at)}</span
                    >
                </div>
                <a class="p-button p-button--secondary" href={`/sessions/${nextSession.id}`}
                    >View session details <span aria-hidden="true">→</span></a
                >
            </div>
        </section>
    {:else}<section class="p-card">
            <h2>No upcoming sessions</h2>
            <p>No future session is scheduled.</p>
        </section>{/if}
    <section class="p-dashboard__stats">
        <div class="p-card p-stat">
            <div class="p-eyebrow">UPCOMING SESSIONS</div>
            <strong class="p-stat__value">{sessions.length}</strong>
            <p class="p-stat__description">{sessions.length ? 'Scheduled in this campaign' : 'None scheduled'}</p>
        </div>
        <div class="p-card p-stat">
            <div class="p-eyebrow">YOUR BALANCE</div>
            <strong class="p-stat__value"
                >{dashboard?.open_contributions_cents
                    ? `${(dashboard.open_contributions_cents / 100).toFixed(2)} EUR`
                    : 'Paid'}</strong
            >
            <p class="p-stat__description">Own open obligations</p>
        </div>
        <div class="p-card p-stat">
            <div class="p-eyebrow">PARTY ROSTER</div>
            <strong class="p-stat__value">{members.length}</strong>
            <p class="p-stat__description">Campaign members</p>
        </div>
    </section>
    <div class="p-dashboard__columns">
        <section class="p-card">
            <div class="p-card__header">
                <div>
                    <p class="p-eyebrow">THE ROAD AHEAD</p>
                    <h3>Upcoming sessions</h3>
                </div>
                <a class="p-button p-button--secondary" href="/sessions"
                    >View calendar <span aria-hidden="true">→</span></a
                >
            </div>
            {#if sessions.length}<div class="p-list">
                    {#each sessions as session}<a class="p-list__row p-session-row" href={`/sessions/${session.id}`}
                            ><div class="p-session-row__date">
                                <strong>{session.session_date.slice(8, 10)}</strong><span
                                    >{session.session_date.slice(5, 7)}</span
                                >
                            </div>
                            <div class="p-session-row__body">
                                <strong class="p-list__title">{session.title}</strong><span class="p-list__meta"
                                    >{formatPortalTime(session.starts_at)} · {session.location ??
                                        'Location to be announced'}</span
                                >
                            </div>
                            <span class="p-session-row__action" aria-hidden="true">→</span></a
                        >{/each}
                </div>{:else}<p role="status">No sessions available.</p>{/if}
        </section>
        <section class="p-card">
            <div class="p-card__header">
                <div>
                    <p class="p-eyebrow">YOUR PARTY</p>
                    <h3>Fellow adventurers</h3>
                </div>
                <a class="p-button p-button--secondary" aria-label="Open party roster" href="/party">＋</a>
            </div>
            {#if members.length}<div class="p-list">
                    {#each members.slice(0, 5) as member}<div class="p-list__row party-row">
                            <div class="p-avatar">{member.display_name.slice(0, 2).toUpperCase()}</div>
                            <div>
                                <strong class="p-list__title">{member.display_name}</strong><span class="p-list__meta"
                                    >{member.membership_role}</span
                                >
                            </div>
                        </div>{/each}
                </div>{:else}<p role="status">No party members available.</p>{/if}<a
                class="p-button p-button--secondary p-button--full"
                href="/party">Open party roster <span aria-hidden="true">→</span></a
            >
        </section>
    </div>
{/if}
