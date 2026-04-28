export interface Project {
    id: string;
    name: string;
    description: string | null;
    source_lang: string;
    target_lang: string;
    status: 'active' | 'completed' | 'archived';
    use_global_tm: boolean;
    mt_provider: string | null;
    char_limit_per_segment: number | null;
    char_limit_warning_pct: number;
    tm_min_match_pct: number;
    last_activity_at: string | null;
    created_at: string;
    updated_at: string;
}

export interface ProjectFile {
    id: string;
    project_id: string;
    original_name: string;
    file_format: string;
    word_count: number;
    segment_count: number;
    translated_count: number;
    status: 'pending' | 'processing' | 'ready' | 'exporting' | 'exported' | 'error';
    error_message: string | null;
    processed_at: string | null;
    export_path: string | null;
}

export interface ProjectSummary {
    id: string;
    name: string;
    source_lang: string;
    target_lang: string;
    status: 'active' | 'completed' | 'archived';
    last_activity_at: string | null;
    created_at: string;
    files_count: number;
    total_segments: number;
    translated_count: number;
    progress_pct: number;
    has_processing_files: boolean;
}
