<?php
require_once("/usr/local/emhttp/plugins/pinggy/php/helpers.php");

$do = $_GET["do"] ?? "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if ($do === "save_settings") {
        $cfg = pinggy_load_cfg($cfgFile);
        $cfg["TOKEN"]              = trim($_POST["token"] ?? "");
        $region                    = trim($_POST["region"] ?? "auto");
        if (!isset($regionHosts[$region])) $region = "auto";
        $cfg["REGION"]             = $region;
        // manual server override, if provided, wins over the region preset
        $manualHost                = trim($_POST["pinggy_host"] ?? "");
        $cfg["PINGGY_HOST"]        = $manualHost !== "" ? $manualHost : $regionHosts[$region];
        $cfg["USE_FORCE"]          = isset($_POST["use_force"]) ? "1" : "0";
        $cfg["AUTO_RECONNECT"]     = isset($_POST["auto_reconnect"]) ? "1" : "0";
        $cfg["KEEPALIVE_ENABLED"]  = isset($_POST["keepalive_enabled"]) ? "1" : "0";
        $cfg["KEEPALIVE"]          = trim($_POST["keepalive"] ?? "60");
        $cfg["HTTPS_ONLY"]         = isset($_POST["https_only"]) ? "1" : "0";
        $cfg["XFF"]                = isset($_POST["xff"]) ? "1" : "0";
        $cfg["FULLURL"]            = isset($_POST["fullurl"]) ? "1" : "0";
        $cfg["AUTOSTART"]          = isset($_POST["autostart"]) ? "1" : "0";
        pinggy_save_cfg($cfgFile, $cfg);
    }

    if ($do === "save_forwardings") {
        $rows = [];
        $n = intval($_POST["row_count"] ?? 0);
        for ($i = 0; $i < $n; $i++) {
            $port = trim($_POST["fwd_port_$i"] ?? "");
            if ($port === "" || isset($_POST["fwd_delete_$i"])) continue;
            $rows[] = [
                "enabled"    => isset($_POST["fwd_enabled_$i"]) ? "1" : "0",
                "type"       => trim($_POST["fwd_type_$i"] ?? "http"),
                "hostname"   => str_replace("|", "", trim($_POST["fwd_hostname_$i"] ?? "")),
                "local_host" => str_replace("|", "", trim($_POST["fwd_localhost_$i"] ?? "localhost")) ?: "localhost",
                "local_port" => str_replace("|", "", $port),
            ];
        }
        if (isset($_POST["add_row"])) {
            $rows[] = ["enabled"=>"1","type"=>"http","hostname"=>"","local_host"=>"localhost","local_port"=>""];
        }
        pinggy_save_fwds($fwdFile, $rows);
    }

    if ($do === "start")   { shell_exec("$ctl start 2>&1"); }
    if ($do === "stop")    { shell_exec("$ctl stop 2>&1"); }
    if ($do === "restart") { shell_exec("$ctl restart 2>&1"); }
}
?>
<script>
// This response lives in a hidden iframe. Reload the actual visible
// settings page in the parent window so it picks up the new state.
if (parent && parent.location) {
    parent.location.reload();
}
</script>
