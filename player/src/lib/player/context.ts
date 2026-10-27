import { getContext, setContext } from 'svelte';

export type PlayerUser = { display_name: string; email?: string | null; username: string | null; roles?: string[] };

const playerUserKey = Symbol('player-user');

export type PlayerContext = { user: PlayerUser | null };

export function providePlayerUser(context: PlayerContext) {
    setContext(playerUserKey, context);
}

export function getPlayerUser(): PlayerContext {
    return getContext<PlayerContext>(playerUserKey);
}
