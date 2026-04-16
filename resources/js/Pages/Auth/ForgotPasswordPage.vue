<script setup>
import { Head, useForm, usePage } from '@inertiajs/vue3';

const page = usePage();

const form = useForm({
    email: '',
});

const submit = () => {
    form.post('/forgot-password');
};
</script>

<template>
    <Head title="Forgot Password" />

    <div class="min-h-screen bg-gray-50">
        <header class="border-b border-gray-100 bg-white">
            <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
                <a href="/" class="flex items-center gap-2.5">
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-blue-600 text-sm font-bold text-white shadow-sm">TR</span>
                    <span class="text-lg font-bold tracking-tight text-gray-900">TravelRocket</span>
                </a>
                <a href="/login" class="text-sm font-semibold text-gray-600 transition hover:text-gray-900">
                    &larr; Back to Login
                </a>
            </div>
        </header>

        <main class="mx-auto flex max-w-7xl items-start justify-center px-4 py-12 sm:px-6 lg:px-8 lg:py-20">
            <div class="w-full max-w-md">
                <div class="text-center">
                    <h1 class="text-2xl font-extrabold tracking-tight text-gray-900 sm:text-3xl">Reset your password</h1>
                    <p class="mt-2 text-sm text-gray-500">Enter the email tied to your account and we'll send a reset link.</p>
                </div>

                <div v-if="page.props.flash?.status" class="mt-6 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                    {{ page.props.flash.status }}
                </div>

                <div v-if="form.errors.email" class="mt-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                    {{ form.errors.email }}
                </div>

                <form class="mt-8 space-y-5" @submit.prevent="submit">
                    <div>
                        <label for="email" class="mb-1.5 block text-sm font-medium text-gray-700">Email</label>
                        <input
                            id="email"
                            v-model="form.email"
                            type="email"
                            autocomplete="email"
                            autofocus
                            placeholder="you@example.com"
                            class="w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm text-gray-900 shadow-sm outline-none transition placeholder:text-gray-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20"
                        />
                    </div>

                    <button
                        type="submit"
                        class="w-full rounded-xl bg-gray-900 px-5 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-gray-800 disabled:cursor-not-allowed disabled:opacity-50"
                        :disabled="form.processing"
                    >
                        {{ form.processing ? 'Sending link...' : 'Send Reset Link' }}
                    </button>
                </form>

                <p class="mt-8 text-center text-xs text-gray-400">&copy; {{ new Date().getFullYear() }} TravelRocket. All rights reserved.</p>
            </div>
        </main>
    </div>
</template>
