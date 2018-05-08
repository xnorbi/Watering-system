<?PHP

header('Content-type: text/plain; charset=utf8', true);

function check_header($name, $value = false) {
    if(!isset($_SERVER[$name])) {
        return false;
    }
    if($value && $_SERVER[$name] != $value) {
        return false;
    }
    return true;
}

function sendFile($path) {
    header($_SERVER["SERVER_PROTOCOL"].' 200 OK', true, 200);
    header('Content-Type: application/octet-stream', true);
    header('Content-Disposition: attachment; filename='.basename($path));
    header('Content-Length: '.filesize($path), true);
    header('x-MD5: '.md5_file($path), true);
    readfile($path);
}

if(!check_header('HTTP_USER_AGENT', 'ESP8266-http-Update')) {
    header($_SERVER["SERVER_PROTOCOL"].' 403 Forbidden', true, 403);
    echo "only for ESP8266 updater!\n";
    exit();
}

if(
    !check_header('HTTP_X_ESP8266_STA_MAC') ||
    !check_header('HTTP_X_ESP8266_AP_MAC') ||
    !check_header('HTTP_X_ESP8266_FREE_SPACE') ||
    !check_header('HTTP_X_ESP8266_SKETCH_SIZE') ||
    !check_header('HTTP_X_ESP8266_SKETCH_MD5') ||
    !check_header('HTTP_X_ESP8266_CHIP_SIZE') ||
    !check_header('HTTP_X_ESP8266_SDK_VERSION')
) {
    header($_SERVER["SERVER_PROTOCOL"].' 403 Forbidden', true, 403);
    echo "only for ESP8266 updater! (header)\n";
    exit();
}

$db = array(				//CASE SENSITIVE!!!!!!!!!!!!!!!!!
//-------------------------------------------------------------szelep
    "5C:CF:7F:79:50:41" => "v1.18.1",			//locsolo2
//-------------------------------------------------------------szenzor
    "18:FE:AA:AA:AA:BB" => "TEMP-1.0.0"
);

if(!isset($db[$_SERVER['HTTP_X_ESP8266_STA_MAC']])) {
    header($_SERVER["SERVER_PROTOCOL"].' 500 ESP MAC not configured for updates', true, 500);
}

//$localBinary = "client.ino.generic.bin";
$localBinary = "/var/www/html/esp/update/bin/".$db[$_SERVER['HTTP_X_ESP8266_STA_MAC']].".bin";

// Check if version has been set and does not match, if not, check if
// MD5 hash between local binary and ESP8266 binary do not match if not.
// then no update has been found.
$fp = fopen('log/log.log', 'a');
$date = date("y/m/d : H:i:s :");
fwrite($fp, $date);
fwrite($fp, "  ");
fwrite($fp, $_SERVER['HTTP_X_ESP8266_STA_MAC']);
fwrite($fp, " HW version: ");
fwrite($fp, $_SERVER['HTTP_X_ESP8266_VERSION']);
fwrite($fp, " desired version: ");
fwrite($fp, $db[$_SERVER['HTTP_X_ESP8266_STA_MAC']]);
fwrite($fp, "   ");
fwrite($fp, "/var/www/html/esp/update/bin/".$db[$_SERVER['HTTP_X_ESP8266_STA_MAC']].".bin");
//fclose($fp);

if(/*!check_header('HTTP_X_ESP8266_SDK_VERSION') &&*/ ($db[$_SERVER['HTTP_X_ESP8266_STA_MAC']] != $_SERVER['HTTP_X_ESP8266_VERSION']) && ($_SERVER["HTTP_X_ESP8266_SKETCH_MD5"] != md5_file($localBinary))){
//    || $_SERVER["HTTP_X_ESP8266_SKETCH_MD5"] != md5_file($localBinary)) {
    fwrite($fp, "   UPDATE STARTED\n");
    fclose($fp);
    sendFile($localBinary);
} else {
    fwrite($fp, "   NO UPDATE\n");
    fclose($fp);    
    header($_SERVER["SERVER_PROTOCOL"].' 304 Not Modified', true, 304);
}

header($_SERVER["SERVER_PROTOCOL"].' 500 no version for ESP MAC', true, 500);


