interface NilaiNormalPemeriksaan {
    suhu_tubuh: string;
    tensi: string;
    nadi: string;
    respirasi: string;
    spo2: string;
    tinggi: string;
    berat: string;
    gcs: string;
    kesadaran: string;
}

function usiaTahun(tanggalLahir: string | null | undefined, tanggalAcuan: string | null | undefined): number {
    if (!tanggalLahir) {
        return 30;
    }

    const lahir = new Date(tanggalLahir);
    const acuan = tanggalAcuan ? new Date(tanggalAcuan) : new Date();

    let usia = acuan.getFullYear() - lahir.getFullYear();
    const belumUlangTahun = acuan.getMonth() < lahir.getMonth()
        || (acuan.getMonth() === lahir.getMonth() && acuan.getDate() < lahir.getDate());

    if (belumUlangTahun) {
        usia -= 1;
    }

    return Math.max(0, usia);
}

export function nilaiNormalPemeriksaan(tanggalLahir: string | null | undefined, jenisKelamin: string | null | undefined, tanggalAcuan: string | null | undefined): NilaiNormalPemeriksaan {
    const usia = usiaTahun(tanggalLahir, tanggalAcuan);
    const lakiLaki = jenisKelamin === 'L';

    if (usia < 1) {
        return { suhu_tubuh: '36.5', tensi: '85/55', nadi: '120', respirasi: '30', spo2: '98', tinggi: '70', berat: '8', gcs: 'E4 V5 M6', kesadaran: 'Compos Mentis' };
    }

    if (usia < 5) {
        return { suhu_tubuh: '36.5', tensi: '95/60', nadi: '105', respirasi: '24', spo2: '98', tinggi: '100', berat: '16', gcs: 'E4 V5 M6', kesadaran: 'Compos Mentis' };
    }

    if (usia < 12) {
        return { suhu_tubuh: '36.5', tensi: '105/65', nadi: '90', respirasi: '22', spo2: '98', tinggi: '135', berat: '32', gcs: 'E4 V5 M6', kesadaran: 'Compos Mentis' };
    }

    if (usia < 18) {
        return { suhu_tubuh: '36.5', tensi: '110/70', nadi: '82', respirasi: '18', spo2: '98', tinggi: lakiLaki ? '165' : '155', berat: lakiLaki ? '55' : '48', gcs: 'E4 V5 M6', kesadaran: 'Compos Mentis' };
    }

    if (usia >= 60) {
        return { suhu_tubuh: '36.5', tensi: '130/80', nadi: '76', respirasi: '18', spo2: '98', tinggi: lakiLaki ? '165' : '155', berat: lakiLaki ? '62' : '54', gcs: 'E4 V5 M6', kesadaran: 'Compos Mentis' };
    }

    return { suhu_tubuh: '36.5', tensi: '120/80', nadi: '80', respirasi: '18', spo2: '98', tinggi: lakiLaki ? '170' : '158', berat: lakiLaki ? '65' : '55', gcs: 'E4 V5 M6', kesadaran: 'Compos Mentis' };
}
