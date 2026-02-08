import { Transition } from '@headlessui/react';
import { Form, Head, Link } from '@inertiajs/react';
import { ArrowLeft, X } from 'lucide-react';
import { useState } from 'react';
import DocumentController from '@/actions/App/Http/Controllers/DocumentController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { index, show, edit } from '@/routes/documents';
import type { BreadcrumbItem, Document } from '@/types';

type Props = {
    document: Document;
};

export default function DocumentEdit({ document }: Props) {
    const [tags, setTags] = useState<string[]>(document.tags || []);
    const [tagInput, setTagInput] = useState('');

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Documents', href: index().url },
        { title: document.title || document.original_name, href: show(document.id).url },
        { title: 'Edit', href: edit(document.id).url },
    ];

    const addTag = (e: React.KeyboardEvent<HTMLInputElement>) => {
        if (e.key === 'Enter' && tagInput.trim()) {
            e.preventDefault();
            if (!tags.includes(tagInput.trim()) && tags.length < 10) {
                setTags([...tags, tagInput.trim()]);
            }
            setTagInput('');
        }
    };

    const removeTag = (tagToRemove: string) => {
        setTags(tags.filter((tag) => tag !== tagToRemove));
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Edit: ${document.title || document.original_name}`} />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div className="flex items-center gap-4">
                    <Button variant="ghost" size="icon" asChild>
                        <Link href={show(document.id)}>
                            <ArrowLeft className="size-4" />
                        </Link>
                    </Button>
                    <Heading
                        variant="small"
                        title="Edit Document"
                        description={document.original_name}
                    />
                </div>

                <Card className="mx-auto w-full max-w-xl">
                    <CardHeader>
                        <CardTitle>Document Information</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <Form
                            {...DocumentController.update.form(document.id)}
                            className="space-y-6"
                            options={{ preserveScroll: true }}
                        >
                            {({ processing, recentlySuccessful, errors, setData }) => (
                                <>
                                    <div className="space-y-2">
                                        <Label htmlFor="title">Title</Label>
                                        <Input
                                            id="title"
                                            name="title"
                                            defaultValue={document.title || ''}
                                            placeholder="Document title"
                                        />
                                        <InputError message={errors.title} />
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="description">Description</Label>
                                        <textarea
                                            id="description"
                                            name="description"
                                            defaultValue={document.description || ''}
                                            className="border-input placeholder:text-muted-foreground flex min-h-24 w-full rounded-md border bg-transparent px-3 py-2 text-sm shadow-xs focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] disabled:cursor-not-allowed disabled:opacity-50"
                                            placeholder="Add a description"
                                        />
                                        <InputError message={errors.description} />
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="tags">Tags</Label>
                                        <Input
                                            id="tags"
                                            value={tagInput}
                                            onChange={(e) => setTagInput(e.target.value)}
                                            onKeyDown={(e) => {
                                                addTag(e);
                                                setData('tags', [...tags, tagInput.trim()]);
                                            }}
                                            placeholder="Type and press Enter to add tags"
                                            disabled={tags.length >= 10}
                                        />
                                        <input
                                            type="hidden"
                                            name="tags"
                                            value={JSON.stringify(tags)}
                                        />
                                        {tags.length > 0 && (
                                            <div className="flex flex-wrap gap-2 pt-2">
                                                {tags.map((tag) => (
                                                    <Badge
                                                        key={tag}
                                                        variant="secondary"
                                                        className="gap-1 pr-1"
                                                    >
                                                        {tag}
                                                        <button
                                                            type="button"
                                                            onClick={() => removeTag(tag)}
                                                            className="ml-1 rounded-full p-0.5 hover:bg-muted-foreground/20"
                                                        >
                                                            <X className="size-3" />
                                                        </button>
                                                    </Badge>
                                                ))}
                                            </div>
                                        )}
                                        <p className="text-xs text-muted-foreground">
                                            {tags.length}/10 tags
                                        </p>
                                        <InputError message={errors.tags} />
                                    </div>

                                    <div className="flex items-center gap-4">
                                        <Button type="submit" disabled={processing}>
                                            Save Changes
                                        </Button>
                                        <Button type="button" variant="outline" asChild>
                                            <Link href={show(document.id)}>Cancel</Link>
                                        </Button>
                                        <Transition
                                            show={recentlySuccessful}
                                            enter="transition ease-in-out"
                                            enterFrom="opacity-0"
                                            leave="transition ease-in-out"
                                            leaveTo="opacity-0"
                                        >
                                            <p className="text-sm text-green-600">Saved</p>
                                        </Transition>
                                    </div>
                                </>
                            )}
                        </Form>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
