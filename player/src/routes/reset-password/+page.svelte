<script lang="ts">
    import { page } from '$app/state';
    import { api, ApiRequestError } from '#lib/api/index.js';
    import AuthLayout from '#lib/components/player/AuthLayout.svelte';

    let code = $state(page.url.searchParams.get('code') ?? '');
    let identifier = $state('');
    let verified = $state(false);
    let password = $state('');
    let confirmation = $state('');
    let message = $state('');
    let error = $state('');

    async function submit() {
        error = '';
        message = '';
        try {
            await api.post('/api/v1/auth/password-reset/complete', {
                code,
                identifier,
                password,
                password_confirmation: confirmation
            });
            message = 'Your password was changed. You can now sign in.';
            code = '';
            password = '';
            confirmation = '';
        } catch (caught) {
            error = caught instanceof ApiRequestError ? caught.message : 'Reset failed.';
        }
    }

    async function validate() {
        error = '';
        try {
            await api.post('/api/v1/auth/password-reset/validate', { code, identifier });
            verified = true;
        } catch (caught) {
            verified = false;
            error = caught instanceof ApiRequestError ? caught.message : 'Reset code validation failed.';
        }
    }
</script>

<svelte:head><title>D&amp;D Portal · Reset password</title></svelte:head>

<AuthLayout
    title="Choose a new password"
    description="Use the reset code from your organizer. The code is single-use and expires after 30 minutes."
>
    <div class="field-group">
        <label for="reset-identifier">Email or username</label><input
            id="reset-identifier"
            bind:value={identifier}
            autocomplete="username"
        />
    </div>
    <button class="secondary-button" type="button" disabled={!code || !identifier} onclick={validate}
        >Validate code</button
    >
    <div class="field-group">
        <label for="reset-code">Reset code</label><input
            id="reset-code"
            bind:value={code}
            autocomplete="one-time-code"
        />
    </div>
    <div class="field-group">
        <label for="password">New password</label><input
            id="password"
            type="password"
            bind:value={password}
            autocomplete="new-password"
        />
    </div>
    <div class="field-group">
        <label for="confirmation">Confirm password</label><input
            id="confirmation"
            type="password"
            bind:value={confirmation}
            autocomplete="new-password"
        />
    </div>
    {#if error}<p class="error-message">{error}</p>{/if}
    {#if message}<p class="success-message">{message}</p>{/if}
    <button
        class="primary-button"
        disabled={!verified || password.length < 8 || password !== confirmation}
        onclick={submit}>Set password <span>→</span></button
    >
    <p class="footer-note"><a href="/">Return to sign in</a></p>
</AuthLayout>
