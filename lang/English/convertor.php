<?php
/**
 * PHP arrays to po file
 */

$fh = fopen("common.po", 'w');
fwrite($fh, "#\n");
fwrite($fh, "msgid \"\"\n");
fwrite($fh,  "msgstr \"\"\n");

include 'common.php';

foreach ($lang_common as $key => $value) {
    $key = addslashes($key);
    $value = addslashes($value);
    fwrite($fh, "\n");
    fwrite($fh, "msgid \"$key\"\n");
    fwrite($fh, "msgstr \"$value\"\n");
}
fclose($fh);