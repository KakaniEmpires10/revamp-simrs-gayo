export type Appearance = 'light' | 'dark' | 'system';
export type ResolvedAppearance = 'light' | 'dark';

export type AppVariant = 'header' | 'sidebar';

export type FlashToast = {
    type: 'success' | 'info' | 'warning' | 'error';
    message: string;
};

export type Platform = 'desktop' | 'touch';

export type FeedbackMode = 'toast' | 'alert';
export type PemeriksaanNavigationMode = 'sidebar-tree' | 'top-tab';
