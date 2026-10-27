<script lang="ts">
    import { api, ApiRequestError } from '#lib/api/index.js';
    type UserRecord = {
        id: number;
        display_name: string;
        email: string | null;
        username: string | null;
        roles: string[];
        version: number;
    };
    let { userId, user = null }: { userId: number; user?: UserRecord | null } = $props();
    let editing = $state(false);
    let saving = $state(false);
    let error = $state('');
    let success = $state('');
    let name = $state('');
    let email = $state('');
    let username = $state('');
    let roles = $state<string[]>([]);
    let version = $state(1);
    function begin() {
        if (!user) return;
        if (!user) return;
        name = user.display_name;
        email = user.email || '';
        username = user.username || '';
        roles = [...user.roles];
        version = user.version;
        error = '';
        success = '';
        editing = true;
    }
    function cancel() {
        editing = false;
        error = '';
    }
    async function save() {
        saving = true;
        error = '';
        success = '';
        try {
            const result = await api.patch<{ id: number; display_name: string; version: number }>(
                `/api/v1/users/${userId}`,
                { display_name: name, email, username, roles, version }
            );
            version = result.version;
            success = 'Changes saved.';
            editing = false;
        } catch (e) {
            error =
                e instanceof ApiRequestError && e.code === 'conflict'
                    ? 'This user was changed by another administrator. Reload before saving again.'
                    : e instanceof ApiRequestError
                      ? e.message
                      : 'Unable to save changes.';
        } finally {
            saving = false;
        }
    }
</script>

{#if !editing}<button class="secondary-button" type="button" onclick={begin}>Edit user</button>{:else}<form
        class="admin-detail__form"
        onsubmit={(event) => {
            event.preventDefault();
            void save();
        }}
    >
        <div class="field-group">
            <label for={`user-name-${userId}`}>Name</label><input
                id={`user-name-${userId}`}
                bind:value={name}
                maxlength="120"
                required
            />
        </div>
        <div class="field-group">
            <label for={`user-email-${userId}`}>Email</label><input
                id={`user-email-${userId}`}
                type="email"
                bind:value={email}
            />
        </div>
        <div class="field-group">
            <label for={`user-username-${userId}`}>Username</label><input
                id={`user-username-${userId}`}
                bind:value={username}
                maxlength="64"
            />
        </div>
        <fieldset>
            <legend>Portal roles</legend><label
                ><input type="checkbox" value="portal_admin" bind:group={roles} /> Portal admin</label
            ><label><input type="checkbox" value="campaign_dm" bind:group={roles} /> Campaign DM</label><label
                ><input type="checkbox" value="campaign_member" bind:group={roles} /> Campaign member</label
            >
        </fieldset>
        {#if error}<p class="error-message" role="alert">{error}</p>{/if}{#if success}<p
                class="success-message"
                role="status"
            >
                {success}
            </p>{/if}
        <button class="primary-button" type="submit" disabled={saving}>{saving ? 'Saving…' : 'Save changes'}</button
        ><button class="secondary-button" type="button" onclick={cancel} disabled={saving}>Cancel</button>
    </form>{/if}
