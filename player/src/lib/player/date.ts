const PORTAL_TIME_ZONE = 'Europe/Amsterdam';

function asUtcDate(value: string, dateOnly = false): Date {
    const normalized = dateOnly ? `${value}T12:00:00` : value.replace(' ', 'T');
    return new Date(/[zZ]|[+-]\d{2}:?\d{2}$/.test(normalized) ? normalized : `${normalized}Z`);
}

export function formatPortalDate(value: string): string {
    return new Intl.DateTimeFormat('nl-NL', { dateStyle: 'medium', timeZone: PORTAL_TIME_ZONE }).format(
        asUtcDate(value, true)
    );
}

export function formatPortalTime(value: string | null): string {
    if (!value) return 'Time to be announced';
    return new Intl.DateTimeFormat('nl-NL', {
        hour: '2-digit',
        minute: '2-digit',
        timeZone: PORTAL_TIME_ZONE
    }).format(asUtcDate(value));
}

export function formatPortalDateTime(value: string | null): string {
    if (!value) return 'Time to be announced';
    return new Intl.DateTimeFormat('nl-NL', {
        dateStyle: 'medium',
        timeStyle: 'short',
        timeZone: PORTAL_TIME_ZONE
    }).format(asUtcDate(value));
}
