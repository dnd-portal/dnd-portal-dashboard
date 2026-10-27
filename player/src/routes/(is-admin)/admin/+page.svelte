<script lang="ts">
    import AdminHeader from '#lib/components/admin/AdminHeader.svelte';
    import AdminOverview from '#lib/components/admin/AdminOverview.svelte';
</script>

<svelte:head>
    <title>Overview Admin | D&amp;D Portal Player Dashboard</title>
    <meta name="description" content="Get an overview of your players, campaigns and sessions." />
</svelte:head>

<div class="admin-shell">
    <AdminHeader pageKey="overview" />
    <AdminOverview />
</div>
