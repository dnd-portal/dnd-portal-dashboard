<script lang="ts">
    import AdminHeader from '#lib/components/admin/AdminHeader.svelte';
    import { page } from '$app/state';
    import { api, ApiRequestError } from '#lib/api/index.js';
    type Obligation = {
        id: number;
        user_id: number;
        amount_cents: number;
        currency: string;
        status: string;
        paid_cents: number;
    };
    type Request = {
        id: number;
        title: string;
        description: string | null;
        amount_cents: number | null;
        currency: string;
        status: string;
        due_date: string | null;
        obligations: Obligation[];
    };
    const id = Number(page.params.id);
    let item = $state<Request | null>(null);
    let error = $state('');
    let busy = $state(false);
    let obligationId = $state('');
    let entryAmount = $state('');
    let reference = $state('');
    let correctionOf = $state('');
    async function load() {
        try {
            item = await api.get<Request>(`/api/v1/payment-requests/${id}`);
        } catch (e) {
            error = e instanceof ApiRequestError ? e.message : 'Unable to load payment request.';
        }
    }
    async function record() {
        busy = true;
        error = '';
        try {
            await api.post(`/api/v1/payment-obligations/${Number(obligationId)}/entries`, {
                amount_cents: Number(entryAmount),
                currency: item?.currency,
                external_reference: reference || null
                , correction_of_entry_id: correctionOf ? Number(correctionOf) : null
            });
            entryAmount = '';
            reference = '';
            correctionOf = '';
            await load();
        } catch (e) {
            error = e instanceof ApiRequestError ? e.message : 'Unable to record payment.';
        } finally {
            busy = false;
        }
    }
    $effect(() => {
        void load();
    });
</script>

<AdminHeader pageKey="payments" title={item?.title ?? 'Payment request'} subtitle={`Record ID: ${id}`} />
{#if item}<section class="admin-detail">
        <p>
            {item.amount_cents === null ? 'Variable amount' : `${item.amount_cents} ${item.currency}`} · {item.status}
        </p>
        <h2>Obligations</h2>
        <ul>
            {#each item.obligations as obligation}<li>
                    User {obligation.user_id}: {obligation.paid_cents}/{obligation.amount_cents}
                    {obligation.currency} ({obligation.status})
                </li>{/each}
        </ul>
        <h2>Record received payment</h2>
        <label for="obligation-id">Obligation ID</label><input
            id="obligation-id"
            type="number"
            bind:value={obligationId}
        /><label for="entry-amount">Amount in cents</label><input
            id="entry-amount"
            type="number"
            min="1"
            bind:value={entryAmount}
        /><label for="entry-reference">External reference</label><input
            id="entry-reference"
            bind:value={reference}
        /><label for="correction-entry">Correction of entry (optional)</label><input
            id="correction-entry"
            type="number"
            min="1"
            bind:value={correctionOf}
        /><button class="primary-button" disabled={busy || !obligationId || !entryAmount} onclick={record}
            >Record payment</button
        >
    </section>{:else}<p role="status">Loading…</p>{/if}{#if error}<p class="error-message" role="alert">{error}</p>{/if}
