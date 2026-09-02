<?php
// Sumber data tunggal untuk semua menu & item menu (label, url, aktif/nonaktif,
// module_key untuk Log Aktivitas, konfigurasi Pencarian Global). Menambah Menu 4:
// tambah entri di getMenuConfig() (termasuk blok "search" kalau mau ikut Pencarian
// Global -- lihat function hasil pencariannya di menu4-item1-filter.php) + buat
// halaman PHP-nya dengan "$activeMenu = 'menu4-item1';" -- sidebar, breadcrumb,
// Pencarian Global, dan Log Aktivitas otomatis ikut, TANPA edit global-search.php.

function getMenuConfig(): array
{
    return [
        'menu1' => [
            'label' => 'Menu 1',
            'icon'  => 'bi-collection-fill',
            'items' => [
                'menu1-item1' => [
                    'label'      => 'Item 1',
                    'url'        => '/includes/menu/menu1/menu1-item1.php',
                    'enabled'    => true,
                    'module_key' => 'menu1_item1',
                    // 'columns': kolom yang dicari (LIKE, OR kalau lebih dari satu).
                    // 'result': nama fungsi (di menu1-item1-filter.php) yang mengubah 1 baris jadi ['title','subtitle'].
                    'search'     => [
                        'table'        => 'menu1_item1',
                        'columns'      => ['full_name'],
                        'order_column' => 'active_date',
                        'date_column'  => 'active_date',
                        'result'       => 'menu1Item1SearchResult',
                    ],
                ],
                'menu1-item2' => [
                    'label'      => 'Item 2',
                    'url'        => null,
                    'enabled'    => false,
                    'module_key' => null,
                ],
            ],
        ],
        'menu2' => [
            'label' => 'Menu 2',
            'icon'  => 'bi-collection-fill',
            'items' => [
                'menu2-item1' => [
                    'label'      => 'Item 1',
                    'url'        => '/includes/menu/menu2/menu2-item1.php',
                    'enabled'    => true,
                    'module_key' => 'menu2_item1',
                    'search'     => [
                        'table'        => 'menu2_item1',
                        'columns'      => ['job_description'],
                        'order_column' => 'work_date',
                        'date_column'  => 'work_date',
                        'result'       => 'menu2Item1SearchResult',
                    ],
                ],
                'menu2-item2' => [
                    'label'      => 'Item 2',
                    'url'        => null,
                    'enabled'    => false,
                    'module_key' => null,
                ],
            ],
        ],
        'menu3' => [
            'label' => 'Menu 3',
            'icon'  => 'bi-collection-fill',
            'items' => [
                'menu3-item1' => [
                    'label'      => 'Item 1',
                    'url'        => '/includes/menu/menu3/menu3-item1.php',
                    'enabled'    => true,
                    'module_key' => 'menu3_item1',
                    'search'     => [
                        'table'        => 'menu3_item1',
                        'columns'      => ['checker_name', 'laporan', 'tindak_lanjut'],
                        'order_column' => 'check_date',
                        'date_column'  => 'check_date',
                        'result'       => 'menu3Item1SearchResult',
                    ],
                ],
                'menu3-item2' => [
                    'label'      => 'Item 2',
                    'url'        => null,
                    'enabled'    => false,
                    'module_key' => null,
                ],
            ],
        ],
    ];
}

function menuGroupLabel(string $menuKey): string
{
    $menuConfig = getMenuConfig();

    if (isset($menuConfig[$menuKey])) {
        return $menuConfig[$menuKey]['label'];
    }

    return '';
}

function menuItemLabel(string $itemKey): string
{
    $menuConfig = getMenuConfig();
    $menuKey    = substr($itemKey, 0, (int) strpos($itemKey, '-'));

    if (isset($menuConfig[$menuKey]['items'][$itemKey])) {
        return $menuConfig[$menuKey]['items'][$itemKey]['label'];
    }

    return '';
}

// Dipakai includes/header.php untuk breadcrumb/judul halaman.
function menuBreadcrumbLabels(string $itemKey): array
{
    $groupLabel = '';
    $itemLabel  = '';

    $itemLabelFound = menuItemLabel($itemKey);
    if ($itemLabelFound !== '') {
        $menuKey    = substr($itemKey, 0, (int) strpos($itemKey, '-'));
        $groupLabel = menuGroupLabel($menuKey);
        $itemLabel  = $itemLabelFound;
    }

    return [$groupLabel, $itemLabel];
}

function getMenuGroupLabels(): array
{
    $menuConfig = getMenuConfig();

    $groupLabels = [];
    foreach ($menuConfig as $menuKey => $menu) {
        $groupLabels[$menuKey] = $menu['label'];
    }

    return $groupLabels;
}

// Dipakai menu2-item1.php & activity-log-helpers.php -- kalau folder upload
// Menu 2 dipindah, cukup ubah 2 fungsi ini saja.
function menu2UploadUrlBase(): string
{
    return '/includes/menu/menu2/uploads/';
}

function menu2SignatureUrlBase(): string
{
    return menu2UploadUrlBase() . 'signatures/';
}

// "menu1_item1" -> "Menu 1 - Item 1", dipakai activity-log-helpers.php.
function menuModuleLabel(string $moduleKey): string
{
    $menuConfig = getMenuConfig();

    foreach ($menuConfig as $menu) {
        foreach ($menu['items'] as $item) {
            if ($item['module_key'] === $moduleKey) {
                return $menu['label'] . ' - ' . $item['label'];
            }
        }
    }

    return '';
}
