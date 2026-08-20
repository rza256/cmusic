<?php
function formatBytes($bytes, $precision = 2) { 
    $units = ['B', 'KB', 'MB', 'GB', 'TB']; 
   
    $bytes = max($bytes, 0); 
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024)); 
    $pow = min($pow, count($units) - 1); 
   
    $bytes /= pow(1024, $pow);
    // this will also work in place of the above line:
    // $bytes /= (1 << (10 * $pow)); 
   
    return round($bytes, $precision) . $units[$pow]; 
} 

function gradientTarget(string $color, int|float $fileSize): string
{
    [$r, $g, $b] = sscanf($color, "#%02x%02x%02x");

    $mb = 1024 * 1024;

    // step every 5 MB
    $step = floor($fileSize / (2 * $mb));

    // max 20 steps (100 MB)
    $step = min($step, 20);

    // start at 60% lighter, decrease by 2% per 5 MB
    $lighten = max(20, 60 - ($step * 2));

    $factor = $lighten / 100;

    $r = round($r + ((255 - $r) * $factor));
    $g = round($g + ((255 - $g) * $factor));
    $b = round($b + ((255 - $b) * $factor));

    return sprintf("#%02x%02x%02x", $r, $g, $b);
}