<script lang="ts">
    import AdminHeader from '#lib/components/admin/AdminHeader.svelte';
    import ApiUsersTable from '#lib/components/admin/ApiUsersTable.svelte';
</script>

<div class="admin-shell">
    <AdminHeader pageKey="users" />
    <ApiUsersTable />
</div>
