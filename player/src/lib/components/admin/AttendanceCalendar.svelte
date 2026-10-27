<script lang="ts">
    import { api, ApiRequestError } from '#lib/api/index.js';
    import Popover from '#lib/components/shared/Popover.svelte';
    type Availability = { user_id: number; availability_date: string; status: string; display_name: string };
    let availability = $state<Availability[]>([]);
    let error = $state('');
    let loading = $state(true);
    let currentMonth = $state(new Date(2026, 9, 1));
    let year = $derived(currentMonth.getFullYear());
    let month = $derived(currentMonth.getMonth());
    let monthName = $derived(new Intl.DateTimeFormat('en-US', { month: 'long', year: 'numeric' }).format(currentMonth));
    let leadingDays = $derived((new Date(year, month, 1).getDay() + 6) % 7);
    let daysInMonth = $derived(new Date(year, month + 1, 0).getDate());
    let calendarDays = $derived(
        Array.from({ length: leadingDays + daysInMonth }, (_, index) =>
            index < leadingDays ? null : index - leadingDays + 1
        )
    );
    function changeMonth(offset: number) {
        currentMonth = new Date(year, month + offset, 1);
    }
    function dateFor(day: number) {
        return `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
    }
    function unavailableFor(day: number) {
        return availability.filter((item) => item.availability_date === dateFor(day) && item.status === 'unavailable');
    }
    function namesFor(day: number) {
        return unavailableFor(day).map((item) => item.display_name);
    }
    async function load() {
        loading = true;
        try {
            availability = await api.get<Availability[]>('/api/v1/availability');
            error = '';
        } catch (e) {
            error = e instanceof ApiRequestError ? e.message : 'Unable to load availability.';
        } finally {
            loading = false;
        }
    }
    $effect(() => {
        void load();
    });
</script>

<section class="admin-calendar">
    <div class="admin-calendar__heading">
        <div>
            <p class="admin-calendar__eyebrow">AVAILABILITY</p>
            <div class="admin-calendar__month">
                <button type="button" aria-label="Previous month" onclick={() => changeMonth(-1)}>←</button>
                <h2>{monthName}</h2>
                <button type="button" aria-label="Next month" onclick={() => changeMonth(1)}>→</button>
            </div>
        </div>
        <div class="admin-calendar__legend">
            Fewer unavailable <span class="admin-calendar__swatch--low"></span><span
                class="admin-calendar__swatch--high"
            ></span> More unavailable
        </div>
    </div>
    <div class="admin-calendar__weekdays">
        {#each ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as day}<span>{day}</span>{/each}
    </div>
    {#if loading}<p role="status">Loading availability…</p>{:else if error}<p class="error-message" role="alert">
            {error}
        </p>{/if}
    <div class="admin-calendar__grid">
        {#each calendarDays as day}{#if day === null}<div
                    class="admin-calendar__empty"
                ></div>{:else}{@const unavailable = unavailableFor(day)}
                <div
                    class:admin-calendar__day--unavailable={unavailable.length > 0}
                    class="admin-calendar__day"
                    style={`--unavailable: ${unavailable.length}`}
                >
                    <strong>{day}</strong>{#if unavailable.length > 0}<Popover
                            label={`${unavailable.length} unavailable`}
                            ><ul class="popover__list">
                                {#each namesFor(day) as name, index (`${index}-${name}`)}<li>{name}</li>{/each}
                            </ul></Popover
                        >{/if}
                </div>{/if}{/each}
    </div>
</section>
