<?php
// Shared helpers for the Pinggy plugin webGUI - included by both pinggy.page
// and action.php so config load/save logic lives in exactly one place.

$cfgDir  = "/boot/config/plugins/pinggy";
$cfgFile = "$cfgDir/pinggy.cfg";
$fwdFile = "$cfgDir/forwardings.cfg";
$ctl     = "/usr/local/emhttp/plugins/pinggy/scripts/pinggy-ctl";

if (!is_dir($cfgDir)) @mkdir($cfgDir, 0755, true);
if (!file_exists($fwdFile)) @touch($fwdFile);

$regionHosts = [
    "auto"      => "pro.pinggy.io",
    "usa"       => "us.a.pinggy.io",
    "europe"    => "eu.a.pinggy.io",
    "asia"      => "ap.a.pinggy.io",
    "samerica"  => "pro.pinggy.io", // no published dedicated hostname yet - falls back to Auto
    "australia" => "pro.pinggy.io", // no published dedicated hostname yet - falls back to Auto
];
$regionLabels = [
    "auto"      => "Auto",
    "usa"       => "USA",
    "europe"    => "Europe",
    "asia"      => "Asia",
    "samerica"  => "South America (routes via Auto)",
    "australia" => "Australia (routes via Auto)",
];

function pinggy_load_cfg($cfgFile) {
    $cfg = [
        "TOKEN"=>"", "PINGGY_HOST"=>"pro.pinggy.io", "REGION"=>"auto",
        "USE_FORCE"=>"1", "AUTO_RECONNECT"=>"1",
        "KEEPALIVE_ENABLED"=>"1", "KEEPALIVE"=>"60",
        "HTTPS_ONLY"=>"0", "XFF"=>"0", "FULLURL"=>"0",
        "AUTOSTART"=>"0",
    ];
    if (file_exists($cfgFile)) {
        foreach (file($cfgFile) as $line) {
            $line = trim($line);
            if ($line === "" || $line[0] === "#") continue;
            if (preg_match('/^([A-Z_]+)="?(.*?)"?$/', $line, $m)) {
                $cfg[$m[1]] = $m[2];
            }
        }
    }
    return $cfg;
}

function pinggy_save_cfg($cfgFile, $cfg) {
    $out = "";
    foreach ($cfg as $k => $v) {
        $v = str_replace('"', '', $v);
        $out .= "$k=\"$v\"\n";
    }
    file_put_contents($cfgFile, $out);
}

function pinggy_load_fwds($fwdFile) {
    $rows = [];
    if (file_exists($fwdFile)) {
        foreach (file($fwdFile, FILE_IGNORE_NEW_LINES) as $line) {
            if (trim($line) === "") continue;
            $parts = explode("|", $line);
            if (count($parts) < 5) continue;
            $rows[] = [
                "enabled"    => $parts[0],
                "type"       => $parts[1],
                "hostname"   => $parts[2],
                "local_host" => $parts[3],
                "local_port" => $parts[4],
            ];
        }
    }
    return $rows;
}

function pinggy_save_fwds($fwdFile, $rows) {
    $out = "";
    foreach ($rows as $r) {
        $out .= $r["enabled"]."|".$r["type"]."|".$r["hostname"]."|".$r["local_host"]."|".$r["local_port"]."\n";
    }
    file_put_contents($fwdFile, $out);
}
