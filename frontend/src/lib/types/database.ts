import type {
	Permissions as PermissionsType,
	Permission as PermissionType
} from './permissions';

import type { User as UserType } from './user';
import type { Poll as PollType } from './poll';

export namespace Database {
	export type Permissions	= PermissionsType;
	export type Permission	= PermissionType;
	export type User		= UserType;
	export type Poll		= PollType;
}