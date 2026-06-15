type BpjsMetadata = {
    code?: string | null;
    message?: string | null;
} | null | undefined;

type BpjsTableState = {
    description: string;
    icon: 'search' | 'error';
    severity: 'info' | 'destructive';
    title: string;
};

const businessEmptyPatterns = [
    'tidak ditemukan',
    'tidak ada',
    'data kosong',
    'not found',
    'no data',
    'belum ada',
];

function isBusinessEmpty(metadata: BpjsMetadata): boolean {
    const code = metadata?.code;
    const message = metadata?.message?.toLowerCase() ?? '';

    return code === '200' || businessEmptyPatterns.some((pattern) => message.includes(pattern));
}

export function statusTabelBpjs(
    metadata: BpjsMetadata,
    emptyTitle: string,
    errorTitle: string,
    fallbackDescription: string,
): BpjsTableState {
    const description = metadata?.message?.trim() || fallbackDescription;

    if (isBusinessEmpty(metadata)) {
        return {
            description,
            icon: 'search',
            severity: 'info',
            title: emptyTitle,
        };
    }

    return {
        description,
        icon: 'error',
        severity: 'destructive',
        title: errorTitle,
    };
}
