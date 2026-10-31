<script lang="ts">
    import Table from "#lib/components/Table.svelte";
    import Page from "#lib/layout/Page.svelte";
</script>

<svelte:head>
    <title>Permissions | D&D Portal Dashboard</title>
    <meta
        name="description"
        content="Explore the permissions that control access to admin pages and user management in D&D Portal Dashboard."
    />
    <meta name="robots" content="noindex" />
</svelte:head>

<Page
    showBreadcrumbs={true}
    eyebrow="Admin reference"
    title="Permissions"
    description="Every adventurer has their proficiencies. These permissions determine which admin areas a user can access and which actions they can perform."
>
    <Table caption="Current permissions">
        {#snippet header()}
            <tr>
                <th scope="col">Permission</th>
                <th scope="col">Purpose</th>
            </tr>
        {/snippet}

        <tr>
            <th scope="row"><code>canVisitAdmin</code></th>
            <td
                >Access the entire /admin area. Required before accessing any
                admin page.</td
            >
        </tr>
        <tr>
            <th scope="row"><code>canVisitAdminPage</code></th>
            <td>View the main admin dashboard at /admin.</td>
        </tr>
        <tr>
            <th scope="row"><code>canVisitAdminUsersPage</code></th>
            <td>View user management at /admin/users.</td>
        </tr>
        <tr>
            <th scope="row"><code>canEditUsers</code></th>
            <td
                >Edit user details. Does not grant permission to delete users.</td
            >
        </tr>
        <tr>
            <th scope="row"><code>canDeleteUsers</code></th>
            <td
                >Delete users. Does not grant permission to edit user details.</td
            >
        </tr>
    </Table>

    <section aria-labelledby="permission-behaviour-title">
        <h2 id="permission-behaviour-title">How permissions work</h2>

        <p>
            Each permission is enabled or disabled for a user. A permission must
            explicitly be set to true to grant access. Missing permissions are
            treated as denied.
        </p>

        <p>
            Access to an admin page does not automatically grant permission to
            edit or delete its data. Those actions require their own checks.
        </p>

        <ul>
            <li>
                <strong>401 — Sign-in required:</strong> no authenticated user is
                available.
            </li>
            <li>
                <strong>403 — Proficiency required:</strong> the user does not have
                the required permission.
            </li>
        </ul>
    </section>
</Page>
