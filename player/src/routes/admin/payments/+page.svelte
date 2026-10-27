<script lang="ts">
    import AdminHeader from '#lib/components/admin/AdminHeader.svelte';
    import { api, ApiRequestError } from '#lib/api/index.js';
    type Request = {
        id: number;
        title: string;
        amount_cents: number | null;
        currency: string;
        status: string;
        due_date: string | null;
        external_url: string | null;
    };
    let campaignId = $state('');
    let rows = $state<Request[]>([]);
    let error = $state('');
    let busy = $state(false);
    let title = $state('');
    let amount = $state('');
    let url = $state('');
    async function load() {
        try {
            rows = await api.get<Request[]>(`/api/v1/campaigns/${Number(campaignId)}/payment-requests`);
        } catch (e) {
            error = e instanceof ApiRequestError ? e.message : 'Unable to load payment requests.';
        }
    }
    async function create() {
        busy = true;
        error = '';
        try {
            await api.post(`/api/v1/campaigns/${Number(campaignId)}/payment-requests`, {
                title,
                amount_cents: amount === '' ? null : Number(amount),
                currency: 'EUR',
                external_url: url || null
            });
            title = '';
            amount = '';
            url = '';
            await load();
        } catch (e) {
            error = e instanceof ApiRequestError ? e.message : 'Unable to create payment request.';
        } finally {
            busy = false;
        }
    }
</script>

<AdminHeader pageKey="payments" />
<section class="admin-detail">
    <label for="payment-campaign">Campaign ID</label><input
        id="payment-campaign"
        type="number"
        bind:value={campaignId}
    /><button class="secondary-button" disabled={!campaignId} onclick={load}>Load requests</button>
    <h2>Create payment request</h2>
    <label for="payment-title">Title</label><input id="payment-title" bind:value={title} /><label for="payment-amount"
        >Amount in cents</label
    ><input id="payment-amount" type="number" min="0" bind:value={amount} /><label for="payment-url"
        >Secure payment URL</label
    ><input id="payment-url" type="url" bind:value={url} /><button
        class="primary-button"
        disabled={busy || !campaignId || !title.trim()}
        onclick={create}>Create</button
    >{#if error}<p class="error-message" role="alert">{error}</p>{/if}
</section>
{#if rows.length}<section class="admin-detail">
        <h2>Payment requests</h2>
        <ul>
            {#each rows as row}<li>
                    <a href={`/admin/payments/${row.id}`}>{row.title}</a> — {row.amount_cents === null
                        ? 'Variable'
                        : `${row.amount_cents} ${row.currency}`} — {row.status}
                    {#if row.external_url}<a href={row.external_url} target="_blank" rel="noreferrer">Open payment link</a><img src={`https://quickchart.io/qr?text=${encodeURIComponent(row.external_url)}&size=180`} alt="QR code for payment link" />{/if}
                </li>{/each}
        </ul>
    </section>{:else if campaignId}<p role="status">No payment requests loaded.</p>{/if}
