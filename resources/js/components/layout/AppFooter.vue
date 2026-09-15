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
    <footer class="mt-7 bg-[#050e05] text-[#8aaa80]">
        <div
            class="mx-auto grid max-w-[1300px] grid-cols-1 gap-8 px-4 pt-10 pb-6 md:grid-cols-2 lg:grid-cols-[1.5fr_1fr_1fr_1.5fr]"
        >
            <div>
                <div class="mb-2 h-20 w-auto">
                    <img
                        :src="`${$page.props.asset_url}carrygo-footer.png`"
                        alt="CarryGo"
                        class="h-full w-auto"
                    />
                </div>
                <div class="mb-3.5 text-sm leading-relaxed text-[#4a6a40]">
                    Redefining the digital auction experience through
                    transparency, curation, and elite service. Your gateway to
                    exclusive opportunities.
                </div>
                <div class="flex gap-2.5">
                    <a
                        href="https://www.instagram.com/carrygo_nigeria?igsh=MTdiNHM2ZTJldnp1cA%3D%3D&utm_source=qr"
                        target="blank"
                        class="flex h-9 w-9 cursor-pointer items-center justify-center rounded-full border border-lemon/20 bg-lemon/10 text-[15px] text-lemon transition-colors hover:bg-forest"
                    >
                        <span class="pi pi-instagram"></span>
                    </a>
                    <a
                        href="https://www.facebook.com/share/18TeWUCG19/"
                        target="blank"
                        class="flex h-9 w-9 cursor-pointer items-center justify-center rounded-full border border-lemon/20 bg-lemon/10 text-[15px] text-lemon transition-colors hover:bg-forest"
                    >
                        <span class="pi pi-facebook"></span>
                    </a>
                </div>
                <div class="mt-4 flex flex-col items-start gap-1">
                    <span
                        class="text-[10px] font-semibold tracking-wider text-[#4a6a40] uppercase"
                        >Customer Support</span
                    >
                    <a
                        href="tel:08077219175"
                        class="flex items-center gap-1.5 text-sm font-semibold text-[#8aaa80] transition-colors hover:text-lemon"
                    >
                        <span class="pi pi-phone text-xs"></span>
                        0807 721 9175
                    </a>
                </div>
            </div>
            <div>
                <h4
                    class="mb-3.5 text-sm font-extrabold tracking-wider text-lemon uppercase"
                >
                    Auctions
                </h4>
                <Link
                    :href="trending.url()"
                    class="mb-2 block text-sm text-[#4a6a40] transition-colors hover:text-lemon"
                    >Trending Now</Link
                >
                <Link
                    :href="openBids.url()"
                    class="mb-2 block text-sm text-[#4a6a40] transition-colors hover:text-lemon"
                    >Open Bids</Link
                >
                <Link
                    :href="events.url()"
                    class="mb-2 block text-sm text-[#4a6a40] transition-colors hover:text-lemon"
                    >Event Items</Link
                >
                <Link
                    :href="leaderboard.url()"
                    class="mb-2 block text-sm text-[#4a6a40] transition-colors hover:text-lemon"
                    >Leaderboard</Link
                >
                <Link
                    :href="howToPlay.url()"
                    class="mb-2 block text-sm text-[#4a6a40] transition-colors hover:text-lemon"
                    >How to Play</Link
                >
            </div>
            <div>
                <h4
                    class="mb-3.5 text-sm font-extrabold tracking-wider text-lemon uppercase"
                >
                    Account &amp; Support
                </h4>
                <Link
                    :href="profile.url()"
                    class="mb-2 block text-sm text-[#4a6a40] transition-colors hover:text-lemon"
                    >My Profile</Link
                >
                <Link
                    :href="tasks.url()"
                    class="mb-2 block text-sm text-[#4a6a40] transition-colors hover:text-lemon"
                    >Task Center</Link
                >
                <Link
                    :href="history.url()"
                    class="mb-2 block text-sm text-[#4a6a40] transition-colors hover:text-lemon"
                    >Winning History</Link
                >
                <a
                    href="#"
                    class="mb-2 block text-sm text-[#4a6a40] transition-colors hover:text-lemon"
                    >Privacy Policy</a
                >
                <Link
                    :href="terms.url()"
                    class="mb-2 block text-sm text-[#4a6a40] transition-colors hover:text-lemon"
                    >Terms of Service</Link
                >
                <a
                    href="#"
                    class="mb-2 block text-sm text-[#4a6a40] transition-colors hover:text-lemon"
                    >Contact Support</a
                >
            </div>
        </div>
        <div
            class="mx-auto flex max-w-[1300px] flex-col items-center justify-between gap-4 border-t border-[#0f2010] p-4 text-xs text-[#2a4030] md:flex-row"
        >
            <span>© {{ year }} CarryGo Executive. All rights reserved.</span>
            <div class="flex gap-4">
                <a
                    href="#"
                    class="text-[#2a4030] transition-colors hover:text-lemon"
                    >Cookie Settings</a
                >
                <a
                    href="#"
                    class="text-[#2a4030] transition-colors hover:text-lemon"
                    >Security</a
                >
                <a
                    href="#"
                    class="text-[#2a4030] transition-colors hover:text-lemon"
                    >Privacy</a
                >
            </div>
        </div>
    </footer>
</template>
