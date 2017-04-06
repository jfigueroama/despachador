<?php
$config = require 'config-test.php';

function cdefault($db, $req, $res){
    return render($res, view('cdefault', ['req' => $req]));
}

function cotra($req, $res){
    return "Hola mundo";
}

function cparams($req, $res){
    return render($res, "Probando parametros", true);
}

$rutas = array(
    array('/cdefault', 'GET' => partial('cdefault', 'db')),
    array('/cotra',   'GET' => 'cotra'),
    array('/cparams', 'GET' => 'cparams'));


dispatch(request($config), response(), $rutas);

?>
