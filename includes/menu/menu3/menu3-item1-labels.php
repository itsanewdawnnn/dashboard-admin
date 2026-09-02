<?php
// Key teknis (item1..item10, unit1..unit3) yang disimpan di database, bukan labelnya
// -- jadi labelnya bisa diganti kapan saja di sini tanpa memengaruhi data tersimpan.
$checklistItemLabels = [
    'item1'  => 'Item 1',
    'item2'  => 'Item 2',
    'item3'  => 'Item 3',
    'item4'  => 'Item 4',
    'item5'  => 'Item 5',
    'item6'  => 'Item 6',
    'item7'  => 'Item 7',
    'item8'  => 'Item 8',
    'item9'  => 'Item 9',
    'item10' => 'Item 10',
];

$unitLabels = [
    'unit1' => 'Unit 1',
    'unit2' => 'Unit 2',
    'unit3' => 'Unit 3',
];

// Kalau $unitKey tidak dikenal, tampilkan key aslinya (fallback aman), dipakai
// di tabel, modal Lihat, Export Excel, Log Aktivitas, dan Pencarian Global.
function menu3UnitLabel(string $unitKey, array $unitLabels): string
{
    if (isset($unitLabels[$unitKey])) {
        return $unitLabels[$unitKey];
    }

    return $unitKey;
}
