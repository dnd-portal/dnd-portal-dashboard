<script lang="ts">
    import DetailPanel from './DetailPanel.svelte';
    import { api, ApiRequestError } from '#lib/api/index.js';
    type Dashboard = {
        next_session: {
            id: number;
            title: string;
            session_date: string;
            campaign_name: string;
            participants: number;
            rsvp_accepted: number;
        } | null;
        open_polls: number;
        open_contributions_cents: number;
        admin?: {
            users: number;
            campaigns: number;
            sessions: number;
            polls: number;
            payments: number;
            activity: { id: number; event_type: string; occurred_at: string; actor_name: string | null }[];
        };
    };
    let data = $state<Dashboard | null>(null);
    let error = $state('');
    let loading = $state(true);
    const humanEvent = (value: string) =>
        ({
            'auth.login': 'Signed in',
            'auth.register': 'Registered account',
            'user.imported': 'Imported user',
            'user.pending_created': 'Created pending user'
        })[value] ?? value.replaceAll('.', ' ');
    const formatDate = (value: string) =>
        new Intl.DateTimeFormat(undefined, { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value));
    async function load() {
        loading = true;
        try {
            data = await api.get<Dashboard>('/api/v1/dashboard');
        } catch (e) {
            error = e instanceof ApiRequestError ? e.message : 'Unable to load dashboard.';
        } finally {
            loading = false;
        }
    }
    $effect(() => {
        void load();
    });
</script>

{#if loading}<p role="status">Loading dashboard…</p>{:else if error}<p class="error-message" role="alert">
        {error}
    </p>{:else if data}<section class="admin-overview" aria-label="Admin overview content">
        {#if data.admin}<section class="admin-overview__metrics" aria-label="MVP administration links">
                <a class="admin-overview__metric" href="/admin/users"
                    ><span>Users</span><strong>{data.admin.users}</strong><small>Manage accounts</small></a
                >
                <a class="admin-overview__metric" href="/admin/invite-codes"
                    ><span>Invite codes</span><small>Review invitations</small></a
                >
                <a class="admin-overview__metric" href="/admin/polls"
                    ><span>Polls</span><strong>{data.admin.polls}</strong><small>Manage global polls</small></a
                >
                <a class="admin-overview__metric" href="/admin/activity"
                    ><span>Activity</span><small>Review recent changes</small></a
                >
            </section>{/if}
        <!-- TODO post-MVP: restore session, contribution and balance metrics when those data sources are implemented. -->
        {#if data.admin}<DetailPanel eyebrow="RECENT ACTIVITY" title="Latest changes"
                >{#if data.admin.activity.length}<ul class="admin-overview__list">
                        {#each data.admin.activity as item}<li class="admin-overview__list-row">
                                <span class="admin-overview__actor">{item.actor_name ?? 'Deleted user'}</span><span
                                    class="admin-overview__event">{humanEvent(item.event_type)}</span
                                ><time>{formatDate(item.occurred_at)}</time>
                            </li>{/each}
                    </ul>{:else}<p class="admin-overview__empty">
                        No activity has been recorded yet.
                    </p>{/if}</DetailPanel
            >{/if}
    </section>{/if}
