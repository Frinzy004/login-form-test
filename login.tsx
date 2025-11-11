import InputError from '@/components/input-error';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthLayout from '@/layouts/auth-layout';
import { Head, useForm } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';
import { useState } from 'react';

interface LoginProps {
    status?: string;
    canResetPassword: boolean;
    flash?: {  // ← Make sure this is defined
        success?: string;
        error?: string;
        warning?: string;
        info?: string;
        message?: string;
    };
}

export default function Login({ status, canResetPassword, flash  }: LoginProps) {
    const { data, setData, post, processing, errors, reset } = useForm({
        email: '',
        password: '',
        remember: false,
    });

    const [localErrors, setLocalErrors] = useState<{ email?: string; password?: string }>({});

    const validateForm = () => {
        const newErrors: { email?: string; password?: string } = {};

        if (!data.email) {
            newErrors.email = 'Email is required';
        } else if (!/\S+@\S+\.\S+/.test(data.email)) {
            newErrors.email = 'Email is invalid';
        }

        if (!data.password) {
            newErrors.password = 'Password is required';
        } else if (data.password.length < 6) {
            newErrors.password = 'Password must be at least 6 characters';
        }

        setLocalErrors(newErrors);
        return Object.keys(newErrors).length === 0;
    };

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        
        // Clear previous errors
        setLocalErrors({});
        
        if (validateForm()) {
            post('/login', {
                onError: (errors) => {
                    // Handle server errors
                    console.log('Server errors:', errors);
                },
                onSuccess: () => {
                    reset();
                }
            });
        }
    };

    // Use local errors first, then server errors
    const displayErrors = {
        email: localErrors.email || errors.email,
        password: localErrors.password || errors.password,
    };

    return (
        <AuthLayout
            title="Account Login"
            description="Enter your email and password below to log in"
        >
            <Head title="Log in" />

            {/* Flash Messages Display */}
            <div className="space-y-3 mb-6">
                {flash?.success && (
                    <div className="p-3 rounded-md bg-green-50 border border-green-200 dark:bg-green-900/20 dark:border-green-800">
                        <p className="text-sm font-medium text-green-800 dark:text-green-200">
                            {flash.success}
                        </p>
                    </div>
                )}
                 {flash?.error && (
                    <div className="p-3 rounded-md bg-red-50 border border-red-200 dark:bg-red-900/20 dark:border-red-800">
                        <p className="text-sm font-medium text-red-800 dark:text-red-200">
                            {flash.error}
                        </p>
                    </div>
                )}
                {flash?.message && (
                    <div className="p-3 rounded-md bg-blue-50 border border-blue-200 dark:bg-blue-900/20 dark:border-blue-800">
                        <p className="text-sm font-medium text-blue-800 dark:text-blue-200">
                            {flash.message}
                        </p>
                    </div>
                )}
            </div>

            <form onSubmit={submit} className="flex flex-col gap-6" noValidate>
                {processing && (
                    <div className="flex justify-center">
                        <LoaderCircle className="h-4 w-4 animate-spin" />
                    </div>
                )}
                
                <div className="grid gap-6">
                    <div className="grid gap-2">
                        <Label htmlFor="email">Email address</Label>
                        <Input
                            id="email"
                            type="email"
                            name="email"
                            required
                            autoFocus
                            value={data.email}
                            onChange={(e) => {
                                setData('email', e.target.value);
                                // Clear error when user starts typing
                                if (localErrors.email) {
                                    setLocalErrors(prev => ({ ...prev, email: undefined }));
                                }
                            }}
                            className='transition-colors duration-200 hover:border-cyan-400 focus:border-cyan-500 focus:ring-cyan-500'
                            tabIndex={1}
                            autoComplete="email"
                            placeholder="email@example.com"
                        />
                        <InputError message={displayErrors.email} />
                    </div>

                    <div className="grid gap-2">
                        <div className="flex items-center">
                            <Label htmlFor="password">Password</Label>
                        </div>
                        <Input
                            id="password"
                            type="password"
                            name="password"
                            required
                            value={data.password}
                            onChange={(e) => {
                                setData('password', e.target.value);
                                // Clear error when user starts typing
                                if (localErrors.password) {
                                    setLocalErrors(prev => ({ ...prev, password: undefined }));
                                }
                            }}
                            className='transition-colors duration-200 hover:border-cyan-400 focus:border-cyan-500 focus:ring-cyan-500'
                            tabIndex={2}
                            autoComplete="current-password"
                            placeholder="Password"
                        />
                        <InputError message={displayErrors.password} />
                    </div>

                    <Button
                        type="submit"
                        className="mt-4 w-full transition-colors duration-200 hover:bg-cyan-700 bg-cyan-600 text-white border-cyan-600"
                        tabIndex={4}
                        disabled={processing}
                        data-test="login-button"
                    >
                        {processing && (
                            <LoaderCircle className="h-4 w-4 animate-spin mr-2" />
                        )}
                        {processing ? 'Logging in...' : 'Log in'}
                    </Button>
                </div>

                <div className="text-center text-sm text-muted-foreground">
                    Don't have an account?{' '}
                    <TextLink href="/register" className="ml-auto text-sm transition-colors duration-200 hover:text-cyan-500" tabIndex={5}>
                        Sign up
                    </TextLink>
                </div>
            </form>

            {status && (
                <div className="mb-4 text-center text-sm font-medium text-green-600">
                    {status}
                </div>
            )}
        </AuthLayout>
    );
}