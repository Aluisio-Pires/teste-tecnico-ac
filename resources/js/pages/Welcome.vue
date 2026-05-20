<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    Coins,
    FileText,
    Zap,
    CheckCircle2,
    ShieldCheck,
    ArrowRightLeft,
    Layers,
    Code2,
    Database,
    Lock
} from 'lucide-vue-next';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { dashboard, login, register } from '@/routes';

defineProps<{
    auth: {
        user: any;
    };
}>();

const features = [
    {
        title: 'Micron Precision',
        description: 'Monetary values stored as integers with 6 decimal places, eliminating rounding errors in critical calculations.',
        icon: Coins,
        color: 'text-amber-500',
        bg: 'bg-amber-500/10'
    },
    {
        title: 'Ledger Architecture',
        description: 'Ledger and Subledger system for total traceability and immutable audit trail of every financial movement.',
        icon: FileText,
        color: 'text-blue-500',
        bg: 'bg-blue-500/10'
    },
    {
        title: 'Async Processing',
        description: 'Transactions processed via Laravel Jobs and Queues, ensuring scalability and resilience under high load.',
        icon: Zap,
        color: 'text-yellow-500',
        bg: 'bg-yellow-500/10'
    },
    {
        title: 'Quality & TDD',
        description: '100% test coverage with Pest, static analysis with Larastan (max level), and assisted refactoring with Rector.',
        icon: CheckCircle2,
        color: 'text-green-500',
        bg: 'bg-green-500/10'
    },
    {
        title: 'Atomic Transactions',
        description: 'ACID compliance guarantee using database locks and transactions to prevent race conditions.',
        icon: ShieldCheck,
        color: 'text-purple-500',
        bg: 'bg-purple-500/10'
    },
    {
        title: 'Reversibility',
        description: 'Native workflow for reversing transactions while maintaining the audit trail and balance consistency.',
        icon: ArrowRightLeft,
        color: 'text-rose-500',
        bg: 'bg-rose-500/10'
    }
];

const techStack = [
    { name: 'Laravel 13', icon: Layers },
    { name: 'Vue 3', icon: Code2 },
    { name: 'Inertia.js v3', icon: Zap },
    { name: 'PostgreSQL', icon: Database },
    { name: 'Tailwind 4', icon: Layers },
    { name: 'Fortify', icon: Lock }
];
</script>

