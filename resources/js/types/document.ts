export type DocumentVisibility = 'private' | 'public';
export type DocumentSensitivity = 'safe' | 'maybe_sensitive' | 'sensitive' | null;

export type Document = {
    id: number;
    user_id: number;
    original_name: string;
    mime_type: string;
    size_bytes: number;
    storage_disk: string;
    storage_path: string;
    visibility: DocumentVisibility;
    public_token: string | null;
    public_enabled_at: string | null;
    public_disabled_at: string | null;
    title: string | null;
    description: string | null;
    tags: string[] | null;
    ai_summary: string | null;
    sensitivity: DocumentSensitivity;
    ai_analyzed: boolean;
    created_at: string;
    updated_at: string;
    formatted_size: string;
    extension: string;
};

export type PaginatedDocuments = {
    data: Document[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: {
        url: string | null;
        label: string;
        active: boolean;
    }[];
};

export type DocumentFilters = {
    search: string;
    visibility: DocumentVisibility | '';
};
