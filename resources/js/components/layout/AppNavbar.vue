<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { router } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { formatDate } from '@/lib/utils';
import {
    events,
    history,
    home,
    howToPlay,
    leaderboard,
    login as loginShow,
    logout as logoutRoute,
    openBids,
    profile,
    tasks,
    trending,
} from '@/routes';

interface Notification {
    id: number;
    title: string;
    body: string;
    icon: string;
    created_at: string;
    action_route?: string;
    action_label?: string;
}

const page = usePage();
const notificationsOpen = ref(false);
const currentPath = computed(() => page.url.split('?')[0]);

const notifications = computed<Notification[]>(
    () => (page.props.notifications as Notification[]) ?? [],
);

const LS_KEY = 'carrygo_read_notification_ids';

function loadReadIds(): number[] {
    try {
        return JSON.parse(localStorage.getItem(LS_KEY) ?? '[]') as number[];
    } catch {
        return [];
    }
}

const readIds = ref<number[]>(loadReadIds());

const unreadCount = computed(
    () =>
        notifications.value.filter((n) => !readIds.value.includes(n.id)).length,
);

function isUnread(id: number): boolean {
    return !readIds.value.includes(id);
}

function markAllRead(): void {
    readIds.value = notifications.value.map((n) => n.id);
    localStorage.setItem(LS_KEY, JSON.stringify(readIds.value));
}

function toggleNotifications(): void {
    notificationsOpen.value = !notificationsOpen.value;
}

function closeNotifications(event: MouseEvent): void {
    const panel = document.getElementById('notification-panel');
    const button = document.getElementById('notification-btn');

    if (
        panel &&
        !panel.contains(event.target as Node) &&
        button &&
        !button.contains(event.target as Node)
    ) {
        notificationsOpen.value = false;
    }
}

onMounted(() => document.addEventListener('click', closeNotifications));
onBeforeUnmount(() =>
    document.removeEventListener('click', closeNotifications),
);

const search = ref(
    new URLSearchParams(
        typeof window !== 'undefined' ? window.location.search : '',
    ).get('search') ?? '',
);

