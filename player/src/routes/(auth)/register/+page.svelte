<script lang="ts">
    import { goto } from '$app/navigation';
    import { page } from '$app/state';
    import { api, ApiRequestError } from '#lib/api/index.js';
    import Tooltip from '#lib/components/shared/Tooltip.svelte';

    const invitePattern = /^[A-Za-z0-9_-]{8,}$/;
    let inviteCode = $state('');
    let inviteError = $state('');
    let inviteAccepted = $state(false);
    let pendingUser = $state(false);
    let pendingDisplayName = $state('');
    let pendingUsername = $state('');
    let email = $state('');
    let displayName = $state('');
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
            const result = await api.post<{
                has_pending_user: boolean;
                display_name: string | null;
                username: string | null;
            }>('/api/v1/auth/invite/validate', { invite_code: normalizedCode });
            pendingUser = result.has_pending_user;
            pendingDisplayName = result.display_name ?? '';
            pendingUsername = result.username ?? '';
            inviteAccepted = true;
        } catch (error) {
            inviteError = error instanceof ApiRequestError ? error.message : 'Invite validation failed.';
        } finally {
            busy = false;
        }
    }

    async function createAccount() {
        if (
            !email.trim() ||
            (pendingUser ? !pendingUsername.trim() : !username.trim()) ||
            (!pendingUser && !displayName.trim()) ||
            password.length < 8
        )
            return;
        busy = true;
        inviteError = '';
        try {
            await api.post('/api/v1/auth/register', {
                invite_code: inviteCode,
                email,
                username: pendingUser ? pendingUsername : username,
                display_name: pendingUser ? pendingDisplayName : username,
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

    const initialCode = page.url.searchParams.get('code');
    if (initialCode) {
        inviteCode = initialCode;
        void checkInvite();
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
        <form
            class="p-form auth-register-form"
            onsubmit={(event) => {
                event.preventDefault();
                void checkInvite();
            }}
        >
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
            {#if inviteError}<p class="p-field__error" role="alert">{inviteError}</p>{/if}
            <button class="p-button p-button--full" type="submit" disabled={!inviteCode.trim() || busy}
                >{busy ? 'Checking invite…' : 'Continue'} <span aria-hidden="true">→</span></button
            >
        </form>
    {:else}
        <div class="invite-summary">
            <p class="invite-summary__label">You were invited as</p>
            <p class="invite-summary__name">{pendingUser ? pendingDisplayName : 'A new portal member'}</p>
            {#if pendingUser}<p class="invite-summary__username">@{pendingUsername}</p>{/if}
            <button
                class="p-button p-button--secondary invite-summary__change"
                type="button"
                onclick={() => (inviteAccepted = false)}
            >
                Use a different invite
            </button>
        </div>
        <form
            class="p-form auth-register-form"
            onsubmit={(event) => {
                event.preventDefault();
                void createAccount();
            }}
        >
            <div class="p-field">
                <label class="p-field__label" for="email">Email</label><input
                    class="p-input"
                    id="email"
                    type="email"
                    bind:value={email}
                    placeholder="you@example.com"
                    autocomplete="email"
                    required
                />
            </div>
            {#if !pendingUser}<div class="p-field">
                    <label class="p-field__label" for="display-name">Display name</label><input
                        class="p-input"
                        id="display-name"
                        bind:value={displayName}
                        placeholder="Your name"
                        autocomplete="name"
                        required
                    />
                </div>{/if}
            {#if !pendingUser}<div class="p-field">
                    <label class="p-field__label" for="username">Username</label><input
                        class="p-input"
                        id="username"
                        bind:value={username}
                        placeholder="Choose a username"
                        autocomplete="username"
                        required
                    />
                </div>{/if}
            <div class="p-field">
                <label class="p-field__label" for="password">Password</label><input
                    class="p-input"
                    id="password"
                    type="password"
                    bind:value={password}
                    placeholder="At least 8 characters"
                    autocomplete="new-password"
                    minlength="8"
                    required
                />
            </div>
            {#if inviteError}<p class="p-field__error" role="alert">{inviteError}</p>{/if}
            <button
                class="p-button p-button--full"
                type="submit"
                disabled={!email.trim() ||
                    (pendingUser ? !pendingUsername.trim() : !username.trim()) ||
                    password.length < 8}
                >{busy ? 'Creating account…' : 'Create account'} <span aria-hidden="true">→</span></button
            >
        </form>
    {/if}
    <p class="footer-note">Already have an account? <a href="/login">Sign in</a></p>
</section>
