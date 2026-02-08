import { Head, Link, router, usePage } from '@inertiajs/react';
import { Check, Crown, Edit, Plus, Shield, Trash2, X } from 'lucide-react';
import { useState } from 'react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import { index, create, edit, destroy } from '@/routes/users';
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
    users: User[];
};

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'User management',
        href: index().url,
    },
];

function formatDate(dateString: string) {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
}

export default function UsersIndex({ users }: Props) {
    const { auth, flash } = usePage<SharedData & { flash?: { success?: string; error?: string } }>().props;
    const [deleteUser, setDeleteUser] = useState<User | null>(null);

    const handleDelete = () => {
        if (deleteUser) {
            router.delete(destroy(deleteUser.id).url, {
                preserveScroll: true,
                onSuccess: () => setDeleteUser(null),
            });
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="User management" />

            <h1 className="sr-only">User Management</h1>

            <SettingsLayout>
                <div className="space-y-6">
                    <div className="flex items-center justify-between">
                        <Heading
                            variant="small"
                            title="Users"
                            description="Manage users who can access this application"
                        />
                        <Button asChild>
                            <Link href={create()}>
                                <Plus className="mr-2 size-4" />
                                Add User
                            </Link>
                        </Button>
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

                    <div className="rounded-lg border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Name</TableHead>
                                    <TableHead>Email</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead>Created</TableHead>
                                    <TableHead className="w-[100px]">Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {users.map((user) => (
                                    <TableRow key={user.id}>
                                        <TableCell className="font-medium">
                                            {user.name}
                                            {user.id === auth.user.id && (
                                                <Badge variant="secondary" className="ml-2">
                                                    You
                                                </Badge>
                                            )}
                                        </TableCell>
                                        <TableCell>{user.email}</TableCell>
                                        <TableCell>
                                            <div className="flex items-center gap-2">
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
                                                {user.is_admin && (
                                                    <Badge className="gap-1">
                                                        <Crown className="size-3" />
                                                        Admin
                                                    </Badge>
                                                )}
                                                {user.two_factor_enabled && (
                                                    <Badge variant="secondary" className="gap-1">
                                                        <Shield className="size-3" />
                                                        2FA
                                                    </Badge>
                                                )}
                                            </div>
                                        </TableCell>
                                        <TableCell>{formatDate(user.created_at)}</TableCell>
                                        <TableCell>
                                            <div className="flex items-center gap-1">
                                                <Button variant="ghost" size="icon" asChild>
                                                    <Link href={edit(user.id)}>
                                                        <Edit className="size-4" />
                                                    </Link>
                                                </Button>
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    onClick={() => setDeleteUser(user)}
                                                    disabled={user.id === auth.user.id}
                                                >
                                                    <Trash2 className="size-4" />
                                                </Button>
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </div>
                </div>
            </SettingsLayout>

            <Dialog open={!!deleteUser} onOpenChange={() => setDeleteUser(null)}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Delete User</DialogTitle>
                        <DialogDescription>
                            Are you sure you want to delete {deleteUser?.name}? This action cannot be undone.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setDeleteUser(null)}>
                            Cancel
                        </Button>
                        <Button variant="destructive" onClick={handleDelete}>
                            Delete
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
