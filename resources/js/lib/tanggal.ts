import {
    getLocalTimeZone,
    now,
    parseDate,
    today
} from '@internationalized/date';
import type {CalendarDate} from '@internationalized/date';

const LOCALE_INDONESIA: Intl.LocalesArgument = ['id-ID', 'en-US'];

function tanggalKeCalendarDate(value: string | null | undefined): CalendarDate | null {
    if (!value) {
        return null;
    }

    try {
        return parseDate(value);
    } catch {
        return null;
    }
}

function formatDate(date: CalendarDate, options: Intl.DateTimeFormatOptions): string {
    return new Intl.DateTimeFormat(LOCALE_INDONESIA, options).format(date.toDate(getLocalTimeZone()));
}

function pad(number: number): string {
    return String(number).padStart(2, '0');
}

export function tanggalHariIni(): string {
    return today(getLocalTimeZone()).toString();
}

export function waktuSekarang(): string {
    const current = now(getLocalTimeZone());

    return `${pad(current.hour)}:${pad(current.minute)}`;
}

export function formatTanggalIndonesia(value: string | null | undefined, options: Intl.DateTimeFormatOptions = { dateStyle: 'medium' }): string {
    const date = tanggalKeCalendarDate(value);

    return date ? formatDate(date, options) : '-';
}

export function labelJenisKelamin(jenisKelamin: string | null | undefined): string {
    if (jenisKelamin === 'L') {
        return 'Laki-laki';
    }

    if (jenisKelamin === 'P') {
        return 'Perempuan';
    }

    return jenisKelamin || '-';
}

export function hitungUmur(tanggalLahir: string | null | undefined, tanggalAcuan: string | null | undefined = tanggalHariIni()): string {
    const lahir = tanggalKeCalendarDate(tanggalLahir);
    const acuan = tanggalKeCalendarDate(tanggalAcuan);

    if (!lahir || !acuan || lahir.compare(acuan) > 0) {
        return '-';
    }

    let tahun = acuan.year - lahir.year;
    let bulan = acuan.month - lahir.month;
    let hari = acuan.day - lahir.day;

    if (hari < 0) {
        bulan -= 1;
        const bulanSebelumnya = acuan.subtract({ months: 1 });
        hari += bulanSebelumnya.calendar.getDaysInMonth(bulanSebelumnya);
    }

    if (bulan < 0) {
        tahun -= 1;
        bulan += 12;
    }

    if (tahun > 0) {
        return `${tahun} th`;
    }

    if (bulan > 0) {
        return `${bulan} bl`;
    }

    return `${hari} hr`;
}
