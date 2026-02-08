import { Head, Link, router } from '@inertiajs/react';
import {
    ChevronRight,
    FileText,
    Loader2,
    Lock,
    Plus,
    Search,
    Share2,
    Unlock,
    Users,
    X,
} from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs';
import AppLayout from '@/layouts/app-layout';
import { index, create, show } from '@/routes/documents';
import type { BreadcrumbItem, Document, DocumentFilters, PaginatedDocuments } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Documents',
        href: index().url,
    },
];

type ExtendedDocument = Document & {
    user?: { id: number; name: string };
};

type Props = {
    documents: PaginatedDocuments & { data: ExtendedDocument[] };
    sharedCount: number;
    filters: DocumentFilters & { filter?: string };
};

function formatDate(dateString: string) {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
}

export default function DocumentsIndex({ documents, sharedCount, filters }: Props) {
    const [search, setSearch] = useState(filters.search);
    const [visibility, setVisibility] = useState<string>(filters.visibility || 'all');
    const [filter, setFilter] = useState<string>(filters.filter || 'owned');
    const [isSearching, setIsSearching] = useState(false);
    const debounceRef = useRef<NodeJS.Timeout | null>(null);

    const isSharedView = filter === 'shared';

    // Debounced search effect
    useEffect(() => {
        // Clear previous timeout
        if (debounceRef.current) {
            clearTimeout(debounceRef.current);
        }

        // Skip if search matches current filter (initial load)
        if (search === filters.search && visibility === filters.visibility) {
            return;
        }

        // Set new timeout for debounced search
        debounceRef.current = setTimeout(() => {
            setIsSearching(true);
            const visibilityParam = visibility === 'all' ? '' : visibility;
            router.get(
                index().url,
                { search, visibility: visibilityParam, filter },
                {
                    preserveState: true,
                    preserveScroll: true,
                    onFinish: () => setIsSearching(false),
                }
            );
        }, 300);

        return () => {
            if (debounceRef.current) {
                clearTimeout(debounceRef.current);
            }
        };
    }, [search, filters.search, filters.visibility, visibility, filter]);

    const handleFilterChange = (value: string) => {
        setFilter(value);
        setSearch('');
        setVisibility('all');
        router.get(index().url, { filter: value }, { preserveState: true });
    };

    const handleVisibilityChange = (value: string) => {
        setVisibility(value);
        const visibilityParam = value === 'all' ? '' : value;
        router.get(index().url, { search, visibility: visibilityParam, filter }, { preserveState: true });
    };

    const clearSearch = () => {
        setSearch('');
        const visibilityParam = visibility === 'all' ? '' : visibility;
        router.get(index().url, { search: '', visibility: visibilityParam, filter }, { preserveState: true });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Documents" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <Heading
                        variant="small"
                        title="Documents"
                        description={isSharedView ? 'Documents shared with you' : 'Manage your uploaded documents'}
                    />
                    {!isSharedView && (
                        <Button asChild>
                            <Link href={create()}>
                                <Plus className="mr-2 size-4" />
                                Upload Document
                            </Link>
                        </Button>
                    )}
                </div>

                <Tabs value={filter} onValueChange={handleFilterChange}>
                    <TabsList>
                        <TabsTrigger value="owned" className="gap-2">
                            <FileText className="size-4" />
                            My Documents
                        </TabsTrigger>
                        <TabsTrigger value="shared" className="gap-2">
                            <Users className="size-4" />
                            Shared with me
                            {sharedCount > 0 && (
                                <Badge variant="secondary" className="ml-1 h-5 min-w-5 px-1.5">
                                    {sharedCount}
                                </Badge>
                            )}
                        </TabsTrigger>
                    </TabsList>
                </Tabs>

                {!isSharedView && (
                    <div className="flex flex-col gap-3 sm:flex-row">
                        <div className="relative flex-1">
                            <Search className="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
                            <Input
                                type="text"
                                placeholder="Search documents..."
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                className="pl-9 pr-9"
                            />
                            {isSearching ? (
                                <Loader2 className="absolute right-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground animate-spin" />
                            ) : search ? (
                                <button
                                    type="button"
                                    onClick={clearSearch}
                                    className="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground"
                                >
                                    <X className="size-4" />
                                </button>
                            ) : null}
                        </div>
                        <Select value={visibility} onValueChange={handleVisibilityChange}>
                            <SelectTrigger className="w-full sm:w-40">
                                <SelectValue placeholder="All visibility" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">All</SelectItem>
                                <SelectItem value="public">Public</SelectItem>
                                <SelectItem value="private">Private</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                )}

                {documents.data.length === 0 ? (
                    <Card>
                        <CardContent className="flex flex-col items-center justify-center py-12">
                            {isSharedView ? (
                                <>
                                    <Share2 className="size-12 text-muted-foreground" />
                                    <h3 className="mt-4 text-lg font-semibold">No shared documents</h3>
                                    <p className="mt-2 text-sm text-muted-foreground">
                                        Documents shared with you will appear here
                                    </p>
                                </>
                            ) : (
                                <>
                                    <FileText className="size-12 text-muted-foreground" />
                                    <h3 className="mt-4 text-lg font-semibold">No documents found</h3>
                                    <p className="mt-2 text-sm text-muted-foreground">
                                        {filters.search || filters.visibility
                                            ? 'Try adjusting your search or filters'
                                            : 'Get started by uploading your first document'}
                                    </p>
                                    {!filters.search && !filters.visibility && (
                                        <Button asChild className="mt-4">
                                            <Link href={create()}>
                                                <Plus className="mr-2 size-4" />
                                                Upload Document
                                            </Link>
                                        </Button>
                                    )}
                                </>
                            )}
                        </CardContent>
                    </Card>
                ) : (
                    <>
                        <div className="divide-y divide-border rounded-lg border bg-card">
                            {documents.data.map((document) => (
                                <Link
                                    key={document.id}
                                    href={show(document.id)}
                                    className="group flex items-center gap-4 p-4 transition-colors hover:bg-muted/50"
                                >
                                    <div className="flex-1 min-w-0">
                                        <div className="flex items-center gap-2 mb-1">
                                            <h3 className="font-medium truncate group-hover:text-primary">
                                                {document.title || document.original_name}
                                            </h3>
                                            <div className="flex items-center gap-1.5 shrink-0">
                                                {document.visibility === 'public' ? (
                                                    <Badge variant="default" className="gap-1 text-xs">
                                                        <Unlock className="size-3" />
                                                        Public
                                                    </Badge>
                                                ) : (
                                                    <Badge variant="secondary" className="gap-1 text-xs">
                                                        <Lock className="size-3" />
                                                        Private
                                                    </Badge>
                                                )}
                                                {document.sensitivity === 'sensitive' && (
                                                    <Badge variant="destructive" className="text-xs">Sensitive</Badge>
                                                )}
                                                {document.sensitivity === 'maybe_sensitive' && (
                                                    <Badge variant="outline" className="border-yellow-500 text-yellow-600 text-xs">
                                                        Caution
                                                    </Badge>
                                                )}
                                            </div>
                                        </div>
                                        {document.description ? (
                                            <p className="text-sm text-muted-foreground line-clamp-2">
                                                {document.description}
                                            </p>
                                        ) : (
                                            <p className="text-sm text-muted-foreground italic">
                                                {document.title ? document.original_name : 'No description available'}
                                            </p>
                                        )}
                                        <div className="flex items-center gap-3 mt-2 text-xs text-muted-foreground">
                                            {isSharedView && document.user && (
                                                <>
                                                    <span className="flex items-center gap-1">
                                                        <Users className="size-3" />
                                                        {document.user.name}
                                                    </span>
                                                    <span>•</span>
                                                </>
                                            )}
                                            <span>{document.formatted_size}</span>
                                            <span>•</span>
                                            <span>{formatDate(document.created_at)}</span>
                                            {document.tags && document.tags.length > 0 && (
                                                <>
                                                    <span>•</span>
                                                    <span>{document.tags.slice(0, 2).join(', ')}{document.tags.length > 2 ? ` +${document.tags.length - 2}` : ''}</span>
                                                </>
                                            )}
                                        </div>
                                    </div>
                                    <ChevronRight className="size-5 text-muted-foreground shrink-0 group-hover:text-primary" />
                                </Link>
                            ))}
                        </div>

                        {documents.last_page > 1 && (
                            <div className="flex justify-center gap-2">
                                {documents.links.map((link, i) => (
                                    <Button
                                        key={i}
                                        variant={link.active ? 'default' : 'outline'}
                                        size="sm"
                                        disabled={!link.url}
                                        asChild={!!link.url}
                                    >
                                        {link.url ? (
                                            <Link
                                                href={link.url}
                                                preserveScroll
                                                dangerouslySetInnerHTML={{
                                                    __html: link.label,
                                                }}
                                            />
                                        ) : (
                                            <span
                                                dangerouslySetInnerHTML={{
                                                    __html: link.label,
                                                }}
                                            />
                                        )}
                                    </Button>
                                ))}
                            </div>
                        )}
                    </>
                )}
            </div>
        </AppLayout>
    );
}
