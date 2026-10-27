<script lang="ts">
    import { onMount } from 'svelte';
    import AdminHeader from '#lib/components/admin/AdminHeader.svelte';
    import Notification from '#lib/components/shared/Notification.svelte';
    import { api, ApiRequestError } from '#lib/api/index.js';

    type Bucket = { bucket_type: string; request_count: number; blocked_until: string; updated_at: string };
    let buckets = $state<Bucket[]>([]);
    let ip = $state('');
    let error = $state('');
    let notice = $state('');
    let busy = $state(false);

    async function load() {
        try {
            buckets = await api.get<Bucket[]>('/api/v1/admin/rate-limits');
        } catch (caught) {
            error = caught instanceof ApiRequestError ? caught.message : 'Unable to load active login blocks.';
        }
    }
    async function resetIp() {
        busy = true;
        error = '';
        notice = '';
        try {
            const result = await api.post<{ reset: boolean; deleted: number }>('/api/v1/admin/rate-limits/reset', {
                bucket_type: 'login',
                key: ip
            });
            notice = result.reset ? 'Login block reset.' : 'No matching active login block was found.';
            await load();
        } catch (caught) {
            error = caught instanceof ApiRequestError ? caught.message : 'Unable to reset the login block.';
        } finally {
            busy = false;
        }
    }
    async function resetAll() {
        if (!confirm('Reset all active login blocks? This affects every currently blocked address.')) return;
        busy = true;
        error = '';
        notice = '';
        try {
            const result = await api.post<{ reset: boolean; deleted: number }>(
                '/api/v1/admin/rate-limits/reset-all',
                {}
            );
            notice = result.deleted
                ? `${result.deleted} active login block(s) reset.`
                : 'No active login blocks were found.';
            await load();
        } catch (caught) {
            error = caught instanceof ApiRequestError ? caught.message : 'Unable to reset active login blocks.';
        } finally {
            busy = false;
        }
    }
    onMount(load);
</script>

<div class="admin-shell">
    <AdminHeader pageKey="rate-limits" />
    {#if error}<Notification message={error} onDismiss={() => (error = '')} />{/if}
    {#if notice}<Notification variant="success" message={notice} onDismiss={() => (notice = '')} />{/if}
    <section class="admin-detail">
        <h2>Temporarily blocked</h2>
        <p>Login blocks are stored by IP address. No passwords, tokens or internal hashes are shown.</p>
        {#if buckets.length}
            <ul class="p-list">
                {#each buckets as bucket}
                    <li class="p-list__row">
                        <div class="p-list__body">
                            <strong class="p-list__title">Login attempts</strong>
                            <span class="p-list__meta"
                                >{bucket.request_count} attempts · blocked until {bucket.blocked_until}</span
                            >
                        </div>
                    </li>
                {/each}
            </ul>
        {:else}<p role="status">No active login blocks.</p>{/if}
    </section>
    <section class="admin-detail">
        <h2>Reset one login block</h2>
        <form
            onsubmit={(event) => {
                event.preventDefault();
                void resetIp();
            }}
        >
            <label for="blocked-ip">IP address</label>
            <input id="blocked-ip" bind:value={ip} autocomplete="off" placeholder="203.0.113.10" />
            <button class="p-button" type="submit" disabled={busy || !ip.trim()}>Reset block</button>
        </form>
    </section>
    <section class="admin-detail">
        <h2>Reset all active blocks</h2>
        <p>This is a global security action and affects every active login block.</p>
        <button class="p-button" type="button" disabled={busy || !buckets.length} onclick={resetAll}
            >Reset all blocks</button
        >
    </section>
</div>
