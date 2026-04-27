<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
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

const notifications = computed<Notification[]>(() => (page.props.notifications as Notification[]) ?? []);

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

function formatDate(dateStr: string): string {
    const date = new Date(dateStr);

    return date.toLocaleDateString('en-GB', { day: 'numeric', month: 'short' });
}

onMounted(() => document.addEventListener('click', closeNotifications));
onBeforeUnmount(() => document.removeEventListener('click', closeNotifications));

const navLinks = [
    { label: 'Home', href: home.url() },
    { label: 'Trending', href: trending.url() },
    { label: 'Open Bids', href: openBids.url() },
    { label: 'Event Items', href: events.url() },
    { label: 'History', href: history.url() },
    { label: 'Leaderboard', href: leaderboard.url() },
    { label: 'Tasks', href: tasks.url() },
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
        <div class="relative mx-auto flex w-full max-w-screen-2xl items-center justify-between px-8 py-4">
            <Link :href="home.url()" class="font-headline text-2xl font-black tracking-tighter text-primary">
                <img src="logo.png" class="h-10 w-auto object-contain">
            </Link>

            <div class="hidden items-center space-x-8 md:flex">
                <Link :href="home.url()" :class="navItemClass(home.url())"> Home </Link>
                <a v-for="item in navLinks.filter((l) => l.href !== home.url())" :key="item.label" :href="item.href"
                    :class="navItemClass(item.href)">
                    {{ item.label }}
                </a>
            </div>

            <div class="flex items-center gap-2">
                <!-- Notification Bell -->
                <div class="relative">
                    <button id="notification-btn" type="button"
                        class="relative flex items-center justify-center rounded-full p-2 text-secondary transition-colors hover:bg-primary/10 hover:text-primary"
                        aria-label="Notifications" @click="toggleNotifications">
                        <span class="material-symbols-outlined text-xl!">notifications</span>
                        <span v-if="unreadCount > 0"
                            class="absolute -right-0.5 -top-0.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-error px-1 text-[10px] font-bold leading-none text-white">
                            {{ unreadCount > 9 ? '9+' : unreadCount }}
                        </span>
                    </button>

                    <!-- Dropdown Panel -->
                    <Transition enter-active-class="transition duration-150 ease-out"
                        enter-from-class="opacity-0 scale-95 translate-y-1"
                        enter-to-class="opacity-100 scale-100 translate-y-0"
                        leave-active-class="transition duration-100 ease-in"
                        leave-from-class="opacity-100 scale-100 translate-y-0"
                        leave-to-class="opacity-0 scale-95 translate-y-1">
                        <div v-if="notificationsOpen" id="notification-panel"
                            class="max-sm:fixed max-sm:inset-x-4 max-sm:top-[68px] sm:absolute sm:right-0 sm:top-full sm:mt-2 sm:w-[360px] z-50">
                            <div
                                class="overflow-hidden rounded-2xl border border-outline-variant/40 bg-white shadow-xl">
                                <!-- Header -->
                                <div
                                    class="flex items-center justify-between border-b border-outline-variant/30 px-4 py-3">
                                    <h3 class="font-headline text-sm font-extrabold text-secondary">
                                        Notifications
                                    </h3>
                                    <button v-if="unreadCount > 0" type="button"
                                        class="text-xs font-semibold text-primary transition-opacity hover:opacity-70"
                                        @click="markAllRead">
                                        Mark all read
                                    </button>
                                    <span v-else class="text-xs text-secondary/50">
                                        All caught up
                                    </span>
                                </div>

                                <!-- Notification List -->
                                <ul
                                    class="hide-scrollbar max-h-[420px] divide-y divide-outline-variant/20 overflow-y-auto">
                                    <li v-for="notification in notifications" :key="notification.id"
                                        class="relative flex items-start gap-3 px-4 py-3.5 transition-colors hover:bg-surface-container-low"
                                        :class="isUnread(notification.id) ? 'bg-primary/5' : ''">
                                        <!-- Unread accent bar -->
                                        <span v-if="isUnread(notification.id)"
                                            class="absolute inset-y-0 left-0 w-0.5 rounded-r bg-primary" />

                                        <!-- Icon -->
                                        <div
                                            class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-primary-fixed text-primary">
                                            <span class="material-symbols-outlined text-base!">
                                                {{ notification.icon }}
                                            </span>
                                        </div>

                                        <!-- Content -->
                                        <div class="min-w-0 flex-1">
                                            <p class="text-sm font-semibold leading-snug text-secondary"
                                                :class="isUnread(notification.id) ? 'text-on-surface' : ''">
                                                {{ notification.title }}
                                            </p>
                                            <p class="mt-0.5 text-xs leading-relaxed text-secondary/70">
                                                {{ notification.body }}
                                            </p>

                                            <!-- Action Button -->
                                            <div v-if="notification.action_route && getNotificationLink(notification.action_route)"
                                                class="mt-2.5 mb-1 flex">
                                                <Link :href="getNotificationLink(notification.action_route)!"
                                                    class="inline-flex items-center gap-1 rounded-xl bg-primary/10 px-3 py-1.5 text-[11px] font-bold tracking-wide text-primary transition-colors hover:bg-primary hover:text-white"
                                                    @click="notificationsOpen = false">
                                                    {{ notification.action_label || 'View Details' }}
                                                    <span
                                                        class="material-symbols-outlined text-[13px]!">arrow_forward</span>
                                                </Link>
                                            </div>

                                            <p class="mt-1.5 text-[10px] font-medium text-secondary/40">
                                                {{ formatDate(notification.created_at) }}
                                            </p>
                                        </div>
                                    </li>

                                    <li v-if="notifications.length === 0" class="px-4 py-8 text-center">
                                        <span class="material-symbols-outlined mb-2 block text-4xl! text-secondary/25">
                                            notifications_off
                                        </span>
                                        <p class="text-sm text-secondary/40">No notifications yet</p>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </Transition>
                </div>

                <!-- Auth buttons -->
                <div v-if="currentUser" class="hidden items-center gap-2 md:flex">
                    <Link :href="profile.url()"
                        class="flex items-center gap-1.5 rounded-full px-4 py-2 text-sm font-bold text-secondary transition-all hover:bg-primary/10 hover:text-primary">
                        <span class="material-symbols-outlined text-xl!">person</span>
                        {{ currentUser.active_point?.points ?? 0 }} pts
                    </Link>
                    <Link :href="logoutRoute.url()" method="post" as="button"
                        class="rounded-full bg-secondary px-4 py-2 text-xs font-semibold text-white transition hover:bg-secondary/80">
                        Logout
                    </Link>
                </div>
                <Link v-else :href="loginShow.url()"
                    class="rounded-full bg-primary px-6 py-2 text-sm font-bold text-white transition-transform hover:opacity-90 active:scale-95">
                    Log In
                </Link>

                <!-- Mobile hamburger -->
                <button type="button"
                    class="flex items-center justify-center p-2 text-secondary transition-colors hover:text-primary md:hidden"
                    aria-label="Toggle menu" @click="mobileOpen = !mobileOpen">
                    <span class="material-symbols-outlined">{{
                        mobileOpen ? 'close' : 'menu'
                        }}</span>
                </button>
            </div>
        </div>

        <div v-show="mobileOpen" class="border-t border-outline-variant/30 px-8 py-4 md:hidden">
            <div class="flex flex-col space-y-3">
                <Link :href="home.url()" :class="navItemClass(home.url())" @click="mobileOpen = false">
                    Home
                </Link>
                <a v-for="item in navLinks.filter((l) => l.href !== home.url())" :key="`m-${item.label}`"
                    :href="item.href" :class="navItemClass(item.href)" @click="mobileOpen = false">
                    {{ item.label }}
                </a>
                <Link v-if="currentUser" :href="profile.url()" :class="navItemClass(profile.url())"
                    class="mt-2 flex items-center gap-2 border-t border-outline-variant/30 pt-4"
                    @click="mobileOpen = false">
                    <span class="material-symbols-outlined text-lg!">person</span>
                    My Profile
                </Link>
            </div>
        </div>

        <div class="absolute bottom-0 h-px w-full bg-linear-to-r from-transparent via-primary/20 to-transparent" />
    </nav>
</template>
