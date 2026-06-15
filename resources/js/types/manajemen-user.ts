export type GrupAuth = {
    id: number;
    name: string;
    alias: string;
    keterangan: string;
    created?: string;
    permissions_count?: number;
    users_count?: number;
};

export type RuteAuth = {
    id: number;
    title: string;
    url: string;
    type: string;
};

export type RuteGrupAuth = {
    title: string;
    routes: RuteAuth[];
};

export type AksesUser = {
    id_user_decrypted: string;
    id_user: string;
    nama: string | null;
    status: string | null;
    alias_group: string | null;
    group_name: string | null;
};

export type Paginated<T> = {
    data: T[];
    links: {
        url: string | null;
        label: string;
        active: boolean;
    }[];
    from: number | null;
    to: number | null;
    total: number;
};
