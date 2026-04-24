import { clsx } from 'clsx';
import type { ClassValue } from 'clsx';
import { twMerge } from 'tailwind-merge';

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
