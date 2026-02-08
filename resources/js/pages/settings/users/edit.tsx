import { Transition } from '@headlessui/react';
import { Form, Head, Link, usePage } from '@inertiajs/react';
import { ArrowLeft, Check, Crown, Shield, X } from 'lucide-react';
import UserController from '@/actions/App/Http/Controllers/Settings/UserController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import { index, edit } from '@/routes/users';
import type { BreadcrumbItem, SharedData } from '@/types';

type User = {
    id: number;
    name: string;
    email: string;
    email_verified_at: string | null;
    is_admin: boolean;
    two_factor_enabled: boolean;
    created_at: string;
};

type Props = {
    user: User;
};

export default function EditUser({ user }: Props) {
    const { flash } = usePage<SharedData & { flash?: { success?: string; error?: string } }>().props;

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'User management',
            href: index().url,
        },
        {
            title: user.name,
            href: edit(user.id).url,
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Edit ${user.name}`} />

            <h1 className="sr-only">Edit User</h1>

            <SettingsLayout>
                <div className="space-y-6">
                    <div className="flex items-center gap-4">
                        <Button variant="ghost" size="icon" asChild>
                            <Link href={index()}>
                                <ArrowLeft className="size-4" />
                            </Link>
                        </Button>
                        <div className="flex-1">
                            <Heading
                                variant="small"
                                title={`Edit ${user.name}`}
                                description="Update user information"
                            />
                        </div>
                        <div className="flex items-center gap-2">
                            {user.is_admin && (
                                <Badge className="gap-1">
                                    <Crown className="size-3" />
                                    Admin
                                </Badge>
                            )}
                            {user.email_verified_at ? (
                                <Badge variant="secondary" className="gap-1">
                                    <Check className="size-3" />
                                    Verified
                                </Badge>
                            ) : (
                                <Badge variant="outline" className="gap-1 text-yellow-600">
                                    <X className="size-3" />
                                    Unverified
                                </Badge>
                            )}
                            {user.two_factor_enabled && (
                                <Badge variant="secondary" className="gap-1">
                                    <Shield className="size-3" />
                                    2FA
                                </Badge>
                            )}
                        </div>
                    </div>

                    {flash?.success && (
                        <div className="rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800 dark:border-green-800 dark:bg-green-900/20 dark:text-green-200">
                            {flash.success}
                        </div>
                    )}

                    {flash?.error && (
                        <div className="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800 dark:border-red-800 dark:bg-red-900/20 dark:text-red-200">
                            {flash.error}
                        </div>
                    )}

                    <Form
                        {...UserController.update.form(user.id)}
                        options={{
                            preserveScroll: true,
                        }}
                        className="space-y-6"
                    >
                        {({ errors, processing, recentlySuccessful }) => (
                            <>
                                <div className="grid gap-2">
                                    <Label htmlFor="name">Name</Label>
                                    <Input
                                        id="name"
                                        name="name"
                                        type="text"
                                        defaultValue={user.name}
                                        autoComplete="name"
                                        placeholder="Full name"
                                    />
                                    <InputError message={errors.name} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="email">Email</Label>
                                    <Input
                                        id="email"
                                        name="email"
                                        type="email"
                                        defaultValue={user.email}
                                        autoComplete="email"
                                        placeholder="email@example.com"
                                    />
                                    <InputError message={errors.email} />
                                    <p className="text-xs text-muted-foreground">
                                        Changing the email will require re-verification.
                                    </p>
                                </div>

                                <div className="flex items-center space-x-3">
                                    <Checkbox
                                        id="is_admin"
                                        name="is_admin"
                                        value="1"
                                        defaultChecked={user.is_admin}
                                    />
                                    <Label htmlFor="is_admin">Administrator</Label>
                                </div>

                                <div className="flex items-center gap-4">
                                    <Button disabled={processing}>
                                        Save Changes
                                    </Button>
                                    <Transition
                                        show={recentlySuccessful}
                                        enter="transition ease-in-out"
                                        enterFrom="opacity-0"
                                        leave="transition ease-in-out"
                                        leaveTo="opacity-0"
                                    >
                                        <p className="text-sm text-neutral-600">Saved</p>
                                    </Transition>
                                </div>
                            </>
                        )}
                    </Form>

                    <Separator />

                    <div className="space-y-6">
                        <Heading
                            variant="small"
                            title="Update password"
                            description="Set a new password for this user"
                        />

                        <Form
                            {...UserController.updatePassword.form(user.id)}
                            options={{
                                preserveScroll: true,
                            }}
                            resetOnSuccess
                            className="space-y-6"
                        >
                            {({ errors, processing, recentlySuccessful }) => (
                                <>
                                    <div className="grid gap-2">
                                        <Label htmlFor="password">New Password</Label>
                                        <Input
                                            id="password"
                                            name="password"
                                            type="password"
                                            autoComplete="new-password"
                                            placeholder="New password"
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

                                    <div className="flex items-center gap-4">
                                        <Button disabled={processing}>
                                            Update Password
                                        </Button>
                                        <Transition
                                            show={recentlySuccessful}
                                            enter="transition ease-in-out"
                                            enterFrom="opacity-0"
                                            leave="transition ease-in-out"
                                            leaveTo="opacity-0"
                                        >
                                            <p className="text-sm text-neutral-600">Password updated</p>
                                        </Transition>
                                    </div>
                                </>
                            )}
                        </Form>
                    </div>
                </div>
            </SettingsLayout>
        </AppLayout>
    );
}
