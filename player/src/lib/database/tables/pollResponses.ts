// Join table between polls, options and users.
export type PollResponse = {
    id: number;
    pollId: number;
    userId: number;
    option: string;
};

export const pollResponses: PollResponse[] = [
    { id: 1, pollId: 1, userId: 1, option: '16 september 2026' },
    { id: 2, pollId: 1, userId: 2, option: '22 september 2026' },
    { id: 3, pollId: 1, userId: 3, option: '16 september 2026' },
    { id: 4, pollId: 1, userId: 4, option: '7 oktober 2026' }
];
