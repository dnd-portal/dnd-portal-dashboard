<script lang="ts">
    import { onMount } from 'svelte';
    import { api, ApiRequestError } from '#lib/api/index.js';
    import DataTable from './DataTable.svelte';
    import type { Column, EntityRow } from '#lib/admin/types.js';
    type User = {
        id: number;
        display_name: string;
        email: string | null;
        username: string | null;
        account_status: string;
        version: number;
        roles: string[];
    };
    type UserRow = EntityRow & { user: User };
    const columns: Column[] = [
        { key: 'display_name', label: 'Name', sortable: true },
        { key: 'email', label: 'Email', sortable: true },
        { key: 'account_status', label: 'Status', sortable: true }
    ];
    let users = $state<UserRow[]>([]);
    let error = $state('');
    onMount(async () => {
        try {
            const records = await api.get<User[]>('/api/v1/users');
            users = records.map((user) => ({
                id: String(user.id),
                label: user.display_name,
                actions: [],
                user,
                cells: {
                    display_name: { text: user.display_name, href: `/admin/users/${user.id}` },
                    email: { text: user.email ?? '—' },
                    account_status: { text: user.account_status }
                }
            }));
        } catch (e) {
            error = e instanceof ApiRequestError ? e.message : 'Unable to load users.';
        }
    });
</script>

{#if error}<p class="error-message" role="alert">{error}</p>{/if}
<DataTable rows={users} {columns} rowKey={(row) => row.id} rowLabel={(row) => row.label} caption="Users">
    {#snippet cell(row, key)}{#if row.cells[key]?.href}<a href={row.cells[key]?.href}>{row.cells[key]?.text ?? '—'}</a
            >{:else}{row.cells[key]?.text ?? '—'}{/if}{/snippet}
</DataTable>
