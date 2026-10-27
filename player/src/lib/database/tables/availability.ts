// Mock availability responses used by the monthly attendance calendar.
export type Availability = {
    date: string;
    unavailableUserIds: number[];
};

export const availability: Availability[] = [
    { date: '2026-10-01', unavailableUserIds: [1, 4, 5, 6, 7, 11, 14, 15] },
    { date: '2026-10-05', unavailableUserIds: [1, 3, 5, 6, 8, 9, 10, 12, 14] },
    { date: '2026-10-07', unavailableUserIds: [6, 8, 9, 10, 12] },
    { date: '2026-10-14', unavailableUserIds: [1, 5, 6, 8, 9, 10, 12, 14, 16] },
    { date: '2026-10-15', unavailableUserIds: [3, 5, 8, 9, 10, 12, 14] },
    { date: '2026-10-19', unavailableUserIds: [2, 3, 4, 5, 6, 8, 9, 10, 12, 14, 15] },
    { date: '2026-10-20', unavailableUserIds: [1, 3, 5, 8, 9, 10, 12, 14] }
];
