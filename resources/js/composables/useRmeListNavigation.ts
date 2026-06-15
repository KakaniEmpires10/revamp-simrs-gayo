type SumberRme = 'rawat-jalan' | 'rawat-inap' | 'igd' | 'rujukan-internal' | string;

const storagePrefix = 'rme:list-url:';

const fallbackPaths: Record<string, string> = {
    'rawat-jalan': '/rme/rawat-jalan',
    'rawat-inap': '/rme/rawat-inap',
    igd: '/rme/igd',
    'rujukan-internal': '/rme/rujukan-internal',
};

const routeKeys: Record<string, string> = {
    'rawat-jalan': 'rawat-jalan',
    'rawat-inap': 'rawat-inap',
    igd: 'igd',
    'rujukan-internal': 'rujukan-internal',
};

function storageKey(source: SumberRme): string {
    return `${storagePrefix}${routeKeys[source] ?? 'rawat-jalan'}`;
}

export function simpanUrlListRme(source: SumberRme, url: string = window.location.pathname + window.location.search): void {
    localStorage.setItem(storageKey(source), url);
}

export function ambilUrlListRme(source: SumberRme): string {
    return localStorage.getItem(storageKey(source)) ?? fallbackPaths[source] ?? fallbackPaths['rawat-jalan'];
}
