<script lang="ts">
    import { page } from '$app/state';
    import ApiCampaignDetail from '#lib/components/admin/ApiCampaignDetail.svelte';
</script>

<ApiCampaignDetail id={Number(page.params.id)} type="campaign" />
