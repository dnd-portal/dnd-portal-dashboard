<script lang="ts">
    import { onMount } from 'svelte';
    import { page } from '$app/state';
    import { api, ApiRequestError } from '#lib/api/index.js';
    import { formatPortalDateTime } from '#lib/player/date.js';
    type Option = { id: number; label: string; respondent_count: number };
    type Respondent = {
        user_id: number;
        display_name: string;
        username: string | null;
        selected_option_ids: number[];
        updated_at: string;
    };
    type Result = {
        poll: { id: number; title: string; description: string | null; status: string };
        summary: { total_respondents: number; total_eligible_users: number };
        options: Option[];
        respondents: Respondent[];
        non_respondents: Respondent[];
    };
    let result = $state<Result | null>(null);
    let error = $state('');
    let loading = $state(true);
    let search = $state('');
    const pollId = $derived(Number(page.params.id));
    const filtered = $derived(
        result?.respondents.filter((item) =>
            `${item.display_name} ${item.username ?? ''}`.toLowerCase().includes(search.toLowerCase())
        ) ?? []
    );
    const percent = (count: number) =>
        result?.summary.total_respondents ? Math.round((count / result.summary.total_respondents) * 100) : 0;
    const barClass = (count: number) => `admin-results__bar-fill--${Math.min(10, Math.ceil(percent(count) / 10))}`;
    onMount(async () => {
        try {
            result = await api.get<Result>(`/api/v1/admin/global-polls/${pollId}/results`);
        } catch (e) {
            error = e instanceof ApiRequestError ? e.message : 'Unable to load poll results.';
        } finally {
            loading = false;
        }
    });
</script>

<svelte:head><title>Poll results · D&amp;D Portal</title></svelte:head>
{#if loading}<p class="admin-results-state" role="status">Loading poll results…</p>
{:else if error}<section class="admin-results-state" role="alert">
        <h1>Unable to load results</h1>
        <p>{error}</p>
        <button class="p-button p-button--secondary" onclick={() => location.reload()}>Try again</button>
    </section>
{:else if result}<div class="admin-results">
        <header class="admin-results__header">
            <p class="admin-results__eyebrow">POLL RESULTS</p>
            <h1>{result.poll.title}</h1>
            {#if result.poll.description}<p>{result.poll.description}</p>{/if}<span
                class={`admin-mvp-badge admin-mvp-badge--${result.poll.status}`}>{result.poll.status}</span
            >
            <p class="admin-results__note">Users can select multiple options.</p>
        </header>
        <section class="admin-results__summary" aria-label="Poll summary">
            <div>
                <strong>{result.summary.total_respondents} of {result.summary.total_eligible_users}</strong><span
                    >responded</span
                >
            </div>
            <div>
                <strong
                    >{result.summary.total_eligible_users
                        ? Math.round((result.summary.total_respondents / result.summary.total_eligible_users) * 100)
                        : 0}%</strong
                ><span>response rate</span>
            </div>
        </section>
        <section class="admin-results__options" aria-labelledby="options-heading">
            <h2 id="options-heading">Availability by option</h2>
            {#each result.options as option}<div class="admin-results__option">
                    <div class="admin-results__option-copy">
                        <strong>{option.label}</strong><span
                            >{option.respondent_count} of {result.summary.total_respondents} respondents · {percent(
                                option.respondent_count
                            )}%</span
                        >
                    </div>
                    <div
                        class="admin-results__bar"
                        role="progressbar"
                        aria-label={`${option.label}: ${percent(option.respondent_count)} percent`}
                        aria-valuenow={percent(option.respondent_count)}
                        aria-valuemin="0"
                        aria-valuemax="100"
                    >
                        <span class={barClass(option.respondent_count)}></span>
                    </div>
                </div>{/each}
        </section>
        <section class="admin-results__responses" aria-labelledby="responses-heading">
            <div class="admin-results__section-heading">
                <div>
                    <h2 id="responses-heading">Who selected what</h2>
                    <p>Each respondent may have selected more than one option.</p>
                </div>
                <label for="respondent-search"
                    >Search respondents<input
                        id="respondent-search"
                        class="p-input"
                        placeholder="Name or username"
                        bind:value={search}
                    /></label
                >
            </div>
            <div class="admin-results__matrix">
                <table>
                    <thead
                        ><tr
                            ><th>User</th>{#each result.options as option}<th>{option.label}</th>{/each}<th
                                >Last updated</th
                            ></tr
                        ></thead
                    ><tbody
                        >{#each filtered as respondent}<tr
                                ><th scope="row"
                                    ><strong>{respondent.display_name}</strong><small
                                        >@{respondent.username ?? 'unknown'}</small
                                    ></th
                                >{#each result.options as option}<td
                                        >{#if respondent.selected_option_ids.includes(option.id)}<span
                                                aria-label="Selected">✓</span
                                            >{:else}<span aria-label="Not selected">—</span>{/if}</td
                                    >{/each}<td
                                    ><time datetime={respondent.updated_at}
                                        >{formatPortalDateTime(respondent.updated_at)}</time
                                    ></td
                                ></tr
                            >{/each}</tbody
                    >
                </table>
            </div>
            <div class="admin-results__cards">
                {#each filtered as respondent}<article>
                        <strong>{respondent.display_name}</strong><small>@{respondent.username ?? 'unknown'}</small>
                        <ul>
                            {#each result.options as option}{#if respondent.selected_option_ids.includes(option.id)}<li>
                                        {option.label}
                                    </li>{/if}{/each}
                        </ul>
                        <time datetime={respondent.updated_at}>{formatPortalDateTime(respondent.updated_at)}</time>
                    </article>{/each}
            </div>
        </section>
        <section class="admin-results__nonrespondents" aria-labelledby="pending-heading">
            <h2 id="pending-heading">Still to respond</h2>
            {#if result.non_respondents.length}<ul>
                    {#each result.non_respondents as respondent}<li>
                            <strong>{respondent.display_name}</strong><span>@{respondent.username ?? 'unknown'}</span>
                        </li>{/each}
                </ul>{:else}<p>Everyone eligible has responded.</p>{/if}
        </section>
    </div>{/if}
