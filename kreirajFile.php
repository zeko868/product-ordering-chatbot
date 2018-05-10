<?php

$newfile = "test.txt";

if (file_exists($newfile)) {
    $fh = fopen($newfile, 'a');
    fwrite($fh, 'x');
} else {
    echo "y";
    $fh = fopen($newfile, 'wb');
    fwrite($fh, 'y');
}

fclose($fh);
chmod($newfile, 0777);

// echo (is_writable($filnme_epub.".js")) ? 'writable' : 'not writable';
echo (is_readable("$newfile")) ? 'readable' : 'not readable';

?>