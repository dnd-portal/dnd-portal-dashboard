<script lang="ts">
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
    async function load() {
        try {
            rows = await api.get<Obligation[]>('/api/v1/me/payment-obligations');
        } catch (e) {
            error = e instanceof ApiRequestError ? e.message : 'Unable to load payments.';
        }
    }
    $effect(() => void load());
</script>

<main class="admin-content">
    <section class="admin-detail">
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
</main>
