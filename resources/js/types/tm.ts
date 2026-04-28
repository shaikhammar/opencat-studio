export interface TranslationMemory {
    id: string;
    team_id: string;
    project_id: string | null;
    name: string;
    source_lang: string;
    target_lang: string;
    entry_count: number;
    is_global: boolean;
    created_at: string;
    updated_at: string;
}

export interface TmEntry {
    id: number;
    tm_id: string;
    source_lang: string;
    target_lang: string;
    source_text: string;
    target_text: string;
    created_at: string;
    last_used_at: string | null;
    created_by: string | null;
    metadata: Record<string, unknown>;
}

export interface Glossary {
    id: string;
    team_id: string;
    project_id: string | null;
    name: string;
    source_lang: string;
    target_lang: string;
    term_count: number;
    is_global: boolean;
}

export interface GlossaryTerm {
    id: number;
    source: string;
    target: string;
    domain: string | null;
}
