<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { router } from '@inertiajs/vue3';
import { useDebounceFn, useIntervalFn } from '@vueuse/core';
import { ref } from 'vue';
import HomeBidItemsSection from '@/components/home/HomeBidItemsSection.vue';
import HomeHeroSection from '@/components/home/HomeHeroSection.vue';
import HomeWinnerPopup from '@/components/home/HomeWinnerPopup.vue';
import { formatPrice,calcProgress } from '@/lib/utils';
import { trending, openBids as openBidsRoute, history, home, login, events, tasks, leaderboard, howToPlay } from '@/routes';

export type Bidder = {
    msisdn: string;
    total_points: string;
};

export type Bid = {
    id: number;
    name: string;
    image: string;
    url: string;
    price: string;
    open_points: number;
    rating: string;
    open_date: number;
    status: 0 | 1 | 2;
    created_at: string;
    bid_entry_points: number | null;
    bid_active_points: number | null;
    ends_at: string | null;
    top_bidders?: Bidder[];
};

export type HeroBid = Pick<Bid, 'id' | 'name' | 'image' | 'url' | 'price' | 'open_points' | 'rating' | 'open_date' | 'status' | 'created_at'>;

export type Winner = {
    id: number;
    msisdn: string;
    total_points: number;
    bidid: number;
    created_at: string;
    bid: { id: number; name: string; image: string; url: string; price: string; } | null;
};

export type Review = {
    id: number;
    user_id: number;
    rating: number;
    comment: string;
    social_platform?: string | null;
    social_handle?: string | null;
    bidid: number;
    created_at: string;
    bid: { id: number; name: string; image: string; url: string; } | null;
    user: { id: number; msisdn: string; } | null;
};

const props = defineProps<{
    heroBid: Bid | null;
    trendingBids: Bid[];
    openBids: Bid[];
    luxuryBids: Bid[];
    categoryBids: Record<string, Bid[]>;
    bids: Bid[];
    categories: string[];
    winners: Winner[];
    userPoints: number | null;
    reviews: Review[];
    winnerPopup: Winner | null;
}>();

const bidItemsSectionRef = ref<InstanceType<typeof HomeBidItemsSection> | null>(null);
const params = typeof window !== 'undefined' ? new URLSearchParams(window.location.search) : new URLSearchParams();

const search = ref(params.get('search') ?? '');

function openBidModal(bid: Bid) {
    if (bidItemsSectionRef.value) {
        bidItemsSectionRef.value.openBidModal(bid);
    }
}

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
const getCategoryIcon = (category: string): string => {
    const map: Record<string, string> = {
        'Appliances': 'kitchen',
        'Computing': 'computer',
        'Electronics': 'devices',
        'Fashion': 'checkroom',
        'Gadgets & Accessories': 'headphones',
        'Gaming': 'sports_esports',
        'Health & Beauty': 'spa',
        'Home & Office': 'home_work',
        'Musical Instrument': 'piano',
        'Supermarket': 'local_grocery_store',
    };

    return map[category] || 'category';
};
</script>

