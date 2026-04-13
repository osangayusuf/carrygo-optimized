<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    events,
    history,
    howToPlay,
    leaderboard,
    openBids,
    profile,
    tasks,
    terms,
    trending,
} from '@/routes';
const year = new Date().getFullYear();
</script>

<template>
    <footer
        class="relative mt-20 w-full overflow-hidden bg-inverse-surface dark:bg-black"
    >
        <div
            class="pointer-events-none absolute top-10 left-10 select-none text-3xl font-black tracking-tighter text-white uppercase"
        >
            CarryGo
        </div>
        <div
            class="relative z-10 mx-auto grid w-full max-w-screen-2xl grid-cols-1 gap-12 px-12 py-20 md:grid-cols-4"
        >
            <div class="md:col-span-1">
                <p class="font-body my-8 text-sm leading-relaxed text-outline-variant">
                    Redefining the digital auction experience through transparency,
                    curation, and elite service. Your gateway to exclusive
                    opportunities.
                </p>
                <div class="flex space-x-4">
                    <a
                        class="flex h-10 w-10 items-center justify-center rounded-full bg-secondary text-inverse-on-surface/80 transition-colors hover:text-primary-container"
                        href="#"
                    >
                        <span class="material-symbols-outlined">public</span>
                    </a>
                    <a
                        class="flex h-10 w-10 items-center justify-center rounded-full bg-secondary text-inverse-on-surface/80 transition-colors hover:text-primary-container"
                        href="#"
                    >
                        <span class="material-symbols-outlined"
                            >alternate_email</span
                        >
                    </a>
                </div>
            </div>
            <div class="md:col-span-1">
                <h4 class="font-headline mb-6 text-lg font-bold text-white">
                    Auctions
                </h4>
                <ul class="space-y-4">
                    <li>
                        <Link
                            class="inline-block text-sm text-outline-variant transition-colors hover:translate-x-1 hover:text-white"
                            :href="trending.url()"
                            >Trending Now</Link
                        >
                    </li>
                    <li>
                        <Link
                            class="inline-block text-sm text-outline-variant transition-colors hover:translate-x-1 hover:text-white"
                            :href="openBids.url()"
                            >Open Bids</Link
                        >
                    </li>
                    <li>
                        <Link
                            class="inline-block text-sm text-outline-variant transition-colors hover:translate-x-1 hover:text-white"
                            :href="events.url()"
                            >Event Items</Link
                        >
                    </li>
                    <li>
                        <Link
                            class="inline-block text-sm text-outline-variant transition-colors hover:translate-x-1 hover:text-white"
                            :href="leaderboard.url()"
                            >Leaderboard</Link
                        >
                    </li>
                    <li>
                        <Link
                            class="inline-block text-sm text-outline-variant transition-colors hover:translate-x-1 hover:text-white"
                            :href="howToPlay.url()"
                            >How to Play</Link
                        >
                    </li>
                </ul>
            </div>
            <div class="md:col-span-1">
                <h4 class="font-headline mb-6 text-lg font-bold text-white">
                    Account & Support
                </h4>
                <ul class="space-y-4">
                    <li>
                        <Link
                            class="inline-block text-sm text-outline-variant transition-colors hover:translate-x-1 hover:text-white"
                            :href="profile.url()"
                            >My Profile</Link
                        >
                    </li>
                    <li>
                        <Link
                            class="inline-block text-sm text-outline-variant transition-colors hover:translate-x-1 hover:text-white"
                            :href="tasks.url()"
                            >Task Center</Link
                        >
                    </li>
                    <li>
                        <Link
                            class="inline-block text-sm text-outline-variant transition-colors hover:translate-x-1 hover:text-white"
                            :href="history.url()"
                            >Winning History</Link
                        >
                    </li>
                    <li>
                        <a
                            class="inline-block text-sm text-outline-variant transition-colors hover:translate-x-1 hover:text-white"
                            href="#"
                            >Privacy Policy</a
                        >
                    </li>
                    <li>
                        <Link
                            class="inline-block text-sm text-outline-variant transition-colors hover:translate-x-1 hover:text-white"
                            :href="terms.url()"
                            >Terms of Service</Link
                        >
                    </li>
                    <li>
                        <a
                            class="inline-block text-sm text-outline-variant transition-colors hover:translate-x-1 hover:text-white"
                            href="#"
                            >Contact Support</a
                        >
                    </li>
                </ul>
            </div>
            <div class="md:col-span-1">
                <h4 class="font-headline mb-6 text-lg font-bold text-white">
                    Newsletter
                </h4>
                <p class="mb-4 text-sm text-outline-variant">
                    Stay updated with our latest luxury drops.
                </p>
                <div
                    class="flex overflow-hidden rounded-xl bg-secondary focus-within:ring-2 focus-within:ring-primary-container/50"
                >
                    <input
                        class="w-full border-none bg-transparent px-4 py-3 text-sm text-white focus:ring-0"
                        placeholder="Your email"
                        type="email"
                    />
                    <button
                        type="button"
                        class="flex items-center justify-center bg-primary px-4 text-on-primary"
                    >
                        <span class="material-symbols-outlined">send</span>
                    </button>
                </div>
            </div>
        </div>
        <div
            class="mx-auto flex max-w-screen-2xl flex-col items-center justify-between gap-4 border-t border-secondary px-12 py-8 md:flex-row"
        >
            <p class="font-body text-sm text-outline">
                © {{ year }} CarryGo Executive. All rights reserved.
            </p>
            <div class="flex gap-8">
                <a
                    class="text-xs text-outline hover:text-primary-container"
                    href="#"
                    >Cookie Settings</a
                >
                <a
                    class="text-xs text-outline hover:text-primary-container"
                    href="#"
                    >Security</a
                >
            </div>
        </div>
    </footer>
</template>
