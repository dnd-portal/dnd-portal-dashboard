<script lang="ts">
    import { onMount } from 'svelte';
    import { page } from '$app/state';
    import { api, ApiRequestError } from '#lib/api/index.js';
    import PlayerShell from '#lib/components/player/PlayerShell.svelte';
    import { formatPortalDate, formatPortalDateTime } from '#lib/player/date.js';
    type User = { id: number; display_name: string; username: string | null; roles?: string[] };
    type Session = {
        id: number;
        title: string;
        session_date: string;
        starts_at: string | null;
        ends_at: string | null;
        location: string | null;
        status: string;
    };
    type Participant = { display_name: string; rsvp_status: string };
    let user = $state<User | null>(null);
    let session = $state<Session | null>(null);
    let participants = $state<Participant[]>([]);
    let error = $state('');
    let saving = $state(false);
    onMount(async () => {
        try {
            user = await api.get<User>('/api/v1/auth/me');
            const id = page.params.id;
            [session, participants] = await Promise.all([
                api.get<Session>(`/api/v1/sessions/${id}`),
                api.get<Participant[]>(`/api/v1/sessions/${id}/participants`)
            ]);
        } catch (cause) {
            error = cause instanceof ApiRequestError ? cause.message : 'Unable to load session.';
        }
    });

    async function updateRsvp(status: 'accepted' | 'declined') {
        if (!user || !session) return;
        saving = true;
        error = '';
        try {
            await api.patch(`/api/v1/sessions/${session.id}/participants/${user.id}`, { rsvp_status: status });
            participants = await api.get<Participant[]>(`/api/v1/sessions/${session.id}/participants`);
        } catch (cause) {
            error = cause instanceof ApiRequestError ? cause.message : 'Unable to update RSVP.';
        } finally {
            saving = false;
        }
    }
</script>

{#if user}<PlayerShell {user}
        ><section class="p-dashboard">
            <a href="/sessions">← Sessions</a>{#if error}<p class="error-message" role="alert">
                    {error}
                </p>{:else if !session}<p role="status">Loading session…</p>{:else}<h1>{session.title}</h1>
                <dl>
                    <dt>Date</dt>
                    <dd>{formatPortalDate(session.session_date)}</dd>
                    <dt>Time</dt>
                    <dd>
                        {formatPortalDateTime(session.starts_at)}{session.ends_at
                            ? ` – ${formatPortalDateTime(session.ends_at)}`
                            : ''}
                    </dd>
                    <dt>Location</dt>
                    <dd>{session.location ?? 'To be announced'}</dd>
                    <dt>Status</dt>
                    <dd>{session.status}</dd>
                </dl>
                <div class="rsvp-actions">
                    <button class="primary-button" disabled={saving} onclick={() => updateRsvp('accepted')}
                        >I’m attending</button
                    ><button class="secondary-button" disabled={saving} onclick={() => updateRsvp('declined')}
                        >I can’t attend</button
                    >
                </div>
                <h2>Participants</h2>
                {#if participants.length}<ul>
                        {#each participants as participant}<li>
                                {participant.display_name} — {participant.rsvp_status}
                            </li>{/each}
                    </ul>{:else}<p>No participant data available.</p>{/if}{/if}
        </section></PlayerShell
    >{:else}<p class="content-state">{error || 'Checking access…'}</p>{/if}
