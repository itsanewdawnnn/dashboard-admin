<?php
// Kerangka halaman bersama. Semua tautan pakai path absolut (diawali "/").

$activeMenu = $activeMenu ?? '';

require_once __DIR__ . '/helpers/dashboard-helpers.php';
require_once __DIR__ . '/menu/menu3/menu3-item1-labels.php';

// $notificationItems generik (icon/judul/sub-judul/link) -- jenis notifikasi baru
// tinggal tambah builder lain lalu array_merge(), markup dropdown tidak perlu diubah.
$pdo                   = getDbConnection();
$activeKendalaRecords  = fetchActiveKendalaRecords($pdo);
$notificationItems     = buildKendalaNotificationItems($activeKendalaRecords, $unitLabels);
$notificationCount     = count($notificationItems);

require_once __DIR__ . '/menu-config.php';
$menuConfig = getMenuConfig();

list($activeMenuLabel, $activeItemLabel) = menuBreadcrumbLabels($activeMenu);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mazer Admin Dashboard</title>
    <link rel="shortcut icon" href="data:image/svg+xml,%3csvg%20xmlns='http://www.w3.org/2000/svg'%20viewBox='0%200%2033%2034'%20fill-rule='evenodd'%20stroke-linejoin='round'%20stroke-miterlimit='2'%20xmlns:v='https://vecta.io/nano'%3e%3cpath%20d='M3%2027.472c0%204.409%206.18%205.552%2013.5%205.552%207.281%200%2013.5-1.103%2013.5-5.513s-6.179-5.552-13.5-5.552c-7.281%200-13.5%201.103-13.5%205.513z'%20fill='%23435ebe'%20fill-rule='nonzero'/%3e%3ccircle%20cx='16.5'%20cy='8.8'%20r='8.8'%20fill='%2341bbdd'/%3e%3c/svg%3e" type="image/x-icon">
    <link rel="shortcut icon" href="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACEAAAAiCAYAAADRcLDBAAAEs2lUWHRYTUw6Y29tLmFkb2JlLnhtcAAAAAAAPD94cGFja2V0IGJlZ2luPSLvu78iIGlkPSJXNU0wTXBDZWhpSHpyZVN6TlRjemtjOWQiPz4KPHg6eG1wbWV0YSB4bWxuczp4PSJhZG9iZTpuczptZXRhLyIgeDp4bXB0az0iWE1QIENvcmUgNS41LjAiPgogPHJkZjpSREYgeG1sbnM6cmRmPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5LzAyLzIyLXJkZi1zeW50YXgtbnMjIj4KICA8cmRmOkRlc2NyaXB0aW9uIHJkZjphYm91dD0iIgogICAgeG1sbnM6ZXhpZj0iaHR0cDovL25zLmFkb2JlLmNvbS9leGlmLzEuMC8iCiAgICB4bWxuczp0aWZmPSJodHRwOi8vbnMuYWRvYmUuY29tL3RpZmYvMS4wLyIKICAgIHhtbG5zOnBob3Rvc2hvcD0iaHR0cDovL25zLmFkb2JlLmNvbS9waG90b3Nob3AvMS4wLyIKICAgIHhtbG5zOnhtcD0iaHR0cDovL25zLmFkb2JlLmNvbS94YXAvMS4wLyIKICAgIHhtbG5zOnhtcE1NPSJodHRwOi8vbnMuYWRvYmUuY29tL3hhcC8xLjAvbW0vIgogICAgeG1sbnM6c3RFdnQ9Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC9zVHlwZS9SZXNvdXJjZUV2ZW50IyIKICAgZXhpZjpQaXhlbFhEaW1lbnNpb249IjMzIgogICBleGlmOlBpeGVsWURpbWVuc2lvbj0iMzQiCiAgIGV4aWY6Q29sb3JTcGFjZT0iMSIKICAgdGlmZjpJbWFnZVdpZHRoPSIzMyIKICAgdGlmZjpJbWFnZUxlbmd0aD0iMzQiCiAgIHRpZmY6UmVzb2x1dGlvblVuaXQ9IjIiCiAgIHRpZmY6WFJlc29sdXRpb249Ijk2LjAiCiAgIHRpZmY6WVJlc29sdXRpb249Ijk2LjAiCiAgIHBob3Rvc2hvcDpDb2xvck1vZGU9IjMiCiAgIHBob3Rvc2hvcDpJQ0NQcm9maWxlPSJzUkdCIElFQzYxOTY2LTIuMSIKICAgeG1wOk1vZGlmeURhdGU9IjIwMjItMDMtMzFUMTA6NTA6MjMrMDI6MDAiCiAgIHhtcDpNZXRhZGF0YURhdGU9IjIwMjItMDMtMzFUMTA6NTA6MjMrMDI6MDAiPgogICA8eG1wTU06SGlzdG9yeT4KICAgIDxyZGY6U2VxPgogICAgIDxyZGY6bGkKICAgICAgc3RFdnQ6YWN0aW9uPSJwcm9kdWNlZCIKICAgICAgc3RFdnQ6c29mdHdhcmVBZ2VudD0iQWZmaW5pdHkgRGVzaWduZXIgMS4xMC4xIgogICAgICBzdEV2dDp3aGVuPSIyMDIyLTAzLTMxVDEwOjUwOjIzKzAyOjAwIi8+CiAgICA8L3JkZjpTZXE+CiAgIDwveG1wTU06SGlzdG9yeT4KICA8L3JkZjpEZXNjcmlwdGlvbj4KIDwvcmRmOlJERj4KPC94OnhtcG1ldGE+Cjw/eHBhY2tldCBlbmQ9InIiPz5V57uAAAABgmlDQ1BzUkdCIElFQzYxOTY2LTIuMQAAKJF1kc8rRFEUxz9maORHo1hYKC9hISNGTWwsRn4VFmOUX5uZZ36oeTOv954kW2WrKLHxa8FfwFZZK0WkZClrYoOe87ypmWTO7dzzud97z+nec8ETzaiaWd4NWtYyIiNhZWZ2TvE946WZSjqoj6mmPjE1HKWkfdxR5sSbgFOr9Ll/rXoxYapQVik8oOqGJTwqPL5i6Q5vCzeo6dii8KlwpyEXFL519LjLLw6nXP5y2IhGBsFTJ6ykijhexGra0ITl5bRqmWU1fx/nJTWJ7PSUxBbxJkwijBBGYYwhBgnRQ7/MIQIE6ZIVJfK7f/MnyUmuKrPOKgZLpEhj0SnqslRPSEyKnpCRYdXp/9++msneoFu9JgwVT7b91ga+LfjetO3PQ9v+PgLvI1xkC/m5A+h7F32zoLXug38dzi4LWnwHzjeg8UGPGbFfySvuSSbh9QRqZ6H+Gqrm3Z7l9zm+h+iafNUV7O5Bu5z3L/wAdthn7QIme0YAAAAJcEhZcwAADsQAAA7EAZUrDhsAAAJTSURBVFiF7Zi9axRBGIefEw2IdxFBRQsLWUTBaywSK4ubdSGVIY1Y6HZql8ZKCGIqwX/AYLmCgVQKfiDn7jZeEQMWfsSAHAiKqPiB5mIgELWYOW5vzc3O7niHhT/YZvY37/swM/vOzJbIqVq9uQ04CYwCI8AhYAlYAB4Dc7HnrOSJWcoJcBS4ARzQ2F4BZ2LPmTeNuykHwEWgkQGAet9QfiMZjUSt3hwD7psGTWgs9pwH1hC1enMYeA7sKwDxBqjGnvNdZzKZjqmCAKh+U1kmEwi3IEBbIsugnY5avTkEtIAtFhBrQCX2nLVehqyRqFoCAAwBh3WGLAhbgCRIYYinwLolwLqKUwwi9pxV4KUlxKKKUwxC6ZElRCPLYAJxGfhSEOCz6m8HEXvOB2CyIMSk6m8HoXQTmMkJcA2YNTHm3congOvATo3tE3A29pxbpnFzQSiQPcB55IFmFNgFfEQeahaAGZMpsIJIAZWAHcDX2HN+2cT6r39GxmvC9aPNwH5gO1BOPFuBVWAZue0vA9+A12EgjPadnhCuH1WAE8ivYAQ4ohKaagV4gvxi5oG7YSA2vApsCOH60WngKrA3R9IsvQUuhIGY00K4flQG7gHH/mLytB4C42EgfrQb0mV7us8AAMeBS8mGNMR4nwHamtBB7B4QRNdaS0M8GxDEog7iyoAguvJ0QYSBuAOcAt71Kfl7wA8DcTvZ2KtOlJEr+ByyQtqqhTyHTIeB+ONeqi3brh+VgIN0fohUgWGggizZFTplu12yW8iy/YLOGWMpDMTPXnl+Az9vj2HERYqPAAAAAElFTkSuQmCC" type="image/png">
    <link rel="stylesheet" href="/assets/compiled/css/app.css">
    <link rel="stylesheet" href="/assets/compiled/css/app-dark.css">
    <style>
        .sidebar-wrapper {
            display: flex;
            flex-direction: column;
            /* height:100vh bawaan template lebih tinggi dari viewport asli di mobile,
               mendorong .sidebar-footer keluar layar. !important wajib karena
               app-dark.css override ini dengan selector yang lebih spesifik. */
            height: auto !important;
        }

        .sidebar-wrapper .sidebar-menu {
            flex: 1 1 auto;
            /* Tanpa ini flexbox tumbuh mengikuti konten, mendorong .sidebar-footer keluar layar. */
            min-height: 0;
            overflow-y: auto;
        }

        .sidebar-footer {
            flex-shrink: 0;
            padding: .75rem 1.5rem;
            border-top: 1px solid var(--bs-border-color, rgba(0, 0, 0, .08));
        }

        .sidebar-footer .sidebar-user-toggle {
            display: flex;
            align-items: center;
            width: 100%;
            padding: .4rem;
            margin: -.4rem;
            border-radius: .5rem;
            text-decoration: none;
            transition: background-color .15s ease;
        }

        .sidebar-footer .sidebar-user-toggle:hover,
        .sidebar-footer .sidebar-user-toggle:focus-visible {
            background-color: rgba(126, 136, 146, .12);
        }

        .sidebar-footer .sidebar-user-toggle::after {
            margin-left: auto;
            flex-shrink: 0;
        }

        .sidebar-footer .text {
            overflow: hidden;
        }

        .sidebar-footer .user-dropdown-name,
        .sidebar-footer .user-dropdown-status {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .sidebar-footer .dropdown-menu {
            width: 100%;
        }

        .sidebar-item.has-sub .submenu-link.disabled {
            opacity: .5;
            pointer-events: none;
            cursor: default;
        }

        .sidebar-wrapper .sidebar-toggler.x {
            position: static;
        }

        .sidebar-toggler.x .sidebar-hide {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 2rem;
            height: 2rem;
            border-radius: 50%;
            color: #a6a8aa;
            text-decoration: none;
            transition: background-color .15s ease, color .15s ease;
        }

        .sidebar-toggler.x .sidebar-hide:hover,
        .sidebar-toggler.x .sidebar-hide:focus-visible {
            color: #5a8dee;
            background-color: rgba(90, 141, 238, .1);
        }

        .sidebar-toggler.x .sidebar-hide i {
            font-size: 1.25rem;
        }

        /* Bootstrap default ".navbar-nav .dropdown-menu{position:static}" cuma
           dioverride ke "absolute" di dalam <nav class="navbar-expand-*">. Topbar
           di sini pakai <header> biasa, jadi override itu tidak aktif -- dropdown
           ikut alur dokumen dan melebarkan <ul class="ms-auto">, membuat ikon
           lonceng bergeser. Dipaksa balik ke absolute di sini. */
        .navbar-nav .notification-dropdown {
            position: absolute;
            top: 100%;
            right: 0;
            left: auto;
            margin-top: .125rem;
        }
    </style>
</head>

<body>
    <script src="/assets/static/js/initTheme.js"></script>
    <div id="app">
        <div id="sidebar">
            <div class="sidebar-wrapper active">
                <div class="sidebar-header position-relative">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="logo">
                            <a href="/index.php"><img src="data:image/svg+xml,%3csvg%20xmlns='http://www.w3.org/2000/svg'%20viewBox='0%200%20152%2034'%20fill-rule='evenodd'%20stroke-linejoin='round'%20stroke-miterlimit='2'%3e%3cpath%20d='M0%2027.472c0%204.409%206.18%205.552%2013.5%205.552%207.281%200%2013.5-1.103%2013.5-5.513s-6.179-5.552-13.5-5.552c-7.281%200-13.5%201.103-13.5%205.513z'%20fill='%23435ebe'%20fill-rule='nonzero'/%3e%3ccircle%20cx='13.5'%20cy='8.8'%20r='8.8'%20fill='%2341bbdd'/%3e%3cpath%20d='M71.676%203.22c.709%200%201.279.228%201.71.684.431.431.646%201.013.646%201.748v22.496c0%20.709-.203%201.267-.608%201.672s-.937.608-1.596.608-1.178-.203-1.558-.608-.57-.963-.57-1.672V12.492l-6.46%2012.236c-.304.557-.633.975-.988%201.254-.355.253-.773.38-1.254.38s-.899-.127-1.254-.38-.684-.671-.988-1.254l-6.498-12.046v15.466c0%20.684-.203%201.241-.608%201.672-.38.405-.899.608-1.558.608s-1.178-.203-1.558-.608-.57-.963-.57-1.672V5.652c0-.735.203-1.317.608-1.748.431-.456%201.001-.684%201.71-.684.988%200%201.761.545%202.318%201.634l8.436%2016.074%208.398-16.074c.557-1.089%201.305-1.634%202.242-1.634zm15.801%207.942c2.584%200%204.497.646%205.738%201.938%201.267%201.267%201.9%203.205%201.9%205.814v9.272c0%20.684-.203%201.229-.608%201.634-.405.38-.962.57-1.672.57-.658%200-1.203-.203-1.634-.608-.405-.405-.608-.937-.608-1.596v-.836c-.431.988-1.114%201.761-2.052%202.318-.912.557-1.976.836-3.192.836-1.241%200-2.368-.253-3.382-.76s-1.811-1.203-2.394-2.09-.874-1.875-.874-2.964c0-1.368.342-2.445%201.026-3.23.71-.785%201.85-1.355%203.42-1.71s3.737-.532%206.498-.532h.95v-.874c0-1.241-.266-2.141-.798-2.698-.532-.583-1.393-.874-2.584-.874a7.78%207.78%200%200%200-2.242.342c-.76.203-1.659.507-2.698.912-.658.329-1.14.494-1.444.494-.456%200-.836-.165-1.14-.494-.278-.329-.418-.76-.418-1.292%200-.431.102-.798.304-1.102.228-.329.596-.633%201.102-.912.887-.481%201.938-.861%203.154-1.14%201.242-.279%202.458-.418%203.648-.418zm-1.178%2015.922c1.267%200%202.293-.418%203.078-1.254.811-.861%201.216-1.963%201.216-3.306v-.798h-.684c-1.697%200-3.015.076-3.952.228s-1.608.418-2.014.798-.608.899-.608%201.558c0%20.811.279%201.482.836%202.014.583.507%201.292.76%202.128.76zm27.476-.456c1.418%200%202.128.595%202.128%201.786%200%20.557-.178%201.001-.532%201.33-.355.304-.887.456-1.596.456h-12.692c-.634%200-1.153-.203-1.558-.608a1.97%201.97%200%200%201-.608-1.444c0-.583.228-1.14.684-1.672l9.766-11.286h-8.474c-.71%200-1.242-.152-1.596-.456s-.532-.747-.532-1.33.177-1.026.532-1.33.886-.456%201.596-.456h12.274c.658%200%201.178.203%201.558.608.405.38.608.861.608%201.444%200%20.608-.216%201.165-.646%201.672l-9.804%2011.286h8.892zm19.762-1.52c.431%200%20.773.165%201.026.494.279.329.418.773.418%201.33%200%20.785-.468%201.444-1.406%201.976-.861.481-1.836.874-2.926%201.178-1.089.279-2.128.418-3.116.418-2.989%200-5.358-.861-7.106-2.584s-2.622-4.079-2.622-7.068c0-1.9.38-3.585%201.14-5.054s1.824-2.609%203.192-3.42c1.394-.811%202.964-1.216%204.712-1.216%201.672%200%203.129.367%204.37%201.102s2.204%201.773%202.888%203.116%201.026%202.926%201.026%204.75c0%201.089-.481%201.634-1.444%201.634h-11.21c.152%201.748.646%203.04%201.482%203.876.836.811%202.052%201.216%203.648%201.216.811%200%201.52-.101%202.128-.304.634-.203%201.343-.481%202.128-.836.76-.405%201.318-.608%201.672-.608zm-6.574-10.602c-1.292%200-2.33.405-3.116%201.216-.76.811-1.216%201.976-1.368%203.496h8.588c-.05-1.545-.43-2.711-1.14-3.496-.709-.811-1.697-1.216-2.964-1.216zm22.43-3.268c.658-.051%201.178.089%201.558.418s.57.823.57%201.482c0%20.684-.165%201.191-.494%201.52s-.925.545-1.786.646l-1.14.114c-1.495.152-2.597.659-3.306%201.52-.684.861-1.026%201.938-1.026%203.23v7.98c0%20.735-.228%201.305-.684%201.71-.456.38-1.026.57-1.71.57s-1.254-.19-1.71-.57c-.431-.405-.646-.975-.646-1.71V13.442c0-.709.215-1.254.646-1.634.456-.38%201.013-.57%201.672-.57s1.19.19%201.596.57c.405.355.608.874.608%201.558v1.52c.481-1.115%201.19-1.976%202.128-2.584.962-.608%202.026-.95%203.192-1.026l.532-.038z'%20fill='%23435ebe'%20fill-rule='nonzero'/%3e%3c/svg%3e" alt="Logo" srcset=""></a>
                        </div>
                        <div class="theme-toggle d-flex gap-2 align-items-center mt-2">
                            <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" aria-hidden="true"
                                role="img" width="20" height="20" preserveAspectRatio="xMidYMid meet" viewBox="0 0 21 21">
                                <g fill="none" fill-rule="evenodd" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M10.5 14.5c2.219 0 4-1.763 4-3.982a4.003 4.003 0 0 0-4-4.018c-2.219 0-4 1.781-4 4c0 2.219 1.781 4 4 4zM4.136 4.136L5.55 5.55m9.9 9.9l1.414 1.414M1.5 10.5h2m14 0h2M4.135 16.863L5.55 15.45m9.899-9.9l1.414-1.415M10.5 19.5v-2m0-14v-2" opacity=".3"></path>
                                    <g transform="translate(-210 -1)">
                                        <path d="M220.5 2.5v2m6.5.5l-1.5 1.5"></path>
                                        <circle cx="220.5" cy="11.5" r="4"></circle>
                                        <path d="m214 5l1.5 1.5m5 14v-2m6.5-.5l-1.5-1.5M214 18l1.5-1.5m-4-5h2m14 0h2"></path>
                                    </g>
                                </g>
                            </svg>
                            <div class="form-check form-switch fs-6">
                                <input class="form-check-input me-0" type="checkbox" id="toggle-dark" style="cursor: pointer">
                                <label class="form-check-label"></label>
                            </div>
                            <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" aria-hidden="true"
                                role="img" width="20" height="20" preserveAspectRatio="xMidYMid meet" viewBox="0 0 24 24">
                                <path fill="currentColor" d="m17.75 4.09l-2.53 1.94l.91 3.06l-2.63-1.81l-2.63 1.81l.91-3.06l-2.53-1.94L12.44 4l1.06-3l1.06 3l3.19.09m3.5 6.91l-1.64 1.25l.59 1.98l-1.7-1.17l-1.7 1.17l.59-1.98L15.75 11l2.06-.05L18.5 9l.69 1.95l2.06.05m-2.28 4.95c.83-.08 1.72 1.1 1.19 1.85c-.32.45-.66.87-1.08 1.27C15.17 23 8.84 23 4.94 19.07c-3.91-3.9-3.91-10.24 0-14.14c.4-.4.82-.76 1.27-1.08c.75-.53 1.93.36 1.85 1.19c-.27 2.86.69 5.83 2.89 8.02a9.96 9.96 0 0 0 8.02 2.89m-1.64 2.02a12.08 12.08 0 0 1-7.8-3.47c-2.17-2.19-3.33-5-3.49-7.82c-2.81 3.14-2.7 7.96.31 10.98c3.02 3.01 7.84 3.12 10.98.31Z"></path>
                            </svg>
                        </div>
                        <div class="sidebar-toggler x">
                            <a href="#" class="sidebar-hide d-xl-none d-block"><i class="bi bi-x bi-middle"></i></a>
                        </div>
                    </div>
                </div>
                <div class="sidebar-menu">
<?php
?>
                    <ul class="menu">
                        <li class="sidebar-title">Menu</li>

                        <!-- Dashboard -->
                        <li class="sidebar-item<?php if ($activeMenu === 'dashboard') { echo ' active'; } ?>">
                            <a href="/index.php" class="sidebar-link">
                                <i class="bi bi-grid-fill"></i>
                                <span>Dashboard</span>
                            </a>
                        </li>

<?php foreach ($menuConfig as $menuKey => $menu): ?>
<?php
    $onThisMenuPage = false;
    if (substr($activeMenu, 0, strlen($menuKey) + 1) === $menuKey . '-') {
        $onThisMenuPage = true;
    }
?>
                        <li class="sidebar-item has-sub<?php if ($onThisMenuPage) { echo ' active'; } ?>">
                            <a href="#" class="sidebar-link">
                                <i class="bi <?= htmlspecialchars($menu['icon'], ENT_QUOTES, 'UTF-8') ?>"></i>
                                <span><?= htmlspecialchars($menu['label'], ENT_QUOTES, 'UTF-8') ?></span>
                            </a>
                            <ul class="submenu<?php if ($onThisMenuPage) { echo ' active'; } ?>">
<?php foreach ($menu['items'] as $itemKey => $item): ?>
<?php if ($item['enabled']): ?>
                                <li class="submenu-item<?php if ($activeMenu === $itemKey) { echo ' active'; } ?>">
                                    <a href="<?= htmlspecialchars($item['url'], ENT_QUOTES, 'UTF-8') ?>" class="submenu-link"><?= htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8') ?></a>
                                </li>
<?php else: ?>
                                <li class="submenu-item">
                                    <a href="#" class="submenu-link disabled" tabindex="-1"><?= htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8') ?></a>
                                </li>
<?php endif; ?>
<?php endforeach; ?>
                            </ul>
                        </li>
<?php endforeach; ?>

                    </ul>
                </div>
                <div class="sidebar-footer">
                    <div class="dropdown dropup">
                        <a href="#" id="sidebarUserDropdown" class="sidebar-user-toggle d-flex align-items-center dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                            <div class="avatar avatar-md2 bg-primary">
                                <span class="avatar-content"><?= htmlspecialchars(userInitials($_SESSION['full_name']), ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                            <div class="text">
                                <h6 class="user-dropdown-name mb-0"><?= htmlspecialchars($_SESSION['full_name'], ENT_QUOTES, 'UTF-8') ?></h6>
                                <p class="user-dropdown-status text-sm text-muted mb-0"><?= htmlspecialchars($_SESSION['role'], ENT_QUOTES, 'UTF-8') ?></p>
                            </div>
                        </a>
                        <ul class="dropdown-menu shadow-lg" aria-labelledby="sidebarUserDropdown">
                            <?php if (isRoot()): ?>
                            <li><a class="dropdown-item" href="/includes/sidebar-footer/add-user.php"><i class="bi bi-person-plus me-2"></i>Manage User</a></li>
                            <li><a class="dropdown-item" href="/includes/sidebar-footer/activity-log.php"><i class="bi bi-clock-history me-2"></i>Log Aktivitas</a></li>
                            <?php endif; ?>
                            <li><a class="dropdown-item" href="/includes/sidebar-footer/profile.php"><i class="bi bi-person me-2"></i>My Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="/includes/sidebar-footer/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <div id="main">
            <header class="mb-3 d-flex align-items-center gap-2">
                <a href="#" class="burger-btn d-block d-xl-none">
                    <i class="bi bi-justify fs-3"></i>
                </a>

                <div class="position-relative flex-grow-1" style="max-width: 420px;" id="globalSearchWrapper">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="text" class="form-control" id="globalSearchInput" placeholder="Cari data..." autocomplete="off">
                    </div>
                    <!-- class "show" ditoggle manual di footer.php, bukan lewat Dropdown Bootstrap. -->
                    <div class="dropdown-menu w-100 p-0" id="globalSearchResults" style="max-height: 70vh; overflow-y: auto;"></div>
                </div>

                <ul class="navbar-nav ms-auto mb-0">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle text-gray-600" href="#" data-bs-toggle="dropdown" data-bs-display="static" aria-expanded="false">
                            <i class="bi bi-bell bi-sub fs-4"></i>
<?php if ($notificationCount > 0): ?>
                            <span class="badge badge-notification bg-danger"><?= (int) $notificationCount ?></span>
<?php endif; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end notification-dropdown" aria-labelledby="dropdownMenuButton">
                            <li class="dropdown-header">
                                <h6>Notifikasi</h6>
                            </li>
<?php if ($notificationCount === 0): ?>
                            <li class="dropdown-item notification-item">
                                <p class="mb-0 text-muted text-center py-2">Tidak ada notifikasi.</p>
                            </li>
<?php else: ?>
<?php foreach (array_slice($notificationItems, 0, 5) as $notificationItem): ?>
                            <li class="dropdown-item notification-item">
                                <a class="d-flex align-items-center" href="<?= htmlspecialchars($notificationItem['link'], ENT_QUOTES, 'UTF-8') ?>">
                                    <div class="notification-icon <?= htmlspecialchars($notificationItem['icon_bg_class'], ENT_QUOTES, 'UTF-8') ?>">
                                        <i class="bi <?= htmlspecialchars($notificationItem['icon_class'], ENT_QUOTES, 'UTF-8') ?>"></i>
                                    </div>
                                    <div class="notification-text ms-4">
                                        <p class="notification-title font-bold"><?= htmlspecialchars($notificationItem['title'], ENT_QUOTES, 'UTF-8') ?></p>
                                        <p class="notification-subtitle font-thin text-sm"><?= htmlspecialchars($notificationItem['subtitle'], ENT_QUOTES, 'UTF-8') ?></p>
                                    </div>
                                </a>
                            </li>
<?php endforeach; ?>
<?php if ($notificationCount > 5): ?>
                            <li>
                                <!-- Hardcode ke Menu 3 -- pikirkan ulang kalau ada sumber notifikasi lain. -->
                                <p class="text-center py-2 mb-0"><a href="/includes/menu/menu3/menu3-item1.php">Lihat semua (<?= (int) $notificationCount ?>)</a></p>
                            </li>
<?php endif; ?>
<?php endif; ?>
                        </ul>
                    </li>
                </ul>
            </header>
