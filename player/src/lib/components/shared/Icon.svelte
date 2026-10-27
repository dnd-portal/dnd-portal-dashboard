<script lang="ts">
    let { name, size = 16 }: { name: string; size?: number } = $props();
</script>

<svg
    class="admin-icon"
    width={size}
    height={size}
    viewBox="0 0 24 24"
    fill="none"
    stroke="currentColor"
    stroke-width="1.7"
    stroke-linecap="round"
    stroke-linejoin="round"
    aria-hidden="true"
>
    {#if name === 'users'}<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" /><circle cx="9" cy="7" r="4" /><path
            d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"
        />
    {:else if name === 'campaigns'}<path d="m12 3 8 4.5-8 4.5-8-4.5L12 3Z" /><path
            d="m4 12 8 4.5 8-4.5M4 16.5l8 4.5 8-4.5"
        />
    {:else if name === 'sessions'}<rect x="3" y="4" width="18" height="17" rx="2" /><path d="M16 2v4M8 2v4M3 10h18" />
    {:else if name === 'polls'}<path d="M4 19V5M4 5h12l-2 4 2 4H4" />
    {:else if name === 'attendance'}<rect x="3" y="4" width="18" height="17" rx="2" /><path
            d="M16 2v4M8 2v4M3 10h18M8 14h.01M12 14h.01M16 14h.01M8 18h.01M12 18h.01"
        />
    {:else if name === 'one-shots'}<path
            d="m12 3 2.2 5.1 5.5.5-4.1 3.6 1.2 5.4-4.8-2.8-4.8 2.8 1.2-5.4-4.1-3.6 5.5-.5L12 3Z"
        />
    {:else if name === 'memberships'}<circle cx="12" cy="8" r="3" /><path d="M5 21a7 7 0 0 1 14 0M19 8h3M20.5 6.5v3" />
    {:else if name === 'invite-codes'}<path d="m15.5 7.5-7 7M7 7h.01M17 17h.01" /><path
            d="M7 3h10a4 4 0 0 1 4 4v10a4 4 0 0 1-4 4H7a4 4 0 0 1-4-4V7a4 4 0 0 1 4-4Z"
        />
    {:else if name === 'payments'}<rect x="3" y="5" width="18" height="14" rx="2" /><path d="M3 10h18M7 15h3" />
    {:else if name === 'error'}<circle cx="12" cy="12" r="9" /><path d="m9 9 6 6M15 9l-6 6" />
    {:else if name === 'success'}<circle cx="12" cy="12" r="9" /><path d="m8 12 2.5 2.5L16 9" />
    {:else if name === 'warning'}<path d="m12 3 9 17H3L12 3Z" /><path d="M12 9v4M12 16h.01" />
    {:else}<path d="M4 19V5M4 19h16M8 16v-4M12 16V8M16 16v-6" />{/if}
</svg>
