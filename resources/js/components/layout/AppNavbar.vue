<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { router } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import {
    events,
    history,
    home,
    leaderboard,
    login as loginShow,
    logout as logoutRoute,
    openBids,
    profile,
    tasks,
    trending,
} from '@/routes';
import { formatDate } from '@/lib/utils';

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
const mobileOpen = ref(false);
const notificationsOpen = ref(false);
const params = typeof window !== 'undefined' ? new URLSearchParams(window.location.search) : new URLSearchParams();


const notifications = computed<Notification[]>(() => (page.props.notifications as Notification[]) ?? []);

const categories = computed<string[]>(() => (page.props.categories as string[]) ?? []);

const LS_KEY = 'carrygo_read_notification_ids';

function loadReadIds(): number[] {
    try {
        return JSON.parse(localStorage.getItem(LS_KEY) ?? '[]') as number[];
    } catch {
        return [];
    }
}

const readIds = ref<number[]>(loadReadIds());

const unreadCount = computed(() => notifications.value.filter((n) => !readIds.value.includes(n.id)).length);

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

    if (panel && !panel.contains(event.target as Node) && button && !button.contains(event.target as Node)) {
        notificationsOpen.value = false;
    }
}

onMounted(() => document.addEventListener('click', closeNotifications));
onBeforeUnmount(() => document.removeEventListener('click', closeNotifications));

const search = ref(params.get('search') ?? '');

function visit(extra: Record<string, string | number> = {}) {
    router.get(
        home.url(),
        {
            ...(search.value ? { search: search.value } : {}),
            ...extra,
        },
        { preserveState: true, preserveScroll: true, replace: true }
    );
}

const onSearch = useDebounceFn(() => visit(), 400);


const navLinks = [
    { label: 'Home', href: home.url(), icon: 'pi pi-home' },
    { label: 'Trending', href: trending.url(), icon: 'pi pi-chart-line' },
    { label: 'Open Bids', href: openBids.url(), icon: 'pi pi-box' },
    { label: 'Event Items', href: events.url(), icon: 'pi pi-calendar' },
    { label: 'History', href: history.url(), icon: 'pi pi-history' },
    { label: 'Leaderboard', href: leaderboard.url(), icon: 'pi pi-chart-bar' },
    { label: 'Tasks', href: tasks.url(), icon: 'pi pi-check-square' },
];

const currentUser = computed(() => (page.props.auth?.user as any) ?? null);

const routeMap: Record<string, any> = {
    openBids,
    events,
    leaderboard,
    tasks,
};

function getNotificationLink(routeKey?: string): string | null {
    if (!routeKey || !routeMap[routeKey]) return null;

    return routeMap[routeKey].url();
}

function navItemClass(href: string): string {
    const path = href.split('?')[0];
    const active =
        href !== '#' &&
        (path === home.url()
            ? page.url === home.url() || page.url === ''
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
        <!-- TOP BAR -->
        <div class="bg-navy text-lemon text-xs py-1 px-4 flex items-center justify-between gap-2">
            <div class="flex-1 overflow-hidden whitespace-nowrap">
                <span class="inline-block animate-marquee">
                    <i class="pi pi-bolt mr-1"></i> LIVE AUCTION: Duffel Bag @ ₦150,000 &nbsp;|&nbsp;
                    <i class="pi pi-trophy mr-1"></i> Gucci Sunglasses – 65/3500 bids &nbsp;|&nbsp;
                    <i class="pi pi-bolt mr-1"></i> Prada Suede Olive – 9% progress! &nbsp;|&nbsp;
                    <i class="pi pi-gift mr-1"></i> White Fendi Shirt – ₦50,000 &nbsp;|&nbsp;
                    <i class="pi pi-star-fill mr-1"></i> Denim Backpack – Only 40 bids so far &nbsp;|&nbsp;
                    <i class="pi pi-send mr-1"></i> New auction drops every Monday!
                </span>
            </div>
        </div>

        <!-- NAVBAR -->
        <nav class="bg-white border-b-2 border-lemon shadow-md">
            <div class="max-w-7xl mx-auto flex items-center gap-3 md:gap-10 py-1.5 px-4">
                <Link :href="home.url()" class="flex items-center no-underline shrink-0">
                    <img class="h-10 w-auto block" src="/logo.png" alt="CarryGo">
                </Link>
                <div class="flex-1 flex min-w-0">
                    <input type="text" v-model="search" @input="onSearch"
                        placeholder="Search luxury items, brands, auctions..."
                        class="flex-1 border-2 border-forest border-r-0 py-2 px-3.5 text-sm font-sans rounded-l-xl outline-none min-w-0">
                    <button
                        class="bg-forest text-lemon border-none py-2 px-4.5 text-sm font-bold cursor-pointer rounded-r-xl whitespace-nowrap hover:bg-forest-dark"><i
                            class="pi pi-search"></i></button>
                </div>
                <div class="flex items-center gap-2 md:gap-5">
                    <button class="bg-transparent border-none cursor-pointer relative p-1.5">
                        <i class="pi pi-bell text-xl text-muted-green"></i>
                        <div
                            class="absolute top-0 right-0 bg-ink text-lemon rounded-full w-4 h-4 text-xs font-extrabold flex items-center justify-center">
                            2</div>
                    </button>
                    <Link :href="loginShow.url()"
                        class="bg-lemon text-navy border-none py-2 px-4.5 rounded-xl text-sm font-extrabold cursor-pointer whitespace-nowrap font-sans hover:bg-amber"
                        as="button">Log In</Link>
                </div>
            </div>
        </nav>

        <!-- SUB NAV -->
        <div class="bg-navy">
            <div
                class="max-w-7xl mx-auto flex items-center min-w-full gap-1 justify-between overflow-x-auto hide-scrollbar">
                <Link v-for="link in navLinks" :key="link.label"
                    :class="['text-white no-underline py-2 px-4 text-sm font-bold whitespace-nowrap block mx-auto hover:bg-lemon/18 hover:text-white cursor-pointer', navItemClass(link.href)]"
                    :href="link.href" @click="mobileOpen = false" as="a">
                    <i v-if="link.icon" :class="[link.icon, 'mr-1']"></i> {{ link.label }}
                </Link>
            </div>
        </div>
        <div class="absolute bottom-0 h-px w-full bg-linear-to-r from-transparent via-primary/20 to-transparent" />
    </nav>
</template>
