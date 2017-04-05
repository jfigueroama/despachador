<?php
$config = require 'config-test.php';


function ruteador($rutas, $req){
    $keys = array_keys($req['get']);
    if (count($keys) > 0){
        return $keys[0];
    }else{
        return '/cdefault';
    }
}

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
    '/cdefault' => array('GET' => partial('cdefault', 'db')),
    '/cotra'    => 'cotra',
    '/cparams'  => 'cparams');


dispatch(request($config), response(), $rutas, 'ruteador');

?>
