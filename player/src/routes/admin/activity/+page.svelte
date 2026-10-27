<script lang="ts">
    import AdminHeader from '#lib/components/admin/AdminHeader.svelte';
    import EntityTable from '#lib/components/admin/EntityTable.svelte';
</script>

<div class="admin-shell">
    <AdminHeader pageKey="activity" />
    <EntityTable entity="activity" />
</div>
