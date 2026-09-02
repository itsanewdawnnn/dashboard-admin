<?php
// "Selamat <waktu>, <Nama>! <kalimat acak>" -- waktu mengikuti jam WIB saat ini.
function buildLoginGreetingText(string $fullName): string
{
    require __DIR__ . '/../login-greeting-messages.php';

    $hour = (int) date('G');

    if ($hour >= 4 && $hour <= 10) {
        $timeOfDay = 'pagi';
    } elseif ($hour >= 11 && $hour <= 14) {
        $timeOfDay = 'siang';
    } elseif ($hour >= 15 && $hour <= 17) {
        $timeOfDay = 'sore';
    } else {
        $timeOfDay = 'malam';
    }

    $randomIndex   = array_rand($loginGreetingMessages);
    $randomMessage = $loginGreetingMessages[$randomIndex];

    return 'Selamat ' . $timeOfDay . ', ' . $fullName . '! ' . $randomMessage;
}
