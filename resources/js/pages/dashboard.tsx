import { Head, Link } from '@inertiajs/react';
import {
    ChevronRight,
    FileText,
    FolderOpen,
    HardDrive,
    Lock,
    Plus,
    RefreshCw,
    Shield,
    Unlock,
} from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import { create, index, show } from '@/routes/documents';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
];

type Stats = {
    total_documents: number;
    public_documents: number;
    private_documents: number;
    total_storage: string;
    pending_analysis: number;
    sensitive_documents: number;
    maybe_sensitive_documents: number;
};

type RecentDocument = {
    id: number;
    title: string;
    visibility: 'public' | 'private';
    sensitivity: string | null;
    formatted_size: string;
    created_at: string;
    ai_analyzed: boolean;
};

type Props = {
    stats: Stats;
    recentDocuments: RecentDocument[];
    documentsByType: Record<string, number>;
};

export default function Dashboard({ stats, recentDocuments, documentsByType }: Props) {
    const typeColors: Record<string, string> = {
        PDFs: 'bg-red-500',
        Images: 'bg-blue-500',
        Documents: 'bg-indigo-500',
        Spreadsheets: 'bg-green-500',
        'Text Files': 'bg-yellow-500',
        Videos: 'bg-purple-500',
        Audio: 'bg-pink-500',
        Other: 'bg-gray-500',
    };

    const totalByType = Object.values(documentsByType).reduce((a, b) => a + b, 0);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                {/* Header */}
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight">Dashboard</h1>
                        <p className="text-sm text-muted-foreground">
                            Overview of your document library
                        </p>
                    </div>
                    <Button asChild>
                        <Link href={create()}>
                            <Plus className="mr-2 size-4" />
                            Upload Document
                        </Link>
                    </Button>
                </div>

                {/* Stats Grid */}
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between pb-2">
                            <CardTitle className="text-sm font-medium">Total Documents</CardTitle>
                            <FolderOpen className="size-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{stats.total_documents}</div>
                            <p className="text-xs text-muted-foreground">
                                {stats.public_documents} public, {stats.private_documents} private
                            </p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between pb-2">
                            <CardTitle className="text-sm font-medium">Storage Used</CardTitle>
                            <HardDrive className="size-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{stats.total_storage}</div>
                            <p className="text-xs text-muted-foreground">
                                Across all documents
                            </p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between pb-2">
                            <CardTitle className="text-sm font-medium">Public Documents</CardTitle>
                            <Unlock className="size-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{stats.public_documents}</div>
                            <p className="text-xs text-muted-foreground">
                                Accessible via QR code
                            </p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between pb-2">
                            <CardTitle className="text-sm font-medium">Pending Analysis</CardTitle>
                            <RefreshCw className="size-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{stats.pending_analysis}</div>
                            <p className="text-xs text-muted-foreground">
                                Awaiting AI processing
                            </p>
                        </CardContent>
                    </Card>
                </div>

                {/* Alerts */}
                {stats.sensitive_documents > 0 && (
                    <Card className="border-red-200 bg-red-50 dark:border-red-900 dark:bg-red-950/50">
                        <CardHeader className="flex flex-row items-center gap-3 pb-2">
                            <Shield className="size-5 text-red-600 dark:text-red-400" />
                            <div>
                                <CardTitle className="text-sm font-medium text-red-900 dark:text-red-100">
                                    Sensitive Documents
                                </CardTitle>
                                <CardDescription className="text-red-700 dark:text-red-300">
                                    {stats.sensitive_documents} document{stats.sensitive_documents !== 1 ? 's' : ''} flagged as sensitive
                                </CardDescription>
                            </div>
                        </CardHeader>
                    </Card>
                )}

                <div className="grid gap-6 lg:grid-cols-3">
                    {/* Recent Documents */}
                    <Card className="lg:col-span-2">
                        <CardHeader className="flex flex-row items-center justify-between">
                            <div>
                                <CardTitle>Recent Documents</CardTitle>
                                <CardDescription>Your latest uploads</CardDescription>
                            </div>
                            <Button variant="ghost" size="sm" asChild>
                                <Link href={index()}>
                                    View all
                                    <ChevronRight className="ml-1 size-4" />
                                </Link>
                            </Button>
                        </CardHeader>
                        <CardContent>
                            {recentDocuments.length === 0 ? (
                                <div className="flex flex-col items-center justify-center py-8 text-center">
                                    <FileText className="size-12 text-muted-foreground" />
                                    <h3 className="mt-4 text-lg font-semibold">No documents yet</h3>
                                    <p className="mt-2 text-sm text-muted-foreground">
                                        Upload your first document to get started
                                    </p>
                                    <Button asChild className="mt-4">
                                        <Link href={create()}>
                                            <Plus className="mr-2 size-4" />
                                            Upload Document
                                        </Link>
                                    </Button>
                                </div>
                            ) : (
                                <div className="space-y-4">
                                    {recentDocuments.map((doc) => (
                                        <Link
                                            key={doc.id}
                                            href={show(doc.id)}
                                            className="flex items-center justify-between rounded-lg border p-3 transition-colors hover:bg-muted/50"
                                        >
                                            <div className="flex items-center gap-3">
                                                <div className="flex size-10 items-center justify-center rounded-lg bg-primary/10">
                                                    <FileText className="size-5 text-primary" />
                                                </div>
                                                <div>
                                                    <p className="font-medium leading-none">
                                                        {doc.title}
                                                    </p>
                                                    <p className="mt-1 text-xs text-muted-foreground">
                                                        {doc.formatted_size} · {doc.created_at}
                                                    </p>
                                                </div>
                                            </div>
                                            <div className="flex items-center gap-2">
                                                {doc.visibility === 'public' ? (
                                                    <Badge variant="default" className="gap-1">
                                                        <Unlock className="size-3" />
                                                        Public
                                                    </Badge>
                                                ) : (
                                                    <Badge variant="secondary" className="gap-1">
                                                        <Lock className="size-3" />
                                                        Private
                                                    </Badge>
                                                )}
                                                {doc.sensitivity === 'sensitive' && (
                                                    <Badge variant="destructive">Sensitive</Badge>
                                                )}
                                                {doc.sensitivity === 'maybe_sensitive' && (
                                                    <Badge variant="outline" className="border-yellow-500 text-yellow-600">
                                                        Caution
                                                    </Badge>
                                                )}
                                                {!doc.ai_analyzed && (
                                                    <RefreshCw className="size-4 animate-spin text-muted-foreground" />
                                                )}
                                                <ChevronRight className="size-4 text-muted-foreground" />
                                            </div>
                                        </Link>
                                    ))}
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    {/* Document Types */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Document Types</CardTitle>
                            <CardDescription>Distribution by file type</CardDescription>
                        </CardHeader>
                        <CardContent>
                            {totalByType === 0 ? (
                                <div className="flex flex-col items-center justify-center py-8 text-center">
                                    <FolderOpen className="size-12 text-muted-foreground" />
                                    <p className="mt-4 text-sm text-muted-foreground">
                                        No documents uploaded yet
                                    </p>
                                </div>
                            ) : (
                                <div className="space-y-4">
                                    {Object.entries(documentsByType)
                                        .sort(([, a], [, b]) => b - a)
                                        .map(([type, count]) => (
                                            <div key={type} className="space-y-2">
                                                <div className="flex items-center justify-between text-sm">
                                                    <span className="font-medium">{type}</span>
                                                    <span className="text-muted-foreground">
                                                        {count} ({Math.round((count / totalByType) * 100)}%)
                                                    </span>
                                                </div>
                                                <div className="h-2 overflow-hidden rounded-full bg-muted">
                                                    <div
                                                        className={`h-full rounded-full ${typeColors[type] || 'bg-gray-500'}`}
                                                        style={{ width: `${(count / totalByType) * 100}%` }}
                                                    />
                                                </div>
                                            </div>
                                        ))}
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
