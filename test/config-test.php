<?php

ini_set('display_errors', 'on');
ini_set('error_reporting', E_ALL);

require_once '..'.DIRECTORY_SEPARATOR.'despachador.php';

return array(
    'app_url'       => './?',   // una requerida y usada en url() y surl()

    // extras
    'views_path'    => __DIR__.DIRECTORY_SEPARATOR.'views'
);
?>
