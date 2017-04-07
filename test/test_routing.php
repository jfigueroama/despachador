<?php

$config = require 'config-test.php';

echo "<h3>Test de ruteo.</h3>";


$rutas = array(
    array('/reporte/:anio/:periodo'),
    array('/una/url/estatica/sin/parametros'));

pprint_r($rutas, "Rutas sin compilar");

$nrutas = array_values(compile_routes($rutas));
pprint_r($nrutas, "Rutas compiladas");

$ruta1 = '/reporte/2016/10';
$ruta2 = '/reporte/jose figueroa martinez/de la cueva';
$ruta3 = '/reporte/jose figueroa martinez/de&cueva=2&lacueva';
$ruta4 = '/una/url/estatica/sin/parametros';
$ruta5 = '/una/url/estatic/sin/parametros';

pprint_r(match($nrutas[0], $ruta1), "Ruta $ruta1 en ruta 0");
pprint_r(match($nrutas[0], $ruta2), "Ruta $ruta2 en ruta 0");
pprint_r(match($nrutas[0], $ruta3), "Ruta $ruta3 en ruta 0");
pprint_r(match($nrutas[0], $ruta3), "Ruta $ruta3 en ruta 0");
pprint_r(match($nrutas[1], $ruta4), "Ruta $ruta4 en ruta 1");
pprint_r(match($nrutas[1], $ruta5), "Ruta $ruta5 en ruta 1");


?>
