<script lang="ts">
    import AdminHeader from '#lib/components/admin/AdminHeader.svelte';
    import { page } from '$app/state';
    import { api, ApiRequestError } from '#lib/api/index.js';
    type Session = { id: number; title: string; session_date: string; status: string; version: number };
    type Participant = { user_id: number; display_name: string; rsvp_status: string; attendance_status: string };
    const id = Number(page.params.id);
    let item = $state<Session | null>(null);
    let participants = $state<Participant[]>([]);
    let error = $state('');
    let busy = $state(false);
    let draft = $state('');
    async function load() {
        try {
            item = await api.get<Session>(`/api/v1/sessions/${id}`);
            draft = item.title;
            participants = await api.get<Participant[]>(`/api/v1/sessions/${id}/participants`);
        } catch (e) {
            error = e instanceof ApiRequestError ? e.message : 'Unable to load session.';
        }
    }
    async function save(status?: string) {
        if (!item) return;
        busy = true;
        try {
            item = await api.patch<Session>(`/api/v1/sessions/${id}`, { title: draft, status, version: item.version });
        } catch (e) {
            error = e instanceof ApiRequestError ? `${e.message} (${e.code})` : 'Unable to save session.';
            await load();
        } finally {
            busy = false;
        }
    }
    async function change(userId: number, body: Record<string, string>) {
        try {
            await api.patch(`/api/v1/sessions/${id}/participants/${userId}`, body);
            await load();
        } catch (e) {
            error = e instanceof ApiRequestError ? e.message : 'Unable to update participant.';
        }
    }
    $effect(() => {
        void load();
    });
</script>

<div class="admin-shell">
    <AdminHeader pageKey="sessions" title={item?.title ?? 'Session'} subtitle={`Record ID: ${id}`} />
    {#if item}<section class="admin-detail">
            <label for="session-detail-title">Title</label><input id="session-detail-title" bind:value={draft} /><button
                class="primary-button"
                disabled={busy}
                onclick={() => save()}>Save changes</button
            ><button
                class="secondary-button"
                disabled={busy || item.status === 'cancelled'}
                onclick={() => save('cancelled')}>Cancel session</button
            >
            <p>{item.session_date} · {item.status}</p>
            <h2>Participants</h2>
            <ul>
                {#each participants as participant}<li>
                        {participant.display_name} — RSVP {participant.rsvp_status} — attendance {participant.attendance_status}<button
                            class="secondary-button"
                            onclick={() => change(participant.user_id, { attendance_status: 'present' })}
                            >Present</button
                        ><button
                            class="secondary-button"
                            onclick={() => change(participant.user_id, { attendance_status: 'absent' })}>Absent</button
                        >
                    </li>{/each}
            </ul>
        </section>{:else}<p role="status">Loading…</p>{/if}{#if error}<p class="error-message" role="alert">
            {error}
        </p>{/if}
</div>
