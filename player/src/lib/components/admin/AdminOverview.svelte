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
        {#if data.admin}<section class="admin-overview__metrics" aria-label="MVP metrics">
                <a class="admin-overview__metric" href="/admin/users"
                    ><span>Users</span><strong>{data.admin.users}</strong><small>Portal administration</small></a
                >
            </section>{/if}
        <!-- TODO post-MVP: restore session, contribution and balance metrics when those data sources are implemented. -->
        {#if data.admin}<DetailPanel eyebrow="RECENT ACTIVITY" title="Latest changes"
                >{#if data.admin.activity.length}<div class="admin-overview__list">
                        {#each data.admin.activity as item}<div class="admin-overview__list-row">
                                <span><b>{item.actor_name ?? 'System'} · {item.event_type}</b></span><time
                                    >{item.occurred_at}</time
                                >
                            </div>{/each}
                    </div>{:else}<p class="admin-overview__empty">
                        No activity has been recorded yet.
                    </p>{/if}</DetailPanel
            >{/if}
    </section>{/if}
