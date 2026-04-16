<script setup>
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

const page = usePage();
const quickLoginProcessing = ref(false);

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post('/login');
};

const quickLogin = () => {
    if (quickLoginProcessing.value) return;
    quickLoginProcessing.value = true;
    router.post('/quick-login', {}, {
        preserveScroll: true,
        onFinish: () => { quickLoginProcessing.value = false; },
    });
};
</script>

<template>
    <Head title="Sign In — TravelRocket" />

    <div class="min-h-screen bg-gray-50">
        <!-- Minimal header -->
        <header class="border-b border-gray-100 bg-white">
            <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
                <a href="/" class="flex items-center gap-2.5">
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-blue-600 text-sm font-bold text-white shadow-sm">TR</span>
                    <span class="text-lg font-bold tracking-tight text-gray-900">TravelRocket</span>
                </a>
                <a href="/" class="text-sm font-semibold text-gray-600 transition hover:text-gray-900">
                    &larr; Back to Home
                </a>
            </div>
        </header>

        <!-- Main content -->
        <main class="mx-auto flex max-w-7xl items-start justify-center px-4 py-12 sm:px-6 lg:px-8 lg:py-20">
            <div class="w-full max-w-md">
                <!-- Heading -->
                <div class="text-center">
                    <h1 class="text-2xl font-extrabold tracking-tight text-gray-900 sm:text-3xl">Welcome back</h1>
                    <p class="mt-2 text-sm text-gray-500">Sign in to access your workspace</p>
                </div>

                <!-- Flash messages -->
                <div v-if="page.props.flash?.success" class="mt-6 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                    {{ page.props.flash.success }}
                </div>
                <div v-if="page.props.flash?.error" class="mt-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                    {{ page.props.flash.error }}
                </div>

                <!-- Quick Login -->
                <div class="mt-8 rounded-2xl border border-blue-100 bg-blue-50/60 p-5">
                    <div class="flex items-start gap-3">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-blue-100">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4.5 w-4.5 text-blue-600"><path fill-rule="evenodd" d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z" clip-rule="evenodd" /></svg>
                        </div>
                        <div class="flex-1">
                            <p class="text-sm font-semibold text-gray-900">Demo Workspace</p>
                            <p class="mt-0.5 text-xs leading-relaxed text-gray-500">Quick walkthrough with sample data — no credentials needed.</p>
                            <button
                                type="button"
                                class="mt-3 inline-flex items-center gap-1.5 rounded-full bg-blue-600 px-4 py-2 text-xs font-bold text-white shadow-sm transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50"
                                :disabled="quickLoginProcessing"
                                @click="quickLogin"
                            >
                                <svg v-if="!quickLoginProcessing" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="h-3.5 w-3.5"><path fill-rule="evenodd" d="M3 10a.75.75 0 01.75-.75h10.638L10.23 5.29a.75.75 0 111.04-1.08l5.5 5.25a.75.75 0 010 1.08l-5.5 5.25a.75.75 0 11-1.04-1.08l4.158-3.96H3.75A.75.75 0 013 10z" clip-rule="evenodd" /></svg>
                                <svg v-else xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="h-3.5 w-3.5 animate-spin"><circle cx="8" cy="8" r="6" stroke="currentColor" stroke-width="2" fill="none" stroke-dasharray="28" stroke-dashoffset="8" /></svg>
                                {{ quickLoginProcessing ? 'Entering...' : 'Quick Login' }}
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Divider -->
                <div class="relative mt-8">
                    <div class="absolute inset-0 flex items-center"><div class="w-full border-t border-gray-200"></div></div>
                    <div class="relative flex justify-center"><span class="bg-gray-50 px-3 text-xs font-medium uppercase tracking-wider text-gray-400">or sign in with email</span></div>
                </div>

                <!-- Login form -->
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
                        <p v-if="form.errors.email" class="mt-1.5 text-xs text-red-600">{{ form.errors.email }}</p>
                    </div>

                    <div>
                        <div class="mb-1.5 flex items-center justify-between">
                            <label for="password" class="text-sm font-medium text-gray-700">Password</label>
                            <a href="/forgot-password" class="text-xs font-semibold text-blue-600 transition hover:text-blue-700">Forgot password?</a>
                        </div>
                        <input
                            id="password"
                            v-model="form.password"
                            type="password"
                            autocomplete="current-password"
                            placeholder="••••••••"
                            class="w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm text-gray-900 shadow-sm outline-none transition placeholder:text-gray-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20"
                        />
                        <p v-if="form.errors.password" class="mt-1.5 text-xs text-red-600">{{ form.errors.password }}</p>
                    </div>

                    <label class="flex items-center gap-2.5 text-sm text-gray-600">
                        <input v-model="form.remember" type="checkbox" class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500" />
                        Keep me signed in
                    </label>

                    <button
                        type="submit"
                        class="w-full rounded-xl bg-gray-900 px-5 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-gray-800 disabled:cursor-not-allowed disabled:opacity-50"
                        :disabled="form.processing || quickLoginProcessing"
                    >
                        {{ form.processing ? 'Signing in...' : 'Sign In' }}
                    </button>
                </form>

                <!-- Footer -->
                <p class="mt-8 text-center text-xs text-gray-400">&copy; {{ new Date().getFullYear() }} TravelRocket. All rights reserved.</p>
            </div>
        </main>
    </div>
</template>
