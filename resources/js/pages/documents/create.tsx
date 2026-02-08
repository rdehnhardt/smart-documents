import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, CloudUpload, File } from 'lucide-react';
import { useCallback, useState } from 'react';
import DocumentController from '@/actions/App/Http/Controllers/DocumentController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { index, create } from '@/routes/documents';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Documents',
        href: index().url,
    },
    {
        title: 'Upload',
        href: create().url,
    },
];

type Props = {
    maxFileSize: number;
};

function formatBytes(bytes: number): string {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

export default function DocumentCreate({ maxFileSize }: Props) {
    const [dragActive, setDragActive] = useState(false);

    const { data, setData, post, processing, errors } = useForm<{
        file: File | null;
        title: string;
        description: string;
    }>({
        file: null,
        title: '',
        description: '',
    });

    const handleDrag = useCallback((e: React.DragEvent) => {
        e.preventDefault();
        e.stopPropagation();
        if (e.type === 'dragenter' || e.type === 'dragover') {
            setDragActive(true);
        } else if (e.type === 'dragleave') {
            setDragActive(false);
        }
    }, []);

    const handleDrop = useCallback(
        (e: React.DragEvent) => {
            e.preventDefault();
            e.stopPropagation();
            setDragActive(false);

            if (e.dataTransfer.files && e.dataTransfer.files[0]) {
                setData('file', e.dataTransfer.files[0]);
            }
        },
        [setData]
    );

    const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        if (e.target.files && e.target.files[0]) {
            setData('file', e.target.files[0]);
        }
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(DocumentController.store());
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Upload Document" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div className="flex items-center gap-4">
                    <Button variant="ghost" size="icon" asChild>
                        <Link href={index()}>
                            <ArrowLeft className="size-4" />
                        </Link>
                    </Button>
                    <Heading
                        variant="small"
                        title="Upload Document"
                        description="Upload a new document to your library"
                    />
                </div>

                <Card className="mx-auto w-full max-w-xl">
                    <CardHeader>
                        <p className="text-sm text-muted-foreground">
                            Maximum file size: {formatBytes(maxFileSize)}
                        </p>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={handleSubmit} className="space-y-6">
                            <div
                                className={`relative rounded-lg border-2 border-dashed p-8 text-center transition-colors ${
                                    dragActive
                                        ? 'border-primary bg-primary/5'
                                        : 'border-muted-foreground/25 hover:border-muted-foreground/50'
                                }`}
                                onDragEnter={handleDrag}
                                onDragLeave={handleDrag}
                                onDragOver={handleDrag}
                                onDrop={handleDrop}
                            >
                                <input
                                    type="file"
                                    name="file"
                                    id="file"
                                    className="absolute inset-0 cursor-pointer opacity-0"
                                    onChange={handleFileChange}
                                />

                                {data.file ? (
                                    <div className="flex flex-col items-center gap-2">
                                        <File className="size-10 text-primary" />
                                        <p className="font-medium">{data.file.name}</p>
                                        <p className="text-sm text-muted-foreground">
                                            {formatBytes(data.file.size)}
                                        </p>
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="sm"
                                            onClick={() => setData('file', null)}
                                        >
                                            Choose different file
                                        </Button>
                                    </div>
                                ) : (
                                    <div className="flex flex-col items-center gap-2">
                                        <CloudUpload className="size-10 text-muted-foreground" />
                                        <p className="font-medium">
                                            Drop your file here or click to browse
                                        </p>
                                    </div>
                                )}
                            </div>
                            <InputError message={errors.file} />

                            <div className="space-y-2">
                                <Label htmlFor="title">Title (optional)</Label>
                                <Input
                                    id="title"
                                    name="title"
                                    value={data.title}
                                    onChange={(e) => setData('title', e.target.value)}
                                    placeholder="Give your document a title"
                                />
                                <p className="text-xs text-muted-foreground">
                                    Leave blank to use the filename or AI-suggested title
                                </p>
                                <InputError message={errors.title} />
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="description">Description (optional)</Label>
                                <textarea
                                    id="description"
                                    name="description"
                                    value={data.description}
                                    onChange={(e) => setData('description', e.target.value)}
                                    className="border-input placeholder:text-muted-foreground flex min-h-20 w-full rounded-md border bg-transparent px-3 py-2 text-sm shadow-xs focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] disabled:cursor-not-allowed disabled:opacity-50"
                                    placeholder="Add a description for your document"
                                />
                                <InputError message={errors.description} />
                            </div>

                            <div className="flex justify-end gap-3">
                                <Button type="button" variant="outline" asChild>
                                    <Link href={index()}>Cancel</Link>
                                </Button>
                                <Button type="submit" disabled={processing || !data.file}>
                                    {processing ? 'Uploading...' : 'Upload Document'}
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
