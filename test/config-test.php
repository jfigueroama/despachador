<?php

require '..'.DIRECTORY_SEPARATOR.'despachador.php';
return array(
    'app_url'       => './?',   // requerido para url()
    'path_url'      => './',    // requerido para curl()
    'path'          => __DIR__,
    'views_path'    => __DIR__.DIRECTORY_SEPARATOR.'views'
);
?>
