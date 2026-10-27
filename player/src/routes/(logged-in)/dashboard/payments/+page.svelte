<script lang="ts">
    import { onMount } from 'svelte';
    import { api, ApiRequestError } from '#lib/api/index.js';
    type Obligation = {
        id: number;
        amount_cents: number;
        paid_cents: number;
        outstanding_cents: number;
        currency: string;
        status: string;
    };
    let rows = $state<Obligation[]>([]);
    let error = $state('');
    onMount(async () => {
        try {
            rows = await api.get<Obligation[]>('/api/v1/me/payment-obligations');
        } catch (cause) {
            error = cause instanceof ApiRequestError ? cause.message : 'Unable to load payments.';
        }
    });
</script>

<section class="p-dashboard">
    <h1>My payments</h1>
    <p>Only your own payment obligations are shown.</p>
    {#if error}<p class="error-message" role="alert">{error}</p>{:else if rows.length === 0}<p role="status">
            No payment obligations.
        </p>{:else}<ul>
            {#each rows as row}<li>
                    Obligation {row.id}: {row.paid_cents}/{row.amount_cents}
                    {row.currency} — {row.outstanding_cents} outstanding ({row.status})
                </li>{/each}
        </ul>{/if}
</section>
