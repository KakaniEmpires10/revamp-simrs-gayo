import type { PaginatedResponse, RegistrationOption } from './pendaftaran';

export type RmeOption = RegistrationOption;

export interface FilterKunjunganRme {
    tgl_awal: string;
    tgl_akhir: string;
    search: string;
    kd_poli?: string | null;
    kd_poli_asal?: string | null;
    kd_bangsal?: string | null;
    kd_pj?: string | null;
    status?: string | null;
    order?: 'asc' | 'desc' | string | null;
    tipe_filter_ranap?: 'belum_pulang' | 'tanggal_masuk' | 'tanggal_keluar' | string | null;
}

export interface PasienRmeDasar {
    no_rawat: string;
    tgl_registrasi: string;
    jam_reg: string;
    no_rkm_medis: string;
    kd_pj: string;
    stts: string;
    stts_daftar: string;
    status_lanjut: string;
    status_bayar: string;
    nm_pasien: string;
    jk: 'L' | 'P' | string;
    tgl_lahir: string | null;
    no_peserta: string | null;
    no_ktp: string | null;
    png_jawab: string;
    umur_registrasi: string | null;
}

export interface TtvPasienRme {
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

export type PasienDialogTtvRme = PasienRmeDasar & Partial<{
    no_reg: string;
    kd_dokter: string;
    kd_poli: string;
    nm_dokter: string;
    nm_poli: string;
    kd_kamar: string;
    nm_bangsal: string;
    kd_poli_tujuan: string;
    nm_poli_tujuan: string;
    nm_dokter_tujuan: string;
}>;

export interface PasienRalanRme extends PasienRmeDasar {
    no_reg: string;
    kd_dokter: string;
    kd_poli: string;
    nm_dokter: string;
    nm_poli: string;
    no_tlp: string | null;
    no_sep: string | null;
    tglsep: string | null;
    klsrawat: '1' | '2' | '3' | string | null;
    diagawal: string | null;
    jam_sep: string | null;
    nmdiagnosaawal: string | null;
    kd_penyakit_igd: string | null;
    nm_penyakit_igd: string | null;
    ada_sep: 0 | 1 | boolean;
    ada_cppt: 0 | 1 | boolean;
    sudah_diperiksa_dokter: 0 | 1 | boolean;
    sudah_diperiksa_perawat: 0 | 1 | boolean;
    ada_lab: 0 | 1 | boolean;
    ada_radiologi: 0 | 1 | boolean;
    ada_fisioterapi: 0 | 1 | boolean;
    ada_resep: 0 | 1 | boolean;
    ada_tindakan: 0 | 1 | boolean;
    ada_resume: 0 | 1 | boolean;
    is_ranap: 0 | 1 | boolean;
}

export interface PasienRanapRme extends PasienRmeDasar {
    kd_dokter: string;
    nm_dokter: string;
    kd_kamar: string;
    trf_kamar: number | string | null;
    diagnosa_awal: string | null;
    diagnosa_akhir: string | null;
    tgl_masuk: string;
    jam_masuk: string;
    tgl_keluar: string | null;
    jam_keluar: string | null;
    lama: number | string | null;
    ttl_biaya: number | string | null;
    stts_pulang: string;
    kelas: string;
    kd_bangsal: string;
    nm_bangsal: string;
    no_sep: string | null;
    tglsep: string | null;
    jam_sep: string | null;
    diagawal: string | null;
    nmdiagnosaawal: string | null;
    ada_sep: 0 | 1 | boolean;
    sudah_diperiksa_dokter: 0 | 1 | boolean;
    sudah_diperiksa_perawat: 0 | 1 | boolean;
    dokter_pj_ranap: string | null;
}

export interface PasienRujukanInternalRme extends PasienRmeDasar {
    nm_dokter_awal: string;
    kd_poli_awal: string;
    nm_poli_awal: string;
    kd_dokter_tujuan: string;
    nm_dokter_tujuan: string;
    kd_poli_tujuan: string;
    nm_poli_tujuan: string;
    sudah_diperiksa_dokter: 0 | 1 | boolean;
    sudah_diperiksa_perawat: 0 | 1 | boolean;
    is_ranap: 0 | 1 | boolean;
}

export interface PemeriksaanMenu {
    key: string;
    label: string;
    description: string;
    icon: string;
    href: string;
    children?: PemeriksaanMenu[];
}

export interface KonteksPasienPemeriksaan {
    no_rawat: string;
    no_reg: string;
    no_rkm_medis: string;
    tgl_registrasi: string;
    jam_reg: string;
    nm_pasien: string;
    jk: string;
    tgl_lahir: string | null;
    no_ktp: string | null;
    no_peserta: string | null;
    kd_dokter: string;
    nm_dokter: string;
    kd_poli: string;
    nm_poli: string;
    kd_pj: string;
    png_jawab: string;
    stts: string;
    stts_daftar: string;
    status_lanjut: string;
    kelas: string | null;
    kelas_sep: string | null;
    kd_kamar: string | null;
    nm_bangsal: string | null;
    no_sep: string | null;
    umur_registrasi: string | null;
    asal: string;
    type_akses: string;
}

export interface DefaultFormCppt {
    no_rawat: string;
    tgl_perawatan: string;
    jam_rawat: string;
    nip: string;
    nama_pengisi: string;
    jenis_pengisi: string;
    kesadaran: string;
}

export interface RiwayatCppt {
    no_rawat: string;
    tgl_perawatan: string;
    jam_rawat: string;
    suhu_tubuh: string | null;
    tensi: string | null;
    nadi: string | null;
    respirasi: string | null;
    tinggi: string | null;
    berat: string | null;
    spo2: string | null;
    gcs: string | null;
    kesadaran: string;
    keluhan: string | null;
    pemeriksaan: string | null;
    alergi: string | null;
    rtl: string | null;
    penilaian: string | null;
    instruksi: string | null;
    evaluasi: string | null;
    nip: string;
    nama_pengisi: string;
    asal_layanan: string;
    sumber: 'pemeriksaan_ralan' | 'pemeriksaan_ranap' | string;
    can_view: boolean;
    can_edit: boolean;
    can_delete: boolean;
}

export interface FilterRiwayatCppt {
    scope: 'kunjungan' | 'rm';
    tgl_awal: string | null;
    tgl_akhir: string | null;
}

export interface RiwayatPasienKunjungan {
    no_rawat: string;
    no_reg: string;
    tgl_registrasi: string;
    jam_reg: string;
    no_rkm_medis: string;
    nm_pasien: string;
    nm_dokter: string;
    kd_poli: string;
    nm_poli: string;
    kd_pj: string;
    png_jawab: string;
    stts: string;
    stts_daftar: string;
    status_lanjut: string;
    no_sep: string | null;
    diagnosa_sep: string | null;
    diagnosa_pasien: string | null;
    jumlah_cppt: number;
    jumlah_lab: number;
    jumlah_radiologi: number;
    jumlah_resep: number;
}

export type PaginatedRme<T> = PaginatedResponse<T>;
