// Table of polls.
export type Poll = {
    id: number;
    title: string;
    options: string[];
    status: 'Open' | 'Closed';
};

export const polls: Poll[] = [
    {
        id: 1,
        title: 'Welke datum kan je?',
        options: ['16 september 2026', '22 september 2026', '30 september 2026', '7 oktober 2026', '14 oktober 2026'],
        status: 'Open'
    }
];
