type ApiFetch = (input: RequestInfo | URL, init?: RequestInit) => Promise<Response>;
type ApiEnvelope<T> = { data: T };
type ApiUser = {
    id: number;
    display_name: string;
    email: string | null;
    username: string | null;
    account_status: string;
    roles: string[];
};
type ApiCampaign = { id: number; name: string; slug: string; type: string; location: string | null };
type ApiSession = {
    id: number;
    campaign_id: number;
    title: string;
    session_date: string;
    starts_at: string | null;
    status: string;
};
type ApiMember = { campaign_id: number; user_id: number };
type ApiPoll = { id: number; title: string; status: string };
type ApiPayment = {
    id: number;
    title: string;
    amount_cents: number | null;
    currency: string;
    status: string;
    external_url?: string | null;
};

async function get<T>(fetcher: ApiFetch, path: string): Promise<T> {
    const response = await fetcher(path, { headers: { Accept: 'application/json' }, credentials: 'include' });
    const payload = (await response.json()) as ApiEnvelope<T> | { error?: { message?: string } };
    if (!response.ok || !('data' in payload)) throw new Error('API request failed: ' + path);
    return payload.data;
}

export async function getAdminData(fetcher: ApiFetch = globalThis.fetch) {
    const [current, users, campaigns] = await Promise.all([
        get<ApiUser>(fetcher, '/api/v1/auth/me'),
        get<ApiUser[]>(fetcher, '/api/v1/users'),
        get<ApiCampaign[]>(fetcher, '/api/v1/campaigns')
    ]);
    const userRows = users.map((item) => ({
        id: item.id,
        name: item.display_name,
        email: item.email ?? '',
        username: item.username ?? '',
        role: item.roles[0] ?? 'player',
        registered: item.account_status === 'active'
    }));
    const campaignData = await Promise.all(
        campaigns.map(async (campaign) => {
            const [members, sessions, polls, payments] = await Promise.all([
                get<ApiMember[]>(fetcher, `/api/v1/campaigns/${campaign.id}/members`),
                get<ApiSession[]>(fetcher, `/api/v1/campaigns/${campaign.id}/sessions`),
                get<ApiPoll[]>(fetcher, `/api/v1/campaigns/${campaign.id}/polls`),
                get<ApiPayment[]>(fetcher, `/api/v1/campaigns/${campaign.id}/payment-requests`)
            ]);
            return { campaign, members, sessions, polls, payments };
        })
    );
    const dashboard = await get<{
        admin?: { activity?: Array<{ id: number; event_type: string; occurred_at: string }> };
    }>(fetcher, '/api/v1/dashboard');
    const sessions = campaignData.flatMap(({ sessions: rows }) => rows);
    const memberships = campaignData.flatMap(({ members }) =>
        members.map((member) => ({ userId: member.user_id, campaignId: member.campaign_id }))
    );
    const paymentRequests = campaignData.flatMap(({ campaign, payments }) =>
        payments.map((payment) => ({
            id: payment.id,
            title: payment.title,
            campaignId: campaign.id,
            amount:
                payment.amount_cents === null ? '—' : `${payment.currency} ${(payment.amount_cents / 100).toFixed(2)}`,
            paymentUrl: payment.external_url ?? undefined,
            status: payment.status
        }))
    );
    return {
        user: userRows.find((item) => item.id === current.id) ?? {
            id: current.id,
            name: current.display_name,
            email: current.email ?? '',
            username: current.username ?? '',
            role: current.roles[0] ?? 'player',
            registered: current.account_status === 'active'
        },
        users: userRows,
        campaigns: campaigns.map((campaign) => ({ ...campaign, location: campaign.location ?? '' })),
        oneShots: campaigns
            .filter((campaign) => campaign.type === 'one_shot')
            .map((campaign) => ({
                id: campaign.id,
                title: campaign.name,
                date: '',
                location: campaign.location ?? '',
                members: [] as string[]
            })),
        memberships,
        sessions: sessions.map((session) => ({
            id: session.id,
            title: session.title,
            date: session.session_date,
            start: session.starts_at ?? '',
            campaignId: session.campaign_id,
            status: session.status,
            members: [] as string[]
        })),
        polls: campaignData.flatMap(({ polls }) =>
            polls.map((poll) => ({
                id: poll.id,
                title: poll.title,
                options: [] as string[],
                status: poll.status === 'open' ? ('Open' as const) : ('Closed' as const)
            }))
        ),
        pollResponses: [] as Array<{ pollId: number; userId: number; option: string }>,
        paymentRequests,
        paymentRequestUsers: [] as Array<{ paymentRequestId: number; userId: number; paid: boolean; paidAt?: string }>,
        inviteCodes: [] as Array<{ id: number; code: string; userId: number; used: boolean }>,
        organizerDashboard: [] as never[],
        activity: (dashboard.admin?.activity ?? []).map((event) => ({
            id: event.id,
            userId: 0,
            type: event.event_type,
            message: event.event_type,
            createdAt: event.occurred_at
        })),
        availability: [] as never[]
    };
}
