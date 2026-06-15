export type RegistrationType = 'rawat_jalan' | 'igd';

export type PatientSearchMode = 'nik' | 'no_peserta' | 'no_rm';

export interface RegistrationOption {
    value: string;
    label: string;
    description?: string;
}

export interface RegistrationPatient {
    no_rkm_medis: string;
    nm_pasien: string;
    no_ktp: string | null;
    no_peserta: string | null;
    jk: 'L' | 'P' | string;
    tmp_lahir?: string | null;
    tgl_lahir: string | null;
    nm_ibu?: string | null;
    umur?: string | null;
    alamat: string | null;
    no_tlp: string | null;
    namakeluarga?: string | null;
    keluarga?: string | null;
    alamatpj?: string | null;
    kelurahanpj?: string | null;
    kecamatanpj?: string | null;
    kabupatenpj?: string | null;
    kd_pj?: string | null;
    png_jawab?: string | null;
    gol_darah?: string | null;
    pekerjaan?: string | null;
    stts_nikah?: string | null;
    agama?: string | null;
    pnd?: string | null;
    kd_kel?: number | null;
    kd_kec?: number | null;
    kd_kab?: number | null;
    kd_prop?: number | null;
    pekerjaanpj?: string | null;
    perusahaan_pasien?: string | null;
    suku_bangsa?: number | null;
    bahasa_pasien?: number | null;
    cacat_fisik?: number | null;
    email?: string | null;
}

export interface RegisteredPatientRow {
    no_reg: string;
    no_rawat: string;
    tgl_registrasi: string;
    jam_reg: string;
    kd_dokter: string;
    no_rkm_medis: string;
    kd_poli: string;
    nm_poli: string;
    p_jawab: string;
    almt_pj: string;
    hubunganpj: string;
    stts: string;
    stts_daftar: string;
    status_lanjut: 'Ralan' | 'Ranap' | string;
    status_bayar: string;
    kd_pj: string;
    nm_pasien: string;
    jk: 'L' | 'P' | string;
    no_tlp: string | null;
    no_peserta: string | null;
    no_ktp: string | null;
    tgl_lahir: string | null;
    png_jawab: string;
    nm_dokter: string;
    perujuk: string | null;
    kategori_rujuk: string | null;
    no_sep: string | null;
    tgl_sep: string | null;
    klsrawat: '1' | '2' | '3' | string | null;
    diagawal: string | null;
    nmdiagnosaawal: string | null;
    diagnosa_ranap_awal: string | null;
    tgl_masuk_ranap: string | null;
    is_ranap: boolean;
    is_mjkn: boolean;
}

export interface RegistrationTableFilters {
    view: 'registration' | 'table';
    jenis_pendaftaran: RegistrationType;
    tgl_awal: string;
    tgl_akhir: string;
    kd_poli: string | null;
    search: string;
}

export interface PaginatedLink {
    url: string | null;
    label: string;
    active: boolean;
}

export interface PaginatedResponse<T> {
    data: T[];
    links: PaginatedLink[];
    current_page: number;
    last_page: number;
    from: number | null;
    to: number | null;
    total: number;
}
