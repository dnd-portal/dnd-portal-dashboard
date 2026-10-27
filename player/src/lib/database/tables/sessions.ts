// Table of campaign sessions. Add session records here as the mock database grows.
export type Session = {
    id: number;
    title: string;
    date: string;
    start: string;
    campaignId?: number;
    status?: string;
    members: string[];
};

export const sessions: Session[] = [
    {
        id: 1,
        title: 'Party 1',
        date: '2026-09-16',
        start: '20:00',
        campaignId: 1,
        status: 'Played',
        members: ['4', '3', '2', '15']
    },
    {
        id: 2,
        title: 'Party 1',
        date: '2026-10-07',
        start: '20:00',
        campaignId: 1,
        status: 'Played',
        members: ['4', '14', '3', '2', '15']
    },
    {
        id: 3,
        title: 'Party 1',
        date: '2026-10-14',
        start: '20:00',
        campaignId: 1,
        status: 'Played',
        members: ['4', '3', '2', '15']
    },
    {
        id: 4,
        title: 'Party 2',
        date: '2026-09-30',
        start: '20:00',
        campaignId: 2,
        status: 'Played',
        members: ['11', '6', '4', '1']
    },
    {
        id: 5,
        title: 'Party 2',
        date: '2026-10-05',
        start: '20:00',
        campaignId: 2,
        status: 'Played',
        members: ['7', '11', '1']
    },
    {
        id: 6,
        title: 'Party 2',
        date: '2026-10-15',
        start: '20:00',
        campaignId: 2,
        status: 'Played',
        members: ['7', '6', '4', '1']
    },
    {
        id: 7,
        title: 'Party 2',
        date: '2026-10-20',
        start: '20:00',
        campaignId: 2,
        status: 'Played',
        members: ['11', '6', '4', '1']
    },
    {
        id: 8,
        title: 'Party 3',
        date: '2026-09-22',
        start: '20:00',
        campaignId: 3,
        status: 'Played',
        members: ['3', '2', '15']
    },
    {
        id: 9,
        title: 'Party 3',
        date: '2026-10-01',
        start: '20:00',
        campaignId: 3,
        status: 'Played',
        members: ['3', '2', '15']
    }
];
