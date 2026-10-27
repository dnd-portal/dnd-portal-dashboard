<script lang="ts">
    let { signedIn = false }: { signedIn?: boolean } = $props();
</script>

<nav class="public-navigation" aria-label="Public navigation">
    <a class="public-navigation__brand" href="/" aria-label="D&D Portal home">
        <img src="/dashboard-dnd-portal-logo-wide.svg" alt="D&D Portal" width="176" height="48" />
    </a>
    <a href="/">Home</a>
    <a href="https://dnd-portal.com/">D&amp;D Portal Wiki</a>
    <a class="p-button" href="/dashboard">{signedIn ? 'Open dashboard' : 'Dashboard'}</a>
    {#if !signedIn}<a href="/login">Login</a><a href="/register">Register</a>{/if}
</nav>
