import type { Permissions } from './permissions';

export type User = {
    id          :number;
    username    :string;
    email       :string;
    name        :string;
    permissions :Permissions;
    status      :boolean;
    created_at  :string;
    updated_at  :string;
};