<?php
declare(strict_types=1);

function fr_month_name(int $m): string {
    $months = [
        1=>'janvier',2=>'février',3=>'mars',4=>'avril',5=>'mai',6=>'juin',
        7=>'juillet',8=>'août',9=>'septembre',10=>'octobre',11=>'novembre',12=>'décembre'
    ];
    return $months[$m] ?? '';
}

function fr_day_name(int $w): string {
    $days = ['dimanche','lundi','mardi','mercredi','jeudi','vendredi','samedi'];
    return $days[$w] ?? '';
}

// 2026-01-19 -> 19/01/2026
function date_fr(string $ymd): string {
    $dt = DateTime::createFromFormat('Y-m-d', $ymd);
    if (!$dt) return $ymd;
    return $dt->format('d/m/Y');
}

// 2026-01-19 -> Lundi le 19 janvier 2026
function date_longue_fr(string $ymd): string {
    $dt = DateTime::createFromFormat('Y-m-d', $ymd);
    if (!$dt) return $ymd;

    $w = (int)$dt->format('w'); // 0=dimanche
    $d = (int)$dt->format('d');
    $m = (int)$dt->format('m');
    $y = (int)$dt->format('Y');

    return ucfirst(fr_day_name($w)) . " le " . sprintf('%02d', $d) . " " . fr_month_name($m) . " " . $y;
}

// 2026-01-19 14:05:22 -> 19/01/2026 14:05
function datetime_fr(string $any): string {
    try {
        $dt = new DateTime($any);
        return $dt->format('d/m/Y H:i');
    } catch (Exception $e) {
        return $any;
    }
}
