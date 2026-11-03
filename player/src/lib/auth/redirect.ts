type AppGoto = (href: string) => Promise<unknown>;

export async function redirectToLogin(goto: AppGoto, pathname: string, search: string): Promise<string | null> {
    const returnTo = `${pathname}${search}`;
    try {
        await goto(`/login?returnTo=${encodeURIComponent(returnTo)}`);
        return null;
    } catch {
        return 'Your session has expired. Please sign in again.';
    }
}
