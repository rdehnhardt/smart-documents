import { Form, Head, Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import UserController from '@/actions/App/Http/Controllers/Settings/UserController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import { index, create } from '@/routes/users';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'User management',
        href: index().url,
    },
    {
        title: 'Create user',
        href: create().url,
    },
];

export default function CreateUser() {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Create user" />

            <h1 className="sr-only">Create User</h1>

            <SettingsLayout>
                <div className="space-y-6">
                    <div className="flex items-center gap-4">
                        <Button variant="ghost" size="icon" asChild>
                            <Link href={index()}>
                                <ArrowLeft className="size-4" />
                            </Link>
                        </Button>
                        <Heading
                            variant="small"
                            title="Create user"
                            description="Add a new user to the application"
                        />
                    </div>

                    <Form
                        {...UserController.store.form()}
                        className="space-y-6"
                    >
                        {({ errors, processing }) => (
                            <>
                                <div className="grid gap-2">
                                    <Label htmlFor="name">Name</Label>
                                    <Input
                                        id="name"
                                        name="name"
                                        type="text"
                                        autoComplete="name"
                                        placeholder="Full name"
                                        autoFocus
                                    />
                                    <InputError message={errors.name} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="email">Email</Label>
                                    <Input
                                        id="email"
                                        name="email"
                                        type="email"
                                        autoComplete="email"
                                        placeholder="email@example.com"
                                    />
                                    <InputError message={errors.email} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="password">Password</Label>
                                    <Input
                                        id="password"
                                        name="password"
                                        type="password"
                                        autoComplete="new-password"
                                        placeholder="Password"
                                    />
                                    <InputError message={errors.password} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="password_confirmation">Confirm Password</Label>
                                    <Input
                                        id="password_confirmation"
                                        name="password_confirmation"
                                        type="password"
                                        autoComplete="new-password"
                                        placeholder="Confirm password"
                                    />
                                    <InputError message={errors.password_confirmation} />
                                </div>

                                <div className="flex items-center space-x-3">
                                    <Checkbox id="is_admin" name="is_admin" value="1" />
                                    <Label htmlFor="is_admin">Administrator</Label>
                                </div>

                                <div className="flex items-center gap-4">
                                    <Button disabled={processing}>
                                        Create User
                                    </Button>
                                    <Button variant="outline" asChild>
                                        <Link href={index()}>Cancel</Link>
                                    </Button>
                                </div>
                            </>
                        )}
                    </Form>
                </div>
            </SettingsLayout>
        </AppLayout>
    );
}
