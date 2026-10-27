<script lang="ts">
    import { page } from '$app/state';
    import { api, ApiRequestError } from '#lib/api/index.js';
    type Session = {
        id: number;
        title: string;
        session_date: string;
        starts_at: string | null;
        ends_at: string | null;
        location: string | null;
        status: string;
    };
    type Participant = { user_id: number; display_name: string; rsvp_status: string; attendance_status: string | null };
    let session = $state<Session | null>(null);
    let participants = $state<Participant[]>([]);
    let error = $state('');
    async function load() {
        try {
            const id = page.params.id;
            [session, participants] = await Promise.all([
                api.get<Session>(`/api/v1/sessions/${id}`),
                api.get<Participant[]>(`/api/v1/sessions/${id}/participants`)
            ]);
        } catch (cause) {
            error = cause instanceof ApiRequestError ? cause.message : 'Unable to load session.';
        }
    }
    $effect(() => void load());
</script>

<main class="admin-content">
    <section class="admin-detail">
        <a href="/sessions">← Sessions</a>{#if error}<p class="error-message" role="alert">
                {error}
            </p>{:else if !session}<p role="status">Loading session…</p>{:else}<h1>{session.title}</h1>
            <dl>
                <dt>Date</dt>
                <dd>{session.session_date}</dd>
                <dt>Time</dt>
                <dd>{session.starts_at ?? 'To be announced'}{session.ends_at ? ` – ${session.ends_at}` : ''}</dd>
                <dt>Location</dt>
                <dd>{session.location ?? 'To be announced'}</dd>
                <dt>Status</dt>
                <dd>{session.status}</dd>
            </dl>
            <h2>Participants</h2>
            {#if participants.length}<ul>
                    {#each participants as participant}<li>
                            {participant.display_name} — {participant.rsvp_status}
                        </li>{/each}
                </ul>{:else}<p>No participant data available.</p>{/if}{/if}
    </section>
</main>
