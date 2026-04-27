export interface Project {
    id: string;
    name: string;
    description: string | null;
    sourceLang: string;
    targetLang: string;
    status: 'active' | 'completed' | 'archived';
    useGlobalTm: boolean;
    mtProvider: string | null;
    charLimitPerSegment: number | null;
    charLimitWarningPct: number;
    tmMinMatchPct: number;
    lastActivityAt: string | null;
    createdAt: string;
    updatedAt: string;
}

export interface ProjectFile {
    id: string;
    projectId: string;
    originalName: string;
    fileFormat: string;
    wordCount: number;
    segmentCount: number;
    translatedCount: number;
    status: 'pending' | 'processing' | 'ready' | 'exporting' | 'exported' | 'error';
    errorMessage: string | null;
    processedAt: string | null;
    exportPath: string | null;
}

export type NextStepKind = 'translate' | 'processing' | 'export' | 'review' | 'qa';

export interface ProjectWithStats extends Project {
    files: ProjectFile[];
    filesCount: number;
    nextStep: NextStepKind;
}
