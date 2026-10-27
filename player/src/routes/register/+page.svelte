<script lang="ts">
    import { api, ApiRequestError } from '#lib/api/index.js';

    const invitePattern = /^[A-Za-z0-9_-]{32,}$/;
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
            accountCreated = true;
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

<main class="register-shell">
    <section class="register-card">
        <div class="brand-mark">✦</div>
        <p class="eyebrow">D&amp;D PORTAL PLAYER DASHBOARD</p>
        <h1>
            {accountCreated ? 'You’re on the list' : inviteAccepted ? 'Create your account' : 'Enter your invite code'}
        </h1>
        <p class="intro">
            {accountCreated
                ? 'Your testing account has been created. You can now sign in to your campaign dashboard.'
                : inviteAccepted
                  ? 'Your invite is valid. Choose the details you’ll use to sign in.'
                  : 'The D&D Portal is currently invite-only. Enter your code to continue.'}
        </p>

        {#if accountCreated}
            <div class="success-mark">✓</div>
            <button class="primary-button" onclick={() => (window.location.href = '/?role=player')}
                >Go to sign in <span>→</span></button
            >
        {:else if !inviteAccepted}
            <label for="invite-code">Invite code</label>
            <input
                id="invite-code"
                bind:value={inviteCode}
                onkeydown={(event) => event.key === 'Enter' && checkInvite()}
                placeholder="WINF-3525-FKMW-3522-LA22"
                autocomplete="off"
                spellcheck="false"
            />
            <p class="format-hint">Letters and numbers separated by hyphens</p>
            {#if inviteError}<p class="error-message">{inviteError}</p>{/if}
            <button class="primary-button" onclick={checkInvite}>Continue <span>→</span></button>
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
                class="primary-button"
                disabled={!email.trim() || !username.trim() || password.length < 8}
                onclick={createAccount}>Create account <span>→</span></button
            >
        {/if}
        <p class="footer-note">Already have an account? <a href="/?role=player">Sign in</a></p>
    </section>
</main>
