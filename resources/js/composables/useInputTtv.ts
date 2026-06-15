export function sanitasiDesimalTtv(value: unknown, maxLength = 5): string {
    return String(value ?? '')
        .replace(',', '.')
        .replace(/[^0-9.]/g, '')
        .replace(/(\..*)\./g, '$1')
        .slice(0, maxLength);
}

export function sanitasiAngkaTtv(value: unknown, maxLength = 3): string {
    return String(value ?? '').replace(/\D/g, '').slice(0, maxLength);
}

export function sanitasiTensiTtv(value: unknown): string {
    const cleaned = String(value ?? '').replace(/[^\d/]/g, '').slice(0, 8);
    const [sistolik = '', diastolik = ''] = cleaned.split('/');

    return cleaned.includes('/') ? `${sistolik}/${diastolik}` : sistolik;
}

export function bolehInputDesimalTtv(event: InputEvent): void {
    if (event.data && !/^[0-9.,]+$/.test(event.data)) {
        event.preventDefault();
    }
}

export function bolehInputAngkaTtv(event: InputEvent): void {
    if (event.data && !/^\d+$/.test(event.data)) {
        event.preventDefault();
    }
}

export function bolehInputTensiTtv(event: InputEvent): void {
    if (event.data && !/^[0-9/]+$/.test(event.data)) {
        event.preventDefault();
    }
}
