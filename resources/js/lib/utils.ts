import { clsx } from 'clsx';
import type { ClassValue } from 'clsx';
import { twMerge } from 'tailwind-merge';
import type { Bid } from '@/pages/Home.vue';

export function cn(...inputs: ClassValue[]) {
    return twMerge(clsx(inputs));
}

export function formatPrice(price: string | number): string {
    if (!price && price !== 0) {
        return '₦ 0';
    }

    const cleanPrice =
        typeof price === 'string' ? price.replace(/,/g, '') : price;
    const num = parseFloat(cleanPrice.toString());

    return isNaN(num) ? price.toString() : `₦ ${num.toLocaleString()}`;
}

export function calcProgress(bid: Bid): number {
    if (!bid.open_points) {
        return 0;
    }

    const progress = ((bid.bid_entry_points || 0) / bid.open_points) * 100;

    return Math.round(Math.min(100, progress));
}

export function formatDate(dateStr: string): string {
    const date = new Date(dateStr);

    return date.toLocaleDateString('en-GB', { day: 'numeric', month: 'short' });
}
