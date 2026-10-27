<script lang="ts">
    import AdminHeader from '#lib/components/admin/AdminHeader.svelte';
    import ApiMembershipTable from '#lib/components/admin/ApiMembershipTable.svelte';
</script>

<div class="admin-shell">
    <AdminHeader pageKey="memberships" />
    <ApiMembershipTable />
</div>
