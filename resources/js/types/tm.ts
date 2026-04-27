export interface TranslationMemory {
    id: string;
    teamId: string;
    projectId: string | null;
    name: string;
    sourceLang: string;
    targetLang: string;
    entryCount: number;
    isGlobal: boolean;
    createdAt: string;
    updatedAt: string;
}

export interface TmEntry {
    id: number;
    tmId: string;
    sourceLang: string;
    targetLang: string;
    sourceText: string;
    targetText: string;
    createdAt: string;
    lastUsedAt: string | null;
    createdBy: string | null;
    metadata: Record<string, unknown>;
}

export interface Glossary {
    id: string;
    teamId: string;
    projectId: string | null;
    name: string;
    sourceLang: string;
    targetLang: string;
    termCount: number;
    isGlobal: boolean;
}

export interface GlossaryTerm {
    id: number;
    source: string;
    target: string;
    domain: string | null;
}