<template>
    <Head title="Home" />
    <HomeWinnerPopup v-if="props.winnerPopup" :winner="props.winnerPopup" />

    <!-- Hidden section to retain the bid modal logic -->
    <HomeBidItemsSection ref="bidItemsSectionRef" :bids="[]" :categories="[]" :userPoints="props.userPoints" class="hidden" />

    <div class="home-wrapper">
        <HomeHeroSection :getCategoryIcon="getCategoryIcon" />

        <!-- PRODUCT STRIP (8 items - 2 rows of 4) -->
        <div class="product-strip mt-6" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));">
          <div class="pcard" v-for="bid in props.bids.slice(0, 8)" :key="bid.id" @click="openBidModal(bid)">
            <div class="pcard-img">
              <img :src="bid.image || 'https://images.unsplash.com/photo-1622560480654-d96214fdc887?w=400&h=300&fit=crop'" :alt="bid.name">
              <span class="pcard-live" v-if="bid.status === 1">LIVE</span>
              <span class="pcard-pct">{{ calcProgress(bid) }}%</span>
            </div>
            <div class="pcard-body">
              <div class="pcard-name">{{ bid.name }}</div>
              <div class="pcard-price">{{ formatPrice(bid.price) }}</div>
              <div class="pcard-bar-wrap"><div class="pcard-bar-fill" :style="{ width: calcProgress(bid) + '%' }"></div></div>
              <div class="pcard-pts">{{ bid.bid_entry_points || 0 }} / {{ bid.open_points }} pts</div>
              <button class="pcard-btn">Place Bid</button>
            </div>
          </div>
        </div>

        <!-- LIVE TICKER -->
        <div class="ticker">
          <div class="ticker-inner">
            <span class="ticker-item" v-for="bid in props.bids" :key="bid.id"><span class="dot"></span> {{ bid.name }} <span class="price">{{ formatPrice(bid.price) }}</span><span class="ticker-live" v-if="bid.status === 1">LIVE</span></span>
          </div>
        </div>

        <!-- CATEGORIES -->
        <div class="section">
          <div class="sec-header">
            <div class="sec-title">Browse Categories</div>
            <Link :href="trending.url()" class="view-all">View All →</Link>
          </div>
          <div class="cats">
            <Link :href="trending.url({ category: cat })" class="cat-card" v-for="cat in props.categories" :key="cat" style="text-decoration:none;">
                <div class="cat-icon"><span class="material-symbols-outlined">{{ getCategoryIcon(cat) }}</span></div><div class="cat-name">{{ cat }}</div>
            </Link>
          </div>
        </div>

        <!-- TRENDING BIDS -->
        <div class="section" v-if="props.trendingBids?.length > 0">
          <div class="sec-header">
            <div class="sec-title">🔥 Trending Bids</div>
            <Link :href="trending.url()" class="view-all">View More →</Link>
          </div>
          <div class="product-strip" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); padding: 0; margin: 0;">
             <div class="item-card" v-for="bid in props.trendingBids.slice(0, 4)" :key="bid.id" @click="openBidModal(bid)">
                <div class="item-img"><img :src="bid.image || 'https://images.unsplash.com/photo-1553062407-98eeb64c6a62?w=300&h=200&fit=crop'" :alt="bid.name"><span class="live-badge" v-if="bid.status === 1">LIVE</span></div>
                <div class="item-body">
                    <div class="item-name">{{ bid.name }}</div>
                    <div class="item-price">{{ formatPrice(bid.price) }}</div>
                    <div class="progress-wrap"><div class="progress-info"><span>{{ bid.bid_entry_points || 0 }}/{{ formatPrice(bid.open_points) }} pts</span><span>{{ calcProgress(bid) }}%</span></div><div class="progress-bar"><div class="progress-fill" :style="{ width: calcProgress(bid) + '%' }"></div></div></div>
                    <button class="btn-bid">Place Bid</button>
                </div>
            </div>
          </div>
        </div>

        <!-- FEATURE BANNERS -->
        <div class="section">
          <div class="banners">
            <div class="banner a">
              <div class="banner-text">
                <div class="banner-label">🎯 Task Center</div>
                <div class="banner-title">Win More<br>Points Free!</div>
                <div class="banner-desc">Complete simple tasks to earn bidding points and increase your chances of winning luxury items.</div>
                <button class="btn-banner">Visit Task Center →</button>
              </div>
              <div class="banner-art">🏆</div>
            </div>
            <div class="banner b">
              <div class="banner-text">
                <div class="banner-label">🔒 Verified Integrity</div>
                <div class="banner-title">100% Fair<br>&amp; Secure!</div>
                <div class="banner-desc">Every bid is recorded on our secure database. Fully transparent, fully trustworthy auction process.</div>
                <button class="btn-banner">Learn More →</button>
              </div>
              <div class="banner-art">🛡️</div>
            </div>
          </div>
        </div>

        <!-- LIVE OPPORTUNITIES -->
        <div class="section" v-if="props.openBids?.length > 0">
          <div class="sec-header">
            <div class="sec-title">⚡ Live Opportunities</div>
            <Link :href="openBidsRoute.url()" class="view-all">View All →</Link>
          </div>
          <div class="product-strip" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); padding: 0; margin: 0;">
            <div class="item-card" v-for="bid in props.openBids.slice(0, 4)" :key="bid.id" @click="openBidModal(bid)">
                <div class="item-img"><img :src="bid.image || 'https://images.unsplash.com/photo-1553062407-98eeb64c6a62?w=300&h=200&fit=crop'" :alt="bid.name"><span class="live-badge" v-if="bid.status === 1">LIVE</span></div>
                <div class="item-body">
                    <div class="item-name">{{ bid.name }}</div>
                    <div class="item-price">{{ formatPrice(bid.price) }}</div>
                    <div class="progress-wrap"><div class="progress-info"><span>{{ bid.bid_entry_points || 0 }}/{{ formatPrice(bid.open_points) }} pts</span><span>{{ calcProgress(bid) }}%</span></div><div class="progress-bar"><div class="progress-fill" :style="{ width: calcProgress(bid) + '%' }"></div></div></div>
                    <button class="btn-bid">Place Bid</button>
                </div>
            </div>
          </div>
        </div>

        <!-- TESTIMONIALS -->
        <div class="section" v-if="props.reviews?.length > 0">
          <div class="sec-header"><div class="sec-title">💬 What Our Community Says</div></div>
          <div style="text-align:center;font-size:13px;color:var(--muted);margin-bottom:18px">Hear from winners who have scored amazing bids on CarryGo</div>
          <div class="testi-grid">
            <div class="testi-card" v-for="review in props.reviews.slice(0, 3)" :key="review.id">
                <div class="stars">★★★★★</div>
                <div class="testi-text">"{{ review.comment }}"</div>
                <div class="testi-user">
                    <div class="testi-avatar">👤</div>
                    <div>
                        <div class="testi-name">{{ review.user?.msisdn || 'Anonymous' }}</div>
                        <div class="testi-role">Verified Bidder</div>
                    </div>
                </div>
                <span class="testi-item-tag">Item: {{ review.bid?.name || 'Luxury Item' }}</span>
            </div>
          </div>
        </div>

        <!-- TRUST BAR -->
        <div class="trust-bar">
          <div class="trust-inner">
            <div class="trust-item"><div class="trust-icon">🚀</div><div class="trust-title">Fast Delivery</div><div class="trust-desc">Winners receive items within 3–5 working days nationwide</div></div>
            <div class="trust-item"><div class="trust-icon">🔒</div><div class="trust-title">Secure Payments</div><div class="trust-desc">All transactions protected with bank-grade encryption</div></div>
            <div class="trust-item"><div class="trust-icon">🏆</div><div class="trust-title">Verified Winners</div><div class="trust-desc">Every winner is verified before item dispatch</div></div>
            <div class="trust-item"><div class="trust-icon">💬</div><div class="trust-title">24/7 Support</div><div class="trust-desc">Our team is available round-the-clock for assistance</div></div>
          </div>
        </div>

        <!-- FOOTER -->
        <footer>
          <div class="footer-top">
            <div>
              <div class="footer-logo"><img src="/logo.png" alt="CarryGo"></div>
              <div class="footer-tagline">Redefining the digital auction experience through transparency, curation, and elite service. Your gateway to exclusive opportunities.</div>
              <div class="social-row"><div class="social-btn">📸</div><div class="social-btn">👥</div><div class="social-btn">🐦</div><div class="social-btn">▶️</div></div>
            </div>
            <div class="footer-col"><h4>Auctions</h4><Link :href="trending.url()">Trending Now</Link><Link :href="openBidsRoute.url()">Open Bids</Link><Link href="#">Event Items</Link><Link href="#">Leaderboard</Link><Link href="#">How to Play</Link></div>
            <div class="footer-col"><h4>Account &amp; Support</h4><Link href="#">My Profile</Link><Link href="#">Task Center</Link><Link :href="history.url()">Winning History</Link><Link href="#">Privacy Policy</Link><Link href="#">Terms of Service</Link><Link href="#">Contact Support</Link></div>
          </div>
          <div class="footer-bottom">
            <span>© 2026 CarryGo Executive. All rights reserved.</span>
            <div class="footer-bottom-links"><Link href="#">Cookie Settings</Link><Link href="#">Security</Link><Link href="#">Privacy</Link></div>
          </div>
        </footer>
    </div>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&family=Barlow+Condensed:wght@700;800;900&display=swap');

