<?php
$config = require 'config-test.php';


function ruteador($req){
    $keys = array_keys($req['get']);
    if (count($keys) > 0){
        return $keys[0];
    }else{
        return '/cdefault';
    }
}

function cdefault($db, $req, $res){
    return render($res, view('cdefault', []), true);
}

function cotra($req, $res){
    return "Hola mundo";
}

$rutas = array(
    '/cdefault' => array('GET' => partial('cdefault', 'db')),
    '/cotra'    => 'cotra');


dispatch(request($config), response(), $rutas, 'ruteador');

?>
