<script lang="ts">
    import { afterNavigate } from "$app/navigation";
    import type { LayoutProps } from "./$types";
    import { tick } from "svelte";

    import Nav from "#lib/layout/Nav.svelte";
    import Footer from "#lib/layout/Footer.svelte";

    import "#lib/scss/style.scss";

    let { children }: LayoutProps = $props();

    afterNavigate(() => {
        if (!import.meta.env.DEV) return;

        void (async () => {
            await tick();

            const { checkPage } = await import("../lib/dev/check-page");
            await checkPage();
        })().catch(console.error);
    });
</script>

<svelte:head>
    <link rel="icon" href="/favicon.ico" />
</svelte:head>

<Nav />
{@render children()}
<Footer />
