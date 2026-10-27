<script lang="ts">
    import { goto } from '$app/navigation';
    import { page } from '$app/state';
    import { api, ApiRequestError } from '#lib/api/index.js';
    let identifier = $state('');
    let password = $state('');
    let loginError = $state('');
    let busy = $state(false);

    async function login() {
        busy = true;
        loginError = '';
        try {
            await api.post('/api/v1/auth/login', { identifier, password });
            api.invalidateCsrf();
            const returnTo = page.url.searchParams.get('returnTo');
            await goto(returnTo?.startsWith('/') && !returnTo.startsWith('//') ? returnTo : '/dashboard');
        } catch (caught) {
            loginError = caught instanceof ApiRequestError ? caught.message : 'Unable to sign in.';
        } finally {
            busy = false;
        }
    }
</script>

<svelte:head><title>D&amp;D Portal · Sign in</title></svelte:head>
<section class="auth-route-content">
    <div class="auth-route-content__heading">
        <h1>Sign in</h1>
        <p>Use your account to continue to your campaign dashboard.</p>
    </div>
    <form
        class="p-form auth-login-form"
        onsubmit={(event) => {
            event.preventDefault();
            void login();
        }}
    >
        <div class="p-field">
            <label class="p-field__label" for="login-identifier">Email or username</label>
            <input class="p-input" id="login-identifier" bind:value={identifier} autocomplete="username" required />
        </div>
        <div class="p-field">
            <label class="p-field__label" for="login-password">Password</label>
            <input
                class="p-input"
                id="login-password"
                type="password"
                bind:value={password}
                autocomplete="current-password"
                required
            />
        </div>
        {#if loginError}<p class="error-message" role="alert">{loginError}</p>{/if}
        <button class="p-button" type="submit" disabled={busy || !identifier.trim() || !password}
            >{busy ? 'Signing in…' : 'Continue with email'} <span aria-hidden="true">→</span></button
        >
    </form>
    <p class="footer-note"><a href="/register">New here?</a> Ask your organizer for an invitation.</p>
</section>
