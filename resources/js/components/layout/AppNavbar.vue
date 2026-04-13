<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import {
    events,
    history,
    leaderboard,
    login as loginShow,
    logout as logoutRoute,
    openBids,
    profile,
    tasks,
    trending,
} from '@/routes';

const page = usePage();
const mobileOpen = ref(false);

const navLinks = [
    { label: 'Home', href: '/' },
    { label: 'Trending', href: trending.url() },
    { label: 'Open Bids', href: openBids.url() },
    { label: 'Event Items', href: events.url() },
    { label: 'History', href: history.url() },
    { label: 'Leaderboard', href: leaderboard.url() },
    { label: 'Tasks', href: tasks.url() },
];

const currentUser = computed(() => (page.props.auth?.user as any) ?? null);

function navItemClass(href: string): string {
    const path = href.split('?')[0];
    const active =
        href !== '#' &&
        (path === '/'
            ? page.url === '/' || page.url === ''
            : page.url.startsWith(path));
    const base =
        'font-headline px-1 py-0.5 text-sm tracking-tight transition-colors';

    if (active) {
        return `${base} border-b-2 border-primary font-extrabold text-primary`;
    }

    return `${base} font-bold text-secondary hover:text-primary`;
}
</script>

<template>
    <nav class="glass-nav sticky top-0 z-50 shadow-sm dark:shadow-none">
        <div
            class="relative mx-auto flex w-full max-w-screen-2xl items-center justify-between px-8 py-4"
        >
            <Link
                href="/"
                class="font-headline text-2xl font-black tracking-tighter text-primary"
            >
                CarryGo
            </Link>

            <div class="hidden items-center space-x-8 md:flex">
                <Link href="/" :class="navItemClass('/')"> Home </Link>
                <a
                    v-for="item in navLinks.filter((l) => l.href !== '/')"
                    :key="item.label"
                    :href="item.href"
                    :class="navItemClass(item.href)"
                >
                    {{ item.label }}
                </a>
            </div>

            <div class="flex items-center space-x-4 md:space-x-2">
                <div
                    v-if="currentUser"
                    class="hidden items-center space-x-4 md:flex"
                >
                    <Link
                        :href="profile.url()"
                        class="flex items-center gap-1.5 rounded-full px-4 py-2 text-sm font-bold text-secondary transition-all hover:bg-primary/10 hover:text-primary"
                    >
                        <span class="material-symbols-outlined text-xl!"
                            >person</span
                        >
                        {{ currentUser.active_point?.points ?? 0 }} pts
                    </Link>
                    <Link
                        :href="logoutRoute.url()"
                        method="post"
                        as="button"
                        class="rounded-full bg-secondary px-4 py-2 text-xs font-semibold text-white transition hover:bg-secondary/80"
                    >
                        Logout
                    </Link>
                </div>
                <Link
                    v-else
                    :href="loginShow.url()"
                    class="rounded-full bg-primary px-6 py-2 text-sm font-bold text-white transition-transform hover:opacity-90 active:scale-95"
                >
                    Log In
                </Link>
                <button
                    type="button"
                    class="flex items-center justify-center p-2 text-secondary transition-colors hover:text-primary md:hidden"
                    aria-label="Toggle menu"
                    @click="mobileOpen = !mobileOpen"
                >
                    <span class="material-symbols-outlined">{{
                        mobileOpen ? 'close' : 'menu'
                    }}</span>
                </button>
            </div>
        </div>

        <div
            v-show="mobileOpen"
            class="border-t border-outline-variant/30 px-8 py-4 md:hidden"
        >
            <div class="flex flex-col space-y-3">
                <Link
                    href="/"
                    :class="navItemClass('/')"
                    @click="mobileOpen = false"
                >
                    Home
                </Link>
                <a
                    v-for="item in navLinks.filter((l) => l.href !== '/')"
                    :key="`m-${item.label}`"
                    :href="item.href"
                    :class="navItemClass(item.href)"
                    @click="mobileOpen = false"
                >
                    {{ item.label }}
                </a>
                <Link
                    v-if="currentUser"
                    :href="profile.url()"
                    :class="navItemClass(profile.url())"
                    class="mt-2 flex items-center gap-2 border-t border-outline-variant/30 pt-4"
                    @click="mobileOpen = false"
                >
                    <span class="material-symbols-outlined text-lg!"
                        >person</span
                    >
                    My Profile
                </Link>
            </div>
        </div>

        <div
            class="absolute bottom-0 h-px w-full bg-linear-to-r from-transparent via-primary/20 to-transparent"
        />
    </nav>
</template>