.home-wrapper {
  --primary: #1a472a;
  --primary-dark: #0f2d19;
  --lemon: #c8e000;
  --yellow: #f5e642;
  --green: #1a472a;
  --navy: #0d1b2a;
  --black: #0a0a0a;
  --bg: #f4f7f0;
  --card: #fff;
  --text: #0a0a0a;
  --muted: #3a4a30;
  --border: #c5d9b0;
  --radius: 10px;
  --shadow: 0 2px 10px rgba(0,0,0,.1);
  font-family: 'Nunito', sans-serif;
  background: var(--bg);
  color: var(--text);
  overflow-x: hidden;
  text-align: left;
}

/* PRODUCT STRIP */
.product-strip{max-width:1300px;margin:10px auto 0;padding:0 16px;display:grid;grid-template-columns:repeat(4,1fr);gap:10px;}
.pcard{background:#fff;border-radius:10px;border:1.5px solid #dde8cc;overflow:hidden;cursor:pointer;transition:.2s;display:flex;flex-direction:column}
.pcard:hover{border-color:#c8e000;transform:translateY(-2px)}
.pcard-img{height:148px;position:relative;overflow:hidden;background:#eef5e0}
.pcard-img img{width:100%;height:100%;object-fit:cover;display:block}
.pcard-live{position:absolute;top:7px;left:7px;background:#1a472a;color:#c8e000;font-size:9px;font-weight:800;padding:3px 8px;border-radius:4px;letter-spacing:.4px;animation:pulse 2s ease infinite}
.pcard-pct{position:absolute;top:7px;right:7px;background:#c8e000;color:#0d1b2a;font-size:9px;font-weight:800;padding:3px 8px;border-radius:4px}
.pcard-body{padding:10px 12px 12px;flex:1;display:flex;flex-direction:column}
.pcard-name{font-size:12px;font-weight:800;color:#0a0a0a;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;margin-bottom:4px}
.pcard-price{font-size:17px;font-weight:900;color:#1a472a;margin-bottom:6px}
.pcard-bar-wrap{height:4px;background:#e8f0d8;border-radius:3px;overflow:hidden;margin-bottom:6px}
.pcard-bar-fill{height:100%;background:linear-gradient(90deg,#1a472a,#c8e000);border-radius:3px}
.pcard-pts{font-size:10px;color:#5a7a4a;margin-bottom:10px}
.pcard-btn{width:100%;background:#0d1b2a;color:#c8e000;border:none;padding:8px;border-radius:6px;font-size:12px;font-weight:800;cursor:pointer;font-family:inherit;margin-top:auto}
.pcard-btn:hover{background:#1a472a}
@keyframes pulse{0%,100%{opacity:1}50%{opacity:.7}}

/* TICKER */
.ticker{background:var(--navy);border-top:2px solid var(--lemon);border-bottom:2px solid var(--lemon);padding:8px 0;overflow:hidden;margin:12px 0}
.ticker-inner{display:flex;align-items:center;white-space:nowrap;animation:marquee 35s linear infinite}
.ticker-item{display:inline-flex;align-items:center;gap:8px;padding:0 30px;font-size:13px;font-weight:700;color:var(--lemon)}
.ticker-item .dot{width:7px;height:7px;border-radius:50%;background:var(--lemon);display:inline-block}
.ticker-item .price{color:#fff;font-weight:800}
.ticker-live{background:var(--green);color:var(--lemon);font-size:11px;font-weight:800;padding:2px 7px;border-radius:3px;margin-left:4px}
.section{max-width:1300px;margin:0 auto 20px;padding:0 16px}
.sec-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:14px}
.sec-title{font-family:'Barlow Condensed',sans-serif;font-size:22px;font-weight:800;color:var(--text)}
.sec-title::before{content:'';display:inline-block;width:4px;height:22px;background:var(--green);border-radius:3px;margin-right:8px;vertical-align:middle}
.view-all{color:var(--green);font-size:13px;font-weight:700;text-decoration:none}
.view-all:hover{text-decoration:underline}
.cats{display:grid;grid-template-columns:repeat(8,1fr);gap:10px}
.cat-card{background:#fff;border-radius:10px;padding:14px 8px;text-align:center;cursor:pointer;border:2px solid transparent;transition:.2s;box-shadow:var(--shadow)}
.cat-card:hover{border-color:var(--lemon);background:var(--navy);transform:translateY(-2px)}
.cat-card:hover .cat-name{color:var(--lemon)}
.cat-icon{font-size:28px;margin-bottom:6px}
.cat-name{font-size:11px;font-weight:800;color:var(--text)}

/* ITEM CARD (Trending/Open Bids) */
.item-card{flex:0 0 220px;background:#fff;border-radius:var(--radius);overflow:hidden;box-shadow:var(--shadow);border:1.5px solid var(--border);cursor:pointer;transition:.2s}
.item-card:hover{box-shadow:0 6px 20px rgba(26,71,42,.25);transform:translateY(-3px);border-color:var(--lemon)}
.item-img{position:relative;height:180px;background:#eef5e0;overflow:hidden}
.item-img img{width:100%;height:100%;object-fit:cover}
.live-badge{position:absolute;top:8px;left:8px;background:var(--green);color:var(--lemon);font-size:10px;font-weight:800;padding:3px 8px;border-radius:4px;letter-spacing:.5px;animation:pulse 2s ease infinite}
.item-body{padding:12px}
.item-name{font-size:13px;font-weight:800;color:var(--text);margin-bottom:4px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.item-price{font-size:18px;font-weight:900;color:var(--green);margin-bottom:4px}
.progress-wrap{margin:4px 0}
.progress-info{display:flex;justify-content:space-between;font-size:10px;color:var(--muted);margin-bottom:3px}
.progress-bar{height:5px;background:#e8f0d8;border-radius:3px;overflow:hidden}
.progress-fill{height:100%;background:linear-gradient(90deg,var(--green),var(--lemon));border-radius:3px;transition:width 1s}
.btn-bid{width:100%;background:var(--navy);color:var(--lemon);border:none;padding:9px;border-radius:7px;font-size:13px;font-weight:800;cursor:pointer;margin-top:8px;font-family:inherit}
.btn-bid:hover{background:var(--green)}

/* BANNERS */
.banners{display:grid;grid-template-columns:1fr 1fr;gap:14px}
.banner{border-radius:12px;padding:24px 28px;color:#fff;display:flex;align-items:center;justify-content:space-between;overflow:hidden;position:relative}
.banner.a{background:linear-gradient(120deg,var(--navy),#1a472a)}
.banner.b{background:linear-gradient(120deg,#0a0a0a,#1a472a)}
.banner-label{background:rgba(200,224,0,.25);color:var(--lemon);font-size:10px;font-weight:800;padding:3px 10px;border-radius:12px;display:inline-block;margin-bottom:8px;text-transform:uppercase;letter-spacing:.5px}
.banner-title{font-family:'Barlow Condensed',sans-serif;font-size:28px;font-weight:900;line-height:1.1;margin-bottom:6px;color:#fff}
.banner-desc{font-size:12px;opacity:.85;margin-bottom:14px;line-height:1.4;color:#cde}
.btn-banner{background:var(--lemon);color:var(--navy);padding:9px 18px;border-radius:7px;font-size:13px;font-weight:800;border:none;cursor:pointer;font-family:inherit}
.btn-banner:hover{background:var(--yellow)}
.banner-art{font-size:90px;opacity:.18;font-weight:900;line-height:1}

/* TESTIMONIALS */
.testi-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:14px}
.testi-card{background:#fff;border-radius:var(--radius);padding:18px;box-shadow:var(--shadow);border:1px solid var(--border)}
.stars{color:var(--yellow);font-size:14px;margin-bottom:10px}
.testi-text{font-size:13px;color:#333;line-height:1.6;margin-bottom:14px;font-style:italic}
.testi-user{display:flex;align-items:center;gap:10px}
.testi-avatar{width:36px;height:36px;border-radius:50%;background:var(--lemon);display:flex;align-items:center;justify-content:center;font-size:16px}
.testi-name{font-size:13px;font-weight:800;color:var(--text)}
.testi-role{font-size:11px;color:var(--muted)}
.testi-item-tag{margin-top:10px;background:#e8f5e0;color:var(--green);font-size:11px;font-weight:700;padding:4px 10px;border-radius:5px;display:inline-block}

/* TRUST BAR */
.trust-bar{background:var(--navy);border-top:2px solid var(--lemon);border-bottom:2px solid var(--lemon);padding:18px 16px;margin-bottom:20px}
.trust-inner{max-width:1300px;margin:auto;display:grid;grid-template-columns:repeat(4,1fr);gap:20px;text-align:center}
.trust-icon{font-size:26px;margin-bottom:6px}
.trust-title{font-size:13px;font-weight:800;color:var(--lemon)}
.trust-desc{font-size:11px;color:#8aaa80}

/* FOOTER */
footer{background:#050e05;color:#8aaa80;margin-top:30px}
.footer-top{max-width:1300px;margin:auto;padding:40px 16px 24px;display:grid;grid-template-columns:1.5fr 1fr 1fr 1.5fr;gap:32px}
.footer-logo img{height:48px;width:auto;margin-bottom:8px}
.footer-tagline{font-size:13px;color:#4a6a40;line-height:1.6;margin-bottom:14px}
.social-row{display:flex;gap:10px}
.social-btn{width:36px;height:36px;border-radius:50%;background:rgba(200,224,0,.1);display:flex;align-items:center;justify-content:center;font-size:15px;cursor:pointer;border:1px solid rgba(200,224,0,.2)}
.social-btn:hover{background:var(--green)}
.footer-col h4{font-size:13px;font-weight:800;color:var(--lemon);margin-bottom:14px;text-transform:uppercase;letter-spacing:.5px}
.footer-col a{display:block;color:#4a6a40;font-size:13px;margin-bottom:8px;text-decoration:none}
.footer-col a:hover{color:var(--lemon)}
.footer-bottom{border-top:1px solid #0f2010;padding:16px;max-width:1300px;margin:auto;display:flex;justify-content:space-between;align-items:center;font-size:12px;color:#2a4030}
.footer-bottom-links{display:flex;gap:16px}
.footer-bottom-links a{color:#2a4030;text-decoration:none}
.footer-bottom-links a:hover{color:var(--lemon)}

/* MOBILE RESPONSIVE */
@media(max-width:1100px){
.hero-wrap{grid-template-columns:180px 1fr}
.hero-h1{font-size:40px}
.hero-banner-right{width:240px}
.hero-banner-right img{width:220px;height:220px}
}
@media(max-width:900px){
.hero-wrap{grid-template-columns:1fr}
.hero-cats-nav{display:none}
.product-strip{grid-template-columns:repeat(2,1fr)}
}
@media(max-width:600px){
.hero-wrap{grid-template-columns:1fr}
.hero-cats-nav{display:none}
.hero-h1{font-size:36px}
.hero-banner-right{display:none}
.product-strip{grid-template-columns:1fr 1fr}
}
@media(max-width:1024px){
.cats{grid-template-columns:repeat(4,1fr)}
.footer-top{grid-template-columns:1fr 1fr}
.banners{grid-template-columns:1fr}
.testi-grid{grid-template-columns:1fr 1fr}
.trust-inner{grid-template-columns:repeat(2,1fr)}
}
@media(max-width:680px){
.nav-inner{flex-wrap:wrap;gap:8px;overflow:hidden}
.search-bar{order:3;width:100%;max-width:100%;box-sizing:border-box;padding:0 12px; margin-top: 10px;}
.search-bar input{min-width:0;width:100%}
.cats{grid-template-columns:repeat(4,1fr)}
.testi-grid{grid-template-columns:1fr}
.footer-top{grid-template-columns:1fr}
.trust-inner{grid-template-columns:1fr 1fr}
.topbar-links{display:none}
.item-card{flex:0 0 180px}
}
@media(max-width:420px){
.cats{grid-template-columns:repeat(3,1fr)}
}
</style>
