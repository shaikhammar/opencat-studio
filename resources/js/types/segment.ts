export type SegmentStatus =
    | 'untranslated'
    | 'draft'
    | 'translated'
    | 'reviewed'
    | 'approved'
    | 'rejected';

export interface TagMap {
    id: number;
    type: 'open' | 'close' | 'self';
    data: string;
    display_text: string;
}

export interface Segment {
    id: string;
    segment_number: number;
    source_text: string;
    target_text: string | null;
    source_tags: TagMap[];
    target_tags: TagMap[];
    status: SegmentStatus;
    word_count: number;
    char_count: number;
    tm_match_percent: number | null;
    tm_match_origin: 'tm' | 'mt' | 'human' | 'exact' | null;
    note: string | null;
    locked: boolean;
    bookmarked: boolean;
}

export interface TmMatch {
    source_text: string;
    target_text: string;
    percent: number;
    origin: 'tm' | 'mt' | 'exact';
    diff_tokens: DiffToken[];
}

export interface DiffToken {
    text: string;
    type: 'match' | 'insert' | 'delete';
}

export interface QaIssue {
    segment_id: string;
    segment_number: number;
    severity: 'error' | 'warning' | 'info';
    check_name: string;
    message: string;
}

export interface SegmentPage {
    data: Segment[];
    meta: {
        page: number;
        limit: number;
        total: number;
        hasMore: boolean;
    };
}