function visit(extra: Record<string, string | number> = {}) {
    const currentParams = new URLSearchParams(
        typeof window !== 'undefined' ? window.location.search : '',
    );
    const existing: Record<string, string> = {};
    currentParams.forEach((value, key) => {
        existing[key] = value;
    });

    router.get(
        currentPath.value,
        {
            ...existing,
            ...(search.value
                ? { search: search.value }
                : { search: undefined }),
            ...extra,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}

const onSearch = useDebounceFn(() => visit({ page: 1 }), 400);

const navLinks = [
    { label: 'Home', href: home.url(), icon: 'pi pi-home' },
    { label: 'Trending', href: trending.url(), icon: 'pi pi-chart-line' },
    { label: 'Open Bids', href: openBids.url(), icon: 'pi pi-box' },
    { label: 'Event Items', href: events.url(), icon: 'pi pi-calendar' },
    { label: 'Winners', href: history.url(), icon: 'pi pi-history' },
    { label: 'Leaderboard', href: leaderboard.url(), icon: 'pi pi-chart-bar' },
    { label: 'Tasks', href: tasks.url(), icon: 'pi pi-check-square' },
    {
        label: 'How to play',
        href: howToPlay.url(),
        icon: 'pi pi-question-circle',
    },
];

const firstLineLinks = computed(() => navLinks.slice(0, 4));
const secondLineLinks = computed(() => navLinks.slice(4));

const currentUser = computed(() => (page.props.auth?.user as any) ?? null);

const routeMap: Record<string, any> = {
    openBids,
    events,
    leaderboard,
    tasks,
};

function getNotificationLink(routeKey?: string): string | null {
    if (!routeKey || !routeMap[routeKey]) {
        return null;
    }

    return routeMap[routeKey].url();
}

function navItemClass(href: string): string {
    const path = href.split('?')[0];
    const active =
        href !== '#' &&
        (path === home.url()
            ? page.url === home.url() || page.url === ''
            : page.url.startsWith(path));
    const base = 'font-headline px-1 py-0.5 tracking-tight transition-colors';

    if (active) {
        return `${base} border-b-2 border-primary font-extrabold text-primary`;
    }

    return `${base} font-bold text-secondary hover:text-primary`;
}
</script>

<template>
    <nav class="glass-nav sticky top-0 z-50 shadow-sm dark:shadow-none">
        <!-- TOP BAR -->
        <div
            class="flex items-center justify-between gap-2 bg-navy px-4 py-1 text-xs text-lemon"
        >
            <div class="flex-1 overflow-hidden whitespace-nowrap">
                <span class="inline-block animate-marquee">
                    <i class="pi pi-bolt mr-1"></i> LIVE AUCTION: Duffel Bag @
                    ₦150,000 &nbsp;|&nbsp;
                    <i class="pi pi-trophy mr-1"></i> Gucci Sunglasses – 65/3500
                    bids &nbsp;|&nbsp; <i class="pi pi-bolt mr-1"></i> Prada
                    Suede Olive – 9% progress! &nbsp;|&nbsp;
                    <i class="pi pi-gift mr-1"></i> White Fendi Shirt – ₦50,000
                    &nbsp;|&nbsp; <i class="pi pi-star-fill mr-1"></i> Denim
                    Backpack – Only 40 bids so far &nbsp;|&nbsp;
                    <i class="pi pi-send mr-1"></i> New auction drops every
                    Monday!
                </span>
            </div>
        </div>

        <!-- NAVBAR -->
        <nav class="border-b-2 border-lemon bg-white shadow-md">
            <div
                class="mx-auto flex max-w-7xl items-center gap-3 px-4 py-2 sm:py-1.5 md:gap-10"
            >
                <Link
                    :href="home.url()"
                    class="flex shrink-0 items-center no-underline"
                >
                    <img
                        class="block h-7 w-auto sm:h-10"
                        :src="`${page.props.asset_url}logo.png`"
                        alt="CarryGo"
                    />
                </Link>
                <div class="flex min-w-0 flex-1">
                    <input
                        type="text"
                        v-model="search"
                        @input="onSearch"
                        placeholder="Search luxury items, brands, auctions..."
                        class="min-w-0 flex-1 rounded-l-xl border-2 border-r-0 border-forest px-3.5 py-1.5 font-sans text-xs outline-none sm:py-2 sm:text-sm"
                    />
                    <button
                        class="cursor-pointer rounded-r-xl border-none bg-forest px-4.5 py-2 text-sm font-bold whitespace-nowrap text-lemon hover:bg-forest-dark"
                    >
                        <i class="pi pi-search"></i>
                    </button>
                </div>
                <div class="relative flex items-center gap-2 md:gap-5">
                    <button
                        id="notification-btn"
                        @click="toggleNotifications"
                        class="relative cursor-pointer border-none bg-transparent p-1.5"
                    >
                        <i
                            class="pi pi-bell text-base text-muted-green transition-colors hover:text-navy sm:text-xl"
                        ></i>
                        <div
                            v-if="unreadCount > 0"
                            class="absolute top-0 right-0 flex h-4 w-4 items-center justify-center rounded-full bg-ink text-[10px] font-extrabold text-lemon"
                        >
                            {{ unreadCount > 9 ? '9+' : unreadCount }}
                        </div>
                    </button>

                    <!-- Notification Panel -->
                    <div
                        v-if="notificationsOpen"
                        id="notification-panel"
                        class="absolute top-full right-0 z-50 mt-2 flex max-h-[80vh] w-80 flex-col overflow-hidden rounded-2xl border-2 border-lemon bg-white shadow-xl sm:w-96 md:-right-2"
                    >
                        <div
                            class="flex items-center justify-between border-b-2 border-lemon bg-gray-50/50 px-4 py-3"
                        >
                            <h3
                                class="m-0 font-headline text-sm font-extrabold text-navy"
                            >
                                Notifications
                            </h3>
                            <button
                                v-if="unreadCount > 0"
                                @click="markAllRead"
                                class="cursor-pointer border-none bg-transparent p-0 text-xs font-bold text-primary transition-colors hover:text-forest"
                            >
                                Mark all read
                            </button>
                        </div>
                        <div
                            class="hide-scrollbar flex-1 overflow-x-hidden overflow-y-auto bg-white"
                        >
                            <div
                                v-if="notifications.length === 0"
                                class="flex h-full flex-col items-center justify-center gap-2 p-8 text-center text-sm font-bold text-secondary"
                            >
                                <i
                                    class="pi pi-check-circle text-3xl text-gray-300"
                                ></i>
                                You're all caught up!
                            </div>
                            <div
                                v-for="notification in notifications"
                                :key="notification.id"
                                :class="[
                                    'flex gap-3.5 border-b border-gray-100 px-4 py-4 transition-colors last:border-b-0',
                                    isUnread(notification.id)
                                        ? 'bg-forest/5'
                                        : 'bg-white hover:bg-gray-50',
                                ]"
                            >
                                <div class="mt-0.5 shrink-0">
                                    <span
                                        class="material-symbols-outlined text-2xl"
                                        :class="
                                            isUnread(notification.id)
                                                ? 'text-forest'
                                                : 'text-gray-400'
                                        "
                                    >
                                        {{
                                            notification.icon || 'notifications'
                                        }}
                                    </span>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div
                                        class="mb-1.5 flex items-start justify-between gap-2"
                                    >
                                        <h4
                                            :class="[
                                                'm-0 font-sans text-sm leading-tight',
                                                isUnread(notification.id)
                                                    ? 'font-extrabold text-navy'
                                                    : 'font-bold text-secondary',
                                            ]"
                                        >
                                            {{ notification.title }}
                                        </h4>
                                        <span
                                            class="mt-0.5 shrink-0 text-[10px] font-bold whitespace-nowrap text-gray-400"
                                            >{{
                                                formatDate(
                                                    notification.created_at,
                                                )
                                            }}</span
                                        >
                                    </div>
                                    <p
                                        class="m-0 text-xs leading-relaxed text-secondary"
                                    >
                                        {{ notification.body }}
                                    </p>
                                    <Link
                                        v-if="
                                            notification.action_route &&
                                            getNotificationLink(
                                                notification.action_route,
                                            )
                                        "
                                        :href="
                                            getNotificationLink(
                                                notification.action_route,
                                            )!
                                        "
                                        class="mt-2.5 inline-flex items-center gap-1 text-xs font-bold text-forest transition-colors hover:text-forest-dark"
                                        @click="notificationsOpen = false"
                                    >
                                        {{
                                            notification.action_label ||
                                            'View details'
                                        }}
                                        <i
                                            class="pi pi-arrow-right text-[10px]"
                                        ></i>
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>

                    <template v-if="!currentUser">
                        <Link
                            :href="loginShow.url()"
                            class="cursor-pointer rounded-xl border-none bg-lemon px-4.5 py-2 font-sans text-sm font-extrabold whitespace-nowrap text-navy transition-colors hover:bg-amber"
                            as="button"
                            >Log In</Link
                        >
                    </template>
                    <template v-else>
                        <Link
                            :href="profile.url()"
                            class="flex cursor-pointer items-center gap-1.5 rounded-xl border-none bg-lemon px-3.5 py-1.5 font-sans text-xs font-extrabold whitespace-nowrap text-navy transition-colors hover:bg-amber sm:py-2 sm:text-sm md:px-4.5"
                            as="button"
                        >
                            <i class="pi pi-wallet text-sm text-forest"></i>
                            <span class="hidden sm:inline"
                                >{{
                                    currentUser.active_point?.points ?? 0
                                }}
                                pts</span
                            >
                            <span class="sm:hidden">{{
                                currentUser.active_point?.points ?? 0
                            }}</span>
                        </Link>
                        <Link
                            :href="logoutRoute.url()"
                            method="post"
                            class="hidden cursor-pointer rounded-xl border-2 border-gray-200 bg-transparent px-3 py-1.5 text-sm font-extrabold whitespace-nowrap text-gray-500 transition-colors hover:border-gray-300 hover:text-navy sm:block md:px-4"
                            as="button"
                            >Log Out</Link
                        >
                        <Link
                            :href="logoutRoute.url()"
                            method="post"
                            class="cursor-pointer border-none bg-transparent p-1.5 text-gray-500 hover:text-navy sm:hidden"
                            as="button"
                        >
                            <i class="pi pi-sign-out text-sm sm:text-xl"></i>
                        </Link>
                    </template>
                </div>
            </div>
        </nav>

        <!-- SUB NAV -->
        <div class="relative bg-navy">
            <!-- Desktop View -->
            <div
                class="hide-scrollbar mx-auto hidden max-w-7xl min-w-full items-center justify-between gap-1 overflow-x-auto md:flex"
            >
                <a
                    v-for="link in navLinks"
                    :key="link.label"
                    :class="[
                        'mx-auto block cursor-pointer px-4 py-2 text-sm whitespace-nowrap text-white no-underline hover:bg-lemon/18 hover:text-white',
                        navItemClass(link.href),
                    ]"
                    :href="link.href"
                >
                    <i v-if="link.icon" :class="[link.icon, 'mr-1']"></i>
                    {{ link.label }}
                </a>
            </div>

            <!-- Mobile View -->
            <div class="flex w-full flex-col md:hidden">
                <div class="flex w-full items-center justify-between px-1">
                    <a
                        v-for="link in firstLineLinks"
                        :key="link.label"
                        :class="[
                            'flex-1 cursor-pointer px-1 py-2 text-center text-[11px] whitespace-nowrap text-white no-underline hover:bg-lemon/18 hover:text-white sm:text-xs',
                            navItemClass(link.href),
                        ]"
                        :href="link.href"
                    >
                        {{ link.label }}
                    </a>
                </div>
                <div
                    class="flex w-full items-center justify-between border-t border-white/10 bg-navy/90 px-1"
                >
                    <a
                        v-for="link in secondLineLinks"
                        :key="link.label"
                        :class="[
                            'flex-1 cursor-pointer px-1 py-2 text-center text-[11px] whitespace-nowrap text-white no-underline hover:bg-lemon/18 hover:text-white sm:text-xs',
                            navItemClass(link.href),
                        ]"
                        :href="link.href"
                    >
                        {{ link.label }}
                    </a>
                </div>
            </div>
        </div>
        <div
            class="absolute bottom-0 h-px w-full bg-linear-to-r from-transparent via-primary/20 to-transparent"
        />
    </nav>
</template>
