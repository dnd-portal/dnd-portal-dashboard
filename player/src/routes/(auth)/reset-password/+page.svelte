<script lang="ts">
    import { goto } from '$app/navigation';
    import { page } from '$app/state';
    import { onMount } from 'svelte';
    import { api, ApiRequestError } from '#lib/api/index.js';
    import Notification from '#lib/components/shared/Notification.svelte';

    let code = $state('');
    let identifier = $state('');
    let password = $state('');
    let confirmation = $state('');
    let busy = $state(false);
    let validated = $state(false);
    let error = $state('');
    let success = $state(false);

    onMount(() => {
        code = page.url.searchParams.get('code') ?? '';
        identifier = page.url.searchParams.get('identifier') ?? '';
        if (code && identifier) void validate();
    });

    async function validate() {
        busy = true;
        error = '';
        try {
            await api.post('/api/v1/auth/password-reset/validate', { code, identifier });
            validated = true;
        } catch (caught) {
            error = caught instanceof ApiRequestError ? caught.message : 'This reset code is invalid or expired.';
            validated = false;
        } finally {
            busy = false;
        }
    }

    async function complete() {
        busy = true;
        error = '';
        try {
            await api.post('/api/v1/auth/password-reset/complete', {
                code,
                identifier,
                password,
                password_confirmation: confirmation
            });
            success = true;
            setTimeout(() => void goto('/login'), 1800);
        } catch (caught) {
            error = caught instanceof ApiRequestError ? caught.message : 'Unable to reset your password.';
        } finally {
            busy = false;
        }
    }
</script>

<svelte:head><title>D&amp;D Portal · Reset password</title></svelte:head>
<section class="auth-route-content">
    <div class="auth-route-content__heading">
        <h1>Reset your password</h1>
        <p>Use the personal reset code from your organizer to choose a new password.</p>
    </div>
    {#if success}
        <Notification message="Your password was reset. Sending you to sign in…" onDismiss={() => {}} />
    {:else if !validated}
        <form
            class="p-form"
            onsubmit={(event) => {
                event.preventDefault();
                void validate();
            }}
        >
            <div class="p-field">
                <label class="p-field__label" for="reset-code">Reset code</label><input
                    class="p-input"
                    id="reset-code"
                    bind:value={code}
                    autocomplete="off"
                    required
                />
            </div>
            <div class="p-field">
                <label class="p-field__label" for="reset-identifier">Email or username</label><input
                    class="p-input"
                    id="reset-identifier"
                    bind:value={identifier}
                    autocomplete="username"
                    required
                />
            </div>
            {#if error}<Notification message={error} onDismiss={() => (error = '')} />{/if}
            <button class="p-button" type="submit" disabled={busy || !code.trim() || !identifier.trim()}
                >{busy ? 'Checking…' : 'Continue'} <span aria-hidden="true">→</span></button
            >
        </form>
    {:else}
        <form
            class="p-form"
            onsubmit={(event) => {
                event.preventDefault();
                void complete();
            }}
        >
            <div class="p-field">
                <label class="p-field__label" for="new-password">New password</label><input
                    class="p-input"
                    id="new-password"
                    type="password"
                    bind:value={password}
                    autocomplete="new-password"
                    minlength="8"
                    required
                />
            </div>
            <div class="p-field">
                <label class="p-field__label" for="confirm-password">Confirm new password</label><input
                    class="p-input"
                    id="confirm-password"
                    type="password"
                    bind:value={confirmation}
                    autocomplete="new-password"
                    minlength="8"
                    required
                />
            </div>
            {#if password && confirmation && password !== confirmation}<Notification
                    message="Passwords do not match."
                    onDismiss={() => {}}
                />{/if}
            {#if error}<Notification message={error} onDismiss={() => (error = '')} />{/if}
            <button class="p-button" type="submit" disabled={busy || password.length < 8 || password !== confirmation}
                >{busy ? 'Resetting…' : 'Set new password'} <span aria-hidden="true">→</span></button
            >
        </form>
    {/if}
    <p class="footer-note"><a href="/login">Back to sign in</a></p>
</section>
