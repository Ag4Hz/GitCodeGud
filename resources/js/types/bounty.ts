
export enum BountyStatus {
    OPEN = 'open',
    CLOSED = 'closed'
}

export interface Bounty {
    id: number;
    title: string;
    description: string;
    reward_xp: number;
    status: BountyStatus;
    created_at: string;
    updated_at: string;
    deleted_at: string | null;
    languages?: string[];
    issue: {
        url: string;
        repo: {
            url: string;
        };
    };
    submissions_count?: number;
}

export interface BountyPagination {
    data: Bounty[];
    total: number;
    current_page: number;
    last_page: number;
}