<template>
    <Head title="Welcome to Finance App">
        <link rel="preconnect" href="https://rsms.me/" />
        <link rel="stylesheet" href="https://rsms.me/inter/inter.css" />
    </Head>

    <div class="min-h-screen bg-[#FDFDFC] text-[#1b1b18] dark:bg-[#0a0a0a] dark:text-[#EDEDEC] selection:bg-orange-500/30">
        <!-- Navigation -->
        <nav class="sticky top-0 z-50 border-b border-[#19140015] bg-[#FDFDFC]/80 backdrop-blur-md dark:border-[#3E3E3A]/50 dark:bg-[#0a0a0a]/80">
            <div class="mx-auto flex max-w-7xl items-center justify-between px-6 py-4">
                <div class="flex items-center gap-2">
                    <AppLogoIcon class="h-8 w-8 text-orange-600" />
                    <span class="text-lg font-bold tracking-tight">Finance App</span>
                </div>

                <div class="flex items-center gap-4">
                    <template v-if="auth.user">
                        <Link
                            :href="dashboard()"
                            class="rounded-lg bg-orange-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-orange-700"
                        >
                            Go to Dashboard
                        </Link>
                    </template>
                    <template v-else>
                        <Link
                            :href="login()"
                            class="text-sm font-medium text-[#706f6c] transition-colors hover:text-orange-600 dark:text-[#A1A09A] dark:hover:text-orange-500"
                        >
                            Log in
                        </Link>
                        <Link
                            :href="register()"
                            class="rounded-lg border border-[#19140035] px-4 py-2 text-sm font-medium transition-all hover:border-orange-600 hover:text-orange-600 dark:border-[#3E3E3A] dark:hover:border-orange-500 dark:hover:text-orange-500"
                        >
                            Register
                        </Link>
                    </template>
                </div>
            </div>
        </nav>

        <main>
            <!-- Hero Section -->
            <section class="relative overflow-hidden px-6 pt-24 pb-20 text-center lg:pt-32 lg:pb-32">
                <div class="mx-auto max-w-4xl">
                    <div class="inline-flex items-center gap-2 rounded-full border border-orange-500/20 bg-orange-500/5 px-3 py-1 text-sm font-medium text-orange-600 dark:text-orange-500">
                        <span class="relative flex h-2 w-2">
                            <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-orange-400 opacity-75"></span>
                            <span class="relative inline-flex h-2 w-2 rounded-full bg-orange-500"></span>
                        </span>
                        Full Stack Technical Challenge
                    </div>

                    <h1 class="mt-8 text-4xl font-extrabold tracking-tight sm:text-6xl lg:text-7xl">
                        Finance with <br class="hidden sm:block" />
                        <span class="bg-gradient-to-r from-orange-600 to-amber-500 bg-clip-text text-transparent">Atomic Precision</span>
                    </h1>

                    <p class="mt-6 text-lg text-[#706f6c] dark:text-[#A1A09A] sm:text-xl">
                        A robust financial system implementation utilizing the best practices of the Laravel ecosystem.
                        Security, auditability, and performance in every cent.
                    </p>

                    <div class="mt-10 flex flex-wrap justify-center gap-4">
                        <Link
                            v-if="!auth.user"
                            :href="register()"
                            class="rounded-xl bg-orange-600 px-8 py-4 text-lg font-semibold text-white shadow-lg shadow-orange-600/20 transition-all hover:bg-orange-700 hover:shadow-orange-600/40"
                        >
                            Get Started
                        </Link>
                        <Link
                            v-else
                            :href="dashboard()"
                            class="rounded-xl bg-orange-600 px-8 py-4 text-lg font-semibold text-white shadow-lg shadow-orange-600/20 transition-all hover:bg-orange-700 hover:shadow-orange-600/40"
                        >
                            Dashboard
                        </Link>
                        <a
                            href="https://github.com/Aluisio-Pires/teste-tecnico-ac"
                            target="_blank"
                            class="rounded-xl border border-[#19140035] bg-white px-8 py-4 text-lg font-semibold transition-all hover:bg-gray-50 dark:border-[#3E3E3A] dark:bg-white/5 dark:hover:bg-white/10"
                        >
                            View on GitHub
                        </a>
                    </div>
                </div>
            </section>

            <!-- Technical Decisions Grid -->
            <section class="mx-auto max-w-7xl px-6 py-20">
                <div class="grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
                    <div
                        v-for="feature in features"
                        :key="feature.title"
                        class="group relative rounded-2xl border border-[#19140015] bg-white p-8 transition-all hover:shadow-xl dark:border-[#3E3E3A]/50 dark:bg-[#161615]"
                    >
                        <div :class="[feature.bg, 'mb-6 inline-flex rounded-xl p-3 transition-transform group-hover:scale-110']">
                            <component :is="feature.icon" :class="['h-6 w-6', feature.color]" />
                        </div>
                        <h3 class="mb-3 text-xl font-bold">{{ feature.title }}</h3>
                        <p class="text-sm leading-relaxed text-[#706f6c] dark:text-[#A1A09A]">
                            {{ feature.description }}
                        </p>
                    </div>
                </div>
            </section>

            <!-- Tech Stack -->
            <section class="border-t border-[#19140010] bg-[#FDFDFC] py-20 dark:border-[#3E3E3A]/30 dark:bg-[#0a0a0a]">
                <div class="mx-auto max-w-7xl px-6 text-center">
                    <h2 class="mb-12 text-2xl font-bold tracking-tight text-[#706f6c] dark:text-[#A1A09A]">
                        Powered by Modern Technologies
                    </h2>
                    <div class="flex flex-wrap justify-center gap-8 lg:gap-12">
                        <div
                            v-for="tech in techStack"
                            :key="tech.name"
                            class="flex items-center gap-3 grayscale transition-all hover:grayscale-0"
                        >
                            <component :is="tech.icon" class="h-6 w-6 text-orange-600" />
                            <span class="text-lg font-medium">{{ tech.name }}</span>
                        </div>
                    </div>
                </div>
            </section>
        </main>

        <!-- Footer -->
        <footer class="border-t border-[#19140010] py-12 dark:border-[#3E3E3A]/30">
            <div class="mx-auto max-w-7xl px-6 text-center">
                <p class="text-sm text-[#706f6c] dark:text-[#A1A09A]">
                    Developed as a technical proof of concept. &copy; 2026
                </p>
            </div>
        </footer>
    </div>
</template>

<style scoped>
@import url('https://rsms.me/inter/inter.css');
</style>
