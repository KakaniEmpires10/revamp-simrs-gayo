import type { BadgeVariants } from '@/components/ui/badge';

export type BpjsSubmenuItem = {
    title: string;
    description: string;
    href: string;
    icon: string;
    badge: string;
    badgeVariant: BadgeVariants['variant'];
};

export type BpjsSubmenuGroup = {
    title: string;
    items: BpjsSubmenuItem[];
};
