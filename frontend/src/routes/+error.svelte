<script lang="ts">
    import { page } from "$app/state";
    import { getErrorInfo } from "../lib/api/errors";

    const info = $derived(getErrorInfo(page.status));
</script>

<svelte:head>
    <title>
        {page.status} — {info.title} | D&D Portal Dashboard
    </title>
    <meta name="robots" content="noindex" />
</svelte:head>

<section class="error-page" aria-labelledby="error-title">
    <p class="error-code">{page.status}</p>

    <h1 id="error-title">{info.title}</h1>

    <p>{page.error?.message ?? info.message}</p>

    <a class="button button-primary" href="/">
        Return to familiar lands
        <span aria-hidden="true">→</span>
    </a>
</section>
