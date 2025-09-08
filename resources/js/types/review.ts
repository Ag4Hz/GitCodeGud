export type Reviewer = {
    id: number;
    name: string;
    nickname: string;
    avatar: string | null;
    oauth_provider: string | null;
    oauth_provider_id: string | null;
};

export type ReviewItem = {
    id: number;
    comment: string | null;
    created_at: string;
    reviewer: Reviewer;
};

export type ReviewsPayload = {
    data: ReviewItem[];
    current_page: number;
    last_page: number;
    next_page_url: string | null;
    prev_page_url: string | null;
};
