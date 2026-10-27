<script lang="ts">
    import { goto } from '$app/navigation';
    import { api, ApiRequestError } from '#lib/api/index.js';
    import Tooltip from '#lib/components/shared/Tooltip.svelte';

    const invitePattern = /^[A-Za-z0-9_-]{8,}$/;
    let inviteCode = $state('');
    let inviteError = $state('');
    let inviteAccepted = $state(false);
    let email = $state('');
    let username = $state('');
    let password = $state('');
    let accountCreated = $state(false);
    let busy = $state(false);

    async function checkInvite() {
        const normalizedCode = inviteCode.trim();
        inviteCode = normalizedCode;
        inviteError = '';

        if (!invitePattern.test(normalizedCode)) {
            inviteError = 'Enter the complete invite code.';
            return;
        }

        busy = true;
        try {
            await api.post('/api/v1/auth/invite/validate', { invite_code: normalizedCode });
            inviteAccepted = true;
        } catch (error) {
            inviteError = error instanceof ApiRequestError ? error.message : 'Invite validation failed.';
        } finally {
            busy = false;
        }
    }

    async function createAccount() {
        if (!email.trim() || !username.trim() || password.length < 8) return;
        busy = true;
        inviteError = '';
        try {
            await api.post('/api/v1/auth/register', {
                invite_code: inviteCode,
                email,
                username,
                display_name: username,
                password,
                password_confirmation: password
            });
            api.invalidateCsrf();
            accountCreated = true;
            await goto('/dashboard/polls/current');
        } catch (error) {
            inviteError = error instanceof ApiRequestError ? error.message : 'Registration failed.';
        } finally {
            busy = false;
        }
    }
</script>

<svelte:head>
    <title>D&amp;D Portal Player Dashboard · Register</title>
    <meta name="description" content="Register for the D&amp;D Portal Player Dashboard with an invite code." />
</svelte:head>

<section class="auth-route-content">
    <div class="auth-route-content__heading">
        <h1>
            {accountCreated ? 'You’re on the list' : inviteAccepted ? 'Create your account' : 'Enter your invite code'}
        </h1>
        <p>
            {accountCreated
                ? 'Your account has been created. You can now sign in.'
                : inviteAccepted
                  ? 'Your invite is valid. Choose the details you’ll use to sign in.'
                  : 'The D&D Portal Dashboard is currently invite-only. Enter your code to continue.'}
        </p>
    </div>
    {#if accountCreated}
        <div class="success-mark">✓</div>
        <button class="p-button" onclick={() => (window.location.href = '/login')}>Go to sign in <span>→</span></button>
    {:else if !inviteAccepted}
        <div class="p-field">
            <label class="p-field__label" for="invite-code">Invite code</label>
            <Tooltip id="invite-code-help" text="Letters and numbers separated by hyphens">
                <input
                    class="p-input"
                    id="invite-code"
                    aria-describedby="invite-code-help"
                    bind:value={inviteCode}
                    onkeydown={(event) => event.key === 'Enter' && checkInvite()}
                    placeholder="ABCD-1234-ABCD-1234-AB12"
                    autocomplete="off"
                    spellcheck="false"
                />
            </Tooltip>
        </div>
        {#if inviteError}<p class="error-message">{inviteError}</p>{/if}
        <button class="p-button" disabled={!inviteCode.trim() || busy} onclick={checkInvite}
            >Continue <span>→</span></button
        >
    {:else}
        <div class="given-code">
            <span>Given invite code:</span><strong>{inviteCode}</strong><button
                onclick={() => (inviteAccepted = false)}
                aria-label="Change invite code">Change</button
            >
        </div>
        <div class="field-group">
            <label for="email">Email</label><input
                id="email"
                type="email"
                bind:value={email}
                placeholder="you@example.com"
                autocomplete="email"
            />
        </div>
        <div class="field-group">
            <label for="username">Username</label><input
                id="username"
                bind:value={username}
                placeholder="Choose a username"
                autocomplete="username"
            />
        </div>
        <div class="field-group">
            <label for="password">Password</label><input
                id="password"
                type="password"
                bind:value={password}
                placeholder="At least 8 characters"
                autocomplete="new-password"
            />
        </div>
        <button
            class="p-button"
            disabled={!email.trim() || !username.trim() || password.length < 8}
            onclick={createAccount}>Create account <span>→</span></button
        >
    {/if}
    <p class="footer-note">Already have an account? <a href="/login">Sign in</a></p>
</section>
