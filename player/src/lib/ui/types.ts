export type Action = { label: string; href?: string; tone?: 'default' | 'danger'; onselect?: () => void };
export type Tone = 'neutral' | 'accent' | 'success' | 'warning';
