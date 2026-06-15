export type DoctorAccount = {
    kd_dokter: string;
    nm_dokter: string;
    jk: 'L' | 'P' | null;
    tmp_lahir: string | null;
    tgl_lahir: string | null;
    gol_drh: string | null;
    agama: string | null;
    almt_tgl: string | null;
    no_telp: string | null;
    stts_nikah: string | null;
    kd_sps: string | null;
    alumni: string | null;
    no_nip: string | null;
    no_ijn_praktek: string | null;
    status: '0' | '1';
    no_ktp: string | null;
    nm_sps: string | null;
    password_decrypted: string | null;
};

export type StaffAccount = {
    nip: string;
    nama: string;
    jk: 'L' | 'P' | null;
    tmp_lahir: string | null;
    tgl_lahir: string | null;
    gol_darah: string | null;
    agama: string | null;
    stts_nikah: string | null;
    alamat: string | null;
    kd_jbtn: string | null;
    no_telp: string | null;
    status: '0' | '1';
    no_ktp: string | null;
    nm_jbtn: string | null;
    password_decrypted: string | null;
};

export type PracticeSchedule = {
    kd_dokter: string;
    nm_dokter: string;
    hari_kerja: string;
    jam_mulai: string;
    jam_selesai: string | null;
    kd_poli: string | null;
    nm_poli: string | null;
    kuota: number | null;
};

export type DoctorOption = {
    kd_dokter: string;
    nm_dokter: string;
};

export type ClinicOption = {
    kd_poli: string;
    nm_poli: string;
};

export type SpecialistOption = {
    kd_sps: string;
    nm_sps: string;
};

export type PositionOption = {
    kd_jbtn: string;
    nm_jbtn: string;
};
