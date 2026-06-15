<?php

return [
    'menu' => [
        [
            'key' => 'cppt',
            'label' => 'CPPT',
            'description' => 'Catatan perkembangan pasien terintegrasi.',
            'icon' => 'ClipboardPenLine',
            'route' => 'rme.pemeriksaan.cppt',
        ],
        [
            'key' => 'tindakan',
            'label' => 'Tindakan',
            'description' => 'Tindakan dokter dan petugas.',
            'icon' => 'Stethoscope',
            'route' => 'rme.pemeriksaan.placeholder',
        ],
        [
            'key' => 'resep',
            'label' => 'Resep',
            'description' => 'Peresepan dan terapi obat.',
            'icon' => 'Pill',
            'route' => 'rme.pemeriksaan.placeholder',
            'children' => [
                [
                    'key' => 'resep',
                    'label' => 'Resep',
                    'description' => 'Resep non-racikan.',
                    'icon' => 'Pill',
                    'route' => 'rme.pemeriksaan.placeholder',
                ],
                [
                    'key' => 'resep-racikan',
                    'label' => 'Resep Racikan',
                    'description' => 'Resep obat racikan.',
                    'icon' => 'Pill',
                    'route' => 'rme.pemeriksaan.placeholder',
                ],
            ],
        ],
        [
            'key' => 'laboratorium',
            'label' => 'Laboratorium',
            'description' => 'Permintaan dan hasil laboratorium.',
            'icon' => 'FlaskConical',
            'route' => 'rme.pemeriksaan.placeholder',
            'children' => [
                [
                    'key' => 'lab-pk',
                    'label' => 'Lab PK',
                    'description' => 'Patologi klinis.',
                    'icon' => 'FlaskConical',
                    'route' => 'rme.pemeriksaan.placeholder',
                ],
                [
                    'key' => 'lab-pa',
                    'label' => 'Lab PA',
                    'description' => 'Patologi anatomi.',
                    'icon' => 'FlaskConical',
                    'route' => 'rme.pemeriksaan.placeholder',
                ],
            ],
        ],
        [
            'key' => 'radiologi',
            'label' => 'Radiologi',
            'description' => 'Permintaan dan hasil radiologi.',
            'icon' => 'ScanLine',
            'route' => 'rme.pemeriksaan.placeholder',
        ],
        [
            'key' => 'resume',
            'label' => 'Resume',
            'description' => 'Resume medis pasien.',
            'icon' => 'FileCheck2',
            'route' => 'rme.pemeriksaan.placeholder',
        ],
    ],
];
