import { Form, Head, Link, router, useForm, usePage, usePoll } from '@inertiajs/react';
import {
    AlertTriangle,
    ArrowLeft,
    Check,
    Copy,
    Download,
    Edit,
    ExternalLink,
    Lock,
    MoreHorizontal,
    RefreshCw,
    Sparkles,
    Trash2,
    Unlock,
    Users,
    X,
} from 'lucide-react';
import { useEffect, useState } from 'react';
import DocumentController from '@/actions/App/Http/Controllers/DocumentController';
import DocumentShareController from '@/actions/App/Http/Controllers/DocumentShareController';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Skeleton } from '@/components/ui/skeleton';
import AppLayout from '@/layouts/app-layout';
import { index, show, edit, download } from '@/routes/documents';
import type { BreadcrumbItem, Document, SharedData } from '@/types';

type SharedUser = {
    id: number;
    name: string;
    email: string;
    pivot: {
        can_download: boolean;
    };
};

type Props = {
    document: Document & { user?: { id: number; name: string } };
    qrCode: string | null;
    publicUrl: string | null;
    isOwner: boolean;
    sharedWith: SharedUser[];
    canDownload: boolean;
};

function formatDate(dateString: string) {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

function SensitivityBadge({ sensitivity }: { sensitivity: Document['sensitivity'] }) {
    if (!sensitivity) return null;

    const variants: Record<
        NonNullable<Document['sensitivity']>,
        { variant: 'default' | 'secondary' | 'destructive' | 'outline'; label: string }
    > = {
        safe: { variant: 'secondary', label: 'Safe' },
        maybe_sensitive: { variant: 'outline', label: 'May be sensitive' },
        sensitive: { variant: 'destructive', label: 'Sensitive' },
    };

    const config = variants[sensitivity];

    return (
        <Badge
            variant={config.variant}
            className={
                sensitivity === 'maybe_sensitive'
                    ? 'border-yellow-500 text-yellow-600'
                    : ''
            }
        >
            {config.label}
        </Badge>
    );
}

export default function DocumentShow({ document, qrCode, publicUrl, isOwner, sharedWith, canDownload }: Props) {
    const { flash } = usePage<SharedData & { flash?: { success?: string; error?: string } }>().props;
    const [copied, setCopied] = useState(false);
    const [deleteOpen, setDeleteOpen] = useState(false);
    const [qrModalOpen, setQrModalOpen] = useState(false);
    const [shareOpen, setShareOpen] = useState(false);

    const shareForm = useForm({
        email: '',
        can_download: true,
    });

    // Poll for updates while AI analysis is pending
    const { start, stop } = usePoll(1000, {}, { autoStart: false });

    // Start/stop polling based on ai_analyzed status
    useEffect(() => {
        if (!document.ai_analyzed) {
            start();
        } else {
            stop();
        }
    }, [document.ai_analyzed, start, stop]);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Documents', href: index().url },
        { title: document.title || document.original_name, href: show(document.id).url },
    ];

    const copyToClipboard = async () => {
        if (publicUrl) {
            await navigator.clipboard.writeText(publicUrl);
            setCopied(true);
            setTimeout(() => setCopied(false), 2000);
        }
    };

    const downloadQrCode = () => {
        if (!qrCode) return;

        const link = window.document.createElement('a');
        link.href = qrCode;
        link.download = `qr-${document.title || document.original_name}.svg`;
        window.document.body.appendChild(link);
        link.click();
        window.document.body.removeChild(link);
    };

    const handleDelete = () => {
        router.delete(DocumentController.destroy(document.id).url, {
            preserveScroll: true,
        });
    };

    const handleShare = (e: React.FormEvent) => {
        e.preventDefault();
        shareForm.post(DocumentShareController.store(document.id).url, {
            preserveScroll: true,
            onSuccess: () => {
                shareForm.reset();
                setShareOpen(false);
            },
        });
    };

    const handleRemoveShare = (userId: number) => {
        router.delete(DocumentShareController.destroy({ document: document.id, user: userId }).url, {
            preserveScroll: true,
        });
    };

    const handleToggleDownload = (userId: number, canDownloadCurrent: boolean) => {
        router.patch(DocumentShareController.update({ document: document.id, user: userId }).url, {
            can_download: !canDownloadCurrent,
        }, {
            preserveScroll: true,
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={document.title || document.original_name} />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div className="flex items-center gap-4">
                        <Button variant="ghost" size="icon" asChild>
                            <Link href={index()}>
                                <ArrowLeft className="size-4" />
                            </Link>
                        </Button>
                        <div>
                            <Heading
                                variant="small"
                                title={document.title || document.original_name}
                                description={document.title ? document.original_name : undefined}
                            />
                            {!isOwner && document.user && (
                                <p className="text-sm text-muted-foreground">
                                    Shared by {document.user.name}
                                </p>
                            )}
                        </div>
                    </div>
                    <DropdownMenu>
                        <DropdownMenuTrigger asChild>
                            <Button variant="outline" size="icon">
                                <MoreHorizontal className="size-4" />
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end">
                            {canDownload && (
                                <DropdownMenuItem asChild>
                                    <a href={download(document.id).url}>
                                        <Download className="mr-2 size-4" />
                                        Download
                                    </a>
                                </DropdownMenuItem>
                            )}
                            {isOwner && (
                                <>
                                    <DropdownMenuItem asChild>
                                        <Link href={edit(document.id)}>
                                            <Edit className="mr-2 size-4" />
                                            Edit
                                        </Link>
                                    </DropdownMenuItem>
                                    <DropdownMenuItem
                                        onClick={() => {
                                            router.post(DocumentController.reanalyze(document.id).url);
                                        }}
                                    >
                                        <RefreshCw className="mr-2 size-4" />
                                        Re-analyze
                                    </DropdownMenuItem>
                                    <DropdownMenuSeparator />
                                    <DropdownMenuItem
                                        className="text-destructive focus:text-destructive"
                                        onClick={() => setDeleteOpen(true)}
                                    >
                                        <Trash2 className="mr-2 size-4" />
                                        Delete
                                    </DropdownMenuItem>
                                </>
                            )}
                        </DropdownMenuContent>
                    </DropdownMenu>

                    <Dialog open={deleteOpen} onOpenChange={setDeleteOpen}>
                        <DialogContent>
                            <DialogHeader>
                                <DialogTitle>Delete Document</DialogTitle>
                                <DialogDescription>
                                    Are you sure you want to delete this document? This action
                                    cannot be undone.
                                </DialogDescription>
                            </DialogHeader>
                            <DialogFooter>
                                <Button
                                    variant="outline"
                                    onClick={() => setDeleteOpen(false)}
                                >
                                    Cancel
                                </Button>
                                <Button variant="destructive" onClick={handleDelete}>
                                    Delete
                                </Button>
                            </DialogFooter>
                        </DialogContent>
                    </Dialog>
                </div>

                {flash?.success && (
                    <div className="rounded-lg border border-green-200 bg-green-50 p-4 text-green-800 dark:border-green-800 dark:bg-green-900/20 dark:text-green-200">
                        {flash.success}
                    </div>
                )}

                {flash?.error && (
                    <div className="rounded-lg border border-red-200 bg-red-50 p-4 text-red-800 dark:border-red-800 dark:bg-red-900/20 dark:text-red-200">
                        {flash.error}
                    </div>
                )}

                {document.sensitivity === 'sensitive' && (
                    <div className="flex items-center gap-3 rounded-lg border border-red-200 bg-red-50 p-4 text-red-800 dark:border-red-800 dark:bg-red-900/20 dark:text-red-200">
                        <AlertTriangle className="size-5 shrink-0" />
                        <div>
                            <p className="font-medium">Sensitive Document</p>
                            <p className="text-sm">
                                AI analysis detected sensitive content. This document cannot be
                                made public.
                            </p>
                        </div>
                    </div>
                )}

                <div className="grid gap-6 lg:grid-cols-3">
                    <div className="space-y-6 lg:col-span-2">
                        <Card>
                            <CardHeader>
                                <CardTitle>Document Details</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="grid gap-4 sm:grid-cols-2">
                                    <div>
                                        <p className="text-sm font-medium text-muted-foreground">
                                            File Type
                                        </p>
                                        <p>{document.mime_type}</p>
                                    </div>
                                    <div>
                                        <p className="text-sm font-medium text-muted-foreground">
                                            Size
                                        </p>
                                        <p>{document.formatted_size}</p>
                                    </div>
                                    <div>
                                        <p className="text-sm font-medium text-muted-foreground">
                                            Uploaded
                                        </p>
                                        <p>{formatDate(document.created_at)}</p>
                                    </div>
                                    <div>
                                        <p className="text-sm font-medium text-muted-foreground">
                                            Visibility
                                        </p>
                                        <div className="flex items-center gap-2">
                                            {document.visibility === 'public' ? (
                                                <Badge className="gap-1">
                                                    <Unlock className="size-3" />
                                                    Public
                                                </Badge>
                                            ) : (
                                                <Badge variant="secondary" className="gap-1">
                                                    <Lock className="size-3" />
                                                    Private
                                                </Badge>
                                            )}
                                            <SensitivityBadge sensitivity={document.sensitivity} />
                                        </div>
                                    </div>
                                </div>

                                {document.description && (
                                    <div>
                                        <p className="text-sm font-medium text-muted-foreground">
                                            Description
                                        </p>
                                        <p className="mt-1">{document.description}</p>
                                    </div>
                                )}

                                {document.tags && document.tags.length > 0 && (
                                    <div>
                                        <p className="mb-2 text-sm font-medium text-muted-foreground">
                                            Tags
                                        </p>
                                        <div className="flex flex-wrap gap-2">
                                            {document.tags.map((tag) => (
                                                <Badge key={tag} variant="outline">
                                                    {tag}
                                                </Badge>
                                            ))}
                                        </div>
                                    </div>
                                )}
                            </CardContent>
                        </Card>

                        {document.ai_analyzed && document.ai_summary && (
                            <Card>
                                <CardHeader>
                                    <CardTitle className="flex items-center gap-2">
                                        <Sparkles className="size-5 text-primary" />
                                        AI Summary
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <p className="whitespace-pre-wrap text-muted-foreground">
                                        {document.ai_summary}
                                    </p>
                                </CardContent>
                            </Card>
                        )}

                        {!document.ai_analyzed && (
                            <Card>
                                <CardHeader>
                                    <CardTitle className="flex items-center gap-2">
                                        <Sparkles className="size-5 text-primary" />
                                        AI Analysis
                                    </CardTitle>
                                    <CardDescription>
                                        AI analysis is processing...
                                    </CardDescription>
                                </CardHeader>
                                <CardContent className="space-y-3">
                                    <Skeleton className="h-4 w-full" />
                                    <Skeleton className="h-4 w-3/4" />
                                    <Skeleton className="h-4 w-1/2" />
                                </CardContent>
                            </Card>
                        )}
                    </div>

                    <div className="space-y-6">
                        {isOwner && (
                            <>
                                <Card>
                                    <CardHeader>
                                        <CardTitle className="flex items-center gap-2">
                                            <Users className="size-5" />
                                            Share with Users
                                        </CardTitle>
                                        <CardDescription>
                                            Share this document with other users
                                        </CardDescription>
                                    </CardHeader>
                                    <CardContent className="space-y-4">
                                        <Dialog open={shareOpen} onOpenChange={setShareOpen}>
                                            <DialogTrigger asChild>
                                                <Button className="w-full">
                                                    <Users className="mr-2 size-4" />
                                                    Share with User
                                                </Button>
                                            </DialogTrigger>
                                            <DialogContent>
                                                <form onSubmit={handleShare}>
                                                    <DialogHeader>
                                                        <DialogTitle>Share Document</DialogTitle>
                                                        <DialogDescription>
                                                            Enter the email of the user you want to share this document with.
                                                        </DialogDescription>
                                                    </DialogHeader>
                                                    <div className="space-y-4 py-4">
                                                        <div className="space-y-2">
                                                            <Label htmlFor="email">User Email</Label>
                                                            <Input
                                                                id="email"
                                                                type="email"
                                                                placeholder="user@example.com"
                                                                value={shareForm.data.email}
                                                                onChange={(e) => shareForm.setData('email', e.target.value)}
                                                            />
                                                            {shareForm.errors.email && (
                                                                <p className="text-sm text-destructive">{shareForm.errors.email}</p>
                                                            )}
                                                        </div>
                                                        <div className="flex items-center space-x-2">
                                                            <Checkbox
                                                                id="can_download"
                                                                checked={shareForm.data.can_download}
                                                                onCheckedChange={(checked) =>
                                                                    shareForm.setData('can_download', checked === true)
                                                                }
                                                            />
                                                            <Label htmlFor="can_download">Allow download</Label>
                                                        </div>
                                                    </div>
                                                    <DialogFooter>
                                                        <Button
                                                            type="button"
                                                            variant="outline"
                                                            onClick={() => setShareOpen(false)}
                                                        >
                                                            Cancel
                                                        </Button>
                                                        <Button type="submit" disabled={shareForm.processing}>
                                                            Share
                                                        </Button>
                                                    </DialogFooter>
                                                </form>
                                            </DialogContent>
                                        </Dialog>

                                        {sharedWith.length > 0 && (
                                            <div className="space-y-2">
                                                <p className="text-sm font-medium">Shared with:</p>
                                                <div className="space-y-2">
                                                    {sharedWith.map((user) => (
                                                        <div
                                                            key={user.id}
                                                            className="flex items-center justify-between rounded-lg border p-2"
                                                        >
                                                            <div className="min-w-0 flex-1">
                                                                <p className="truncate text-sm font-medium">
                                                                    {user.name}
                                                                </p>
                                                                <p className="truncate text-xs text-muted-foreground">
                                                                    {user.email}
                                                                </p>
                                                            </div>
                                                            <div className="flex items-center gap-1">
                                                                <Button
                                                                    variant="ghost"
                                                                    size="sm"
                                                                    onClick={() => handleToggleDownload(user.id, user.pivot.can_download)}
                                                                    title={user.pivot.can_download ? 'Revoke download' : 'Allow download'}
                                                                >
                                                                    <Download
                                                                        className={`size-4 ${
                                                                            user.pivot.can_download
                                                                                ? 'text-green-500'
                                                                                : 'text-muted-foreground'
                                                                        }`}
                                                                    />
                                                                </Button>
                                                                <Button
                                                                    variant="ghost"
                                                                    size="sm"
                                                                    onClick={() => handleRemoveShare(user.id)}
                                                                >
                                                                    <X className="size-4 text-destructive" />
                                                                </Button>
                                                            </div>
                                                        </div>
                                                    ))}
                                                </div>
                                            </div>
                                        )}
                                    </CardContent>
                                </Card>

                                <Card>
                                    <CardHeader>
                                        <CardTitle>Public Sharing</CardTitle>
                                        <CardDescription>
                                            {document.visibility === 'public'
                                                ? 'This document is publicly accessible'
                                                : 'Make this document public to share it'}
                                        </CardDescription>
                                    </CardHeader>
                                    <CardContent className="space-y-4">
                                        {document.visibility === 'public' ? (
                                            <>
                                                <div className="space-y-2">
                                                    <p className="text-sm font-medium">Public Link</p>
                                                    <div className="flex gap-2">
                                                        <code className="flex flex-1 items-center truncate rounded bg-muted px-2 text-sm">
                                                            {publicUrl}
                                                        </code>
                                                        <Button
                                                            variant="outline"
                                                            size="icon"
                                                            onClick={copyToClipboard}
                                                        >
                                                            {copied ? (
                                                                <Check className="size-4 text-green-500" />
                                                            ) : (
                                                                <Copy className="size-4" />
                                                            )}
                                                        </Button>
                                                        <Button
                                                            variant="outline"
                                                            size="icon"
                                                            asChild
                                                        >
                                                            <a
                                                                href={publicUrl || '#'}
                                                                target="_blank"
                                                                rel="noopener noreferrer"
                                                            >
                                                                <ExternalLink className="size-4" />
                                                            </a>
                                                        </Button>
                                                    </div>
                                                </div>

                                                {qrCode && (
                                                    <div className="space-y-2">
                                                        <p className="text-sm font-medium">QR Code</p>
                                                        <button
                                                            type="button"
                                                            onClick={() => setQrModalOpen(true)}
                                                            className="flex w-full cursor-pointer justify-center rounded-lg border bg-white p-4 transition-shadow hover:shadow-md"
                                                        >
                                                            <img
                                                                src={qrCode}
                                                                alt="QR Code"
                                                                className="size-32"
                                                            />
                                                        </button>
                                                        <p className="text-center text-xs text-muted-foreground">
                                                            Click to enlarge
                                                        </p>
                                                    </div>
                                                )}

                                                <Dialog open={qrModalOpen} onOpenChange={setQrModalOpen}>
                                                    <DialogContent className="w-full max-w-lg">
                                                        <DialogHeader>
                                                            <DialogTitle>QR Code</DialogTitle>
                                                            <DialogDescription>
                                                                Scan this code to access the document
                                                            </DialogDescription>
                                                        </DialogHeader>
                                                        <div className="flex flex-col items-center gap-4 py-4">
                                                            <div className="rounded-lg border bg-white p-6">
                                                                <img
                                                                    src={qrCode || ''}
                                                                    alt="QR Code"
                                                                    className="size-64"
                                                                />
                                                            </div>
                                                            <div className="w-full space-y-2 text-center">
                                                                <p className="font-medium">
                                                                    {document.title || document.original_name}
                                                                </p>
                                                                <code className="block rounded bg-muted px-2 py-1 text-xs">
                                                                    {publicUrl && publicUrl.length > 40
                                                                        ? `${publicUrl.slice(0, 40)}...`
                                                                        : publicUrl}
                                                                </code>
                                                            </div>
                                                        </div>
                                                        <DialogFooter className="flex-col gap-2 sm:flex-row">
                                                            <Button
                                                                variant="outline"
                                                                className="w-full sm:w-auto"
                                                                onClick={downloadQrCode}
                                                            >
                                                                <Download className="mr-2 size-4" />
                                                                Download QR
                                                            </Button>
                                                            <Button
                                                                variant="outline"
                                                                className="w-full sm:w-auto"
                                                                onClick={copyToClipboard}
                                                            >
                                                                {copied ? (
                                                                    <Check className="mr-2 size-4 text-green-500" />
                                                                ) : (
                                                                    <Copy className="mr-2 size-4" />
                                                                )}
                                                                Copy Link
                                                            </Button>
                                                            <Button
                                                                variant="outline"
                                                                className="w-full sm:w-auto"
                                                                asChild
                                                            >
                                                                <a
                                                                    href={publicUrl || '#'}
                                                                    target="_blank"
                                                                    rel="noopener noreferrer"
                                                                >
                                                                    <ExternalLink className="mr-2 size-4" />
                                                                    Open Link
                                                                </a>
                                                            </Button>
                                                        </DialogFooter>
                                                    </DialogContent>
                                                </Dialog>

                                                <Form
                                                    {...DocumentController.makePrivate.form(
                                                        document.id
                                                    )}
                                                >
                                                    {({ processing }) => (
                                                        <Button
                                                            type="submit"
                                                            variant="outline"
                                                            className="w-full"
                                                            disabled={processing}
                                                        >
                                                            <Lock className="mr-2 size-4" />
                                                            Make Private
                                                        </Button>
                                                    )}
                                                </Form>
                                            </>
                                        ) : (
                                            <Form
                                                {...DocumentController.makePublic.form(document.id)}
                                            >
                                                {({ processing }) => (
                                                    <Button
                                                        type="submit"
                                                        className="w-full"
                                                        disabled={
                                                            processing ||
                                                            document.sensitivity === 'sensitive'
                                                        }
                                                    >
                                                        <Unlock className="mr-2 size-4" />
                                                        Make Public
                                                    </Button>
                                                )}
                                            </Form>
                                        )}
                                    </CardContent>
                                </Card>
                            </>
                        )}
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
