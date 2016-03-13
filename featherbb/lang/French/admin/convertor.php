<?php
/**
 * PHP arrays to po file
 */

$fh = fopen("users.po", 'w');
fwrite($fh, "#\n");
fwrite($fh, "msgid \"\"\n");
fwrite($fh,  "msgstr \"\"\n");

include 'users.php';

foreach ($lang_admin_users as $key => $value) {
    $key = addslashes($key);
    $value = addslashes($value);
    fwrite($fh, "\n");
    fwrite($fh, "msgid \"$key\"\n");
    fwrite($fh, "msgstr \"$value\"\n");
}
fclose($fh);