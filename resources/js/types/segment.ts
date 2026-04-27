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
    displayText: string;
}

export interface Segment {
    id: string;
    segmentNumber: number;
    sourceText: string;
    targetText: string | null;
    sourceTags: TagMap[];
    targetTags: TagMap[];
    status: SegmentStatus;
    wordCount: number;
    charCount: number;
    tmMatchPercent: number | null;
    tmMatchOrigin: 'tm' | 'mt' | 'human' | 'exact' | null;
    note: string | null;
    locked: boolean;
    bookmarked: boolean;
}

export interface TmMatch {
    sourceText: string;
    targetText: string;
    percent: number;
    origin: 'tm' | 'mt' | 'exact';
    diffTokens: DiffToken[];
}

export interface DiffToken {
    text: string;
    type: 'match' | 'insert' | 'delete';
}

export interface QaIssue {
    segmentId: string;
    segmentNumber: number;
    severity: 'error' | 'warning' | 'info';
    checkName: string;
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
