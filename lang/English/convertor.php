<?php
/**
 * PHP arrays to po file
 */

$fh = fopen("antispam_questions.po", 'w');
fwrite($fh, "#\n");
fwrite($fh, "msgid \"\"\n");
fwrite($fh,  "msgstr \"\"\n");

include 'antispam.php';

foreach ($lang_antispam_questions as $key => $value) {
    $key = addslashes($key);
    $value = addslashes($value);
    fwrite($fh, "\n");
    fwrite($fh, "msgid \"$key\"\n");
    fwrite($fh, "msgstr \"$value\"\n");
}
fclose($fh);