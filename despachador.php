<?php
/**
 * Pequeña librería para crear aplicaciones en PHP con un solo punto de
 * entrada.
 *
 * Esta librería está basada en noodlehaus/dispatch
 * @date 20170404
 * @author jfigueroama
 * @license BSD 2-Clause
 * @todo: cookies, inyectores al request, event handlers, etc.
 */

/**
 * Aplicación parcial de una función.
 * Retorna una función parcialmente aplicada.
 * Tomada de http://eddmann.com/posts/using-partial-application-in-php/
 */
function partial(/* $func, $args... */){
    $args = func_get_args();
    $func = array_shift($args);

    return function() use ($func, $args){
        return call_user_func_array($func,
            array_merge($args, func_get_args()));
    };
}

/**
 * Realiza un $arr[$k] = $v y vuelve el nuevo $arr.
 */
function assoc($arr, $k, $v){
    $arr[$k] = $v;
    return $arr;
}

/**
 * Agrega $v a los valores el arreglo $arr[$k]. Si $arr[$k] no es un arreglo lo
 * transforma a arreglo.
 */
function assoc2($arr, $k, $v){
    if (!is_array($arr[$k]))
        $arr[$k] = array();
    
    $a = $arr[$k];
    $a[] = $v;
    $arr[$k] = $a;

    return $a;
}

/**
 * Agrega $v a la clave $k en el arreglo $arr[$kin].
 */
function assoc2k($arr, $kin, $k, $v){
    if (!is_array($arr[$kin]))
        $arr[$kin] = array();
    
    $a = $arr[$kin];
    $a[$k] = $v;
    $arr[$kin] = $a;

    return $a;
}

/**
 * Crea un request a partir del entorno actual. Agrega todas las variables
 * convencionales del entorno a un arreglo junto con la configuracion enviada
 * por el usuario.
 * MUTABLE
 */
function request($config = null){
    $req = array(
        'request_method'    => $_SERVER['REQUEST_METHOD'],
        'query_string'      => isset($_SERVER['QUERY_STRING']) ?
                                $_SERVER['QUERY_STRING'] : '',
        'script_name'       => $_SERVER['SCRIPT_NAME'],

        // Estos nombres son como los encontraría en el entorno.
        'get'               => $_GET,
        'post'              => $_POST,
        'files'             => $_FILES,
        'cookie'            => $_COOKIE,    // arreglo de cookies entrantes
        'server'            => $_SERVER,
        'globals'           => $GLOBALS,
        'session'           => isset($_SESSION) ? $_SESSION : array()
    );

    if ($config){
        $req['config'] = $config;
    }else{
        throw new Exception("No se ha recibido una configuracion");
    }

    return $req;
}

/**
 * Crea un response base.
 */
function response(){
    return array(
        'status'    => 200,
        'content'   => '',
        'headers'   => array(),
        'session'   => array(),
        'cookies'   => array(),
    );
}

/**
 * Dice si un arreglo es un response o tiene lo basico de un response.
 */
function is_response($res){
    return (isset($res['status']) && isset($res['content'])
        && isset($res['headers']) && isset($res['session'])
        && isset($res['cookies']));
}

/**
 * Injecta cada inyector en $injectors al request $req.
 */
function inject($req, $injectors){
    $next = $req;
    $prev = $req;

    foreach ($injectors as $i){
        $next = $i($prev);
        $prev = $next;
    }

    return $next;
}

/**
 * Procesa un response con funciones externas. Tal vez mutable.
 * Recibe el request por si hay datos que debe utilizar de ahí.
 * Cada procesador en $pfne es una función debe recibir el request y el
 * response y debe retornar un response.
 */
function process($req, $res, $processors){
    $next = $res;
    $prev = $res;

    foreach ($processors as $p){
        $next = $p($req, $prev);
        $prev = $next;
    }

    return $next;
}


/**
 * Retorna un response con una cabecera extra para enviar o alguna modificada.
 */
function rheader($res, $cabecera){
    return assoc2($res, 'headers', $cabecera);
}

/**
 * Cambia el status de un response a $status.
 */
function status($res, $status = 200){
    return assoc($res, 'status', $status);
}

/**
 * Retorna un response que además debe dejar persistentes datos en sesión.
 * La persistencia de la sesión depende de procesadores extras si se requiere
 * el uso de bases de datos.
 * La persistencia default se basa en la sesión de php.
 */
function session($res, $k, $v){
    return assoc2k($res, 'session', $k, $v);
}

/**
 * Crea una cookie y la agrega al response $res.
 */
function cookie($res, $nombre, $valor = '', $tiempo = 0,
    $path = null, $dominio = null,
    $segura = false, $httponly = false){
    return assoc2($res, 'cookies', array(
        'name'      => $nombre,
        'value'     => $valor,
        'expire'    => $tiempo,
        'path'      => $path,
        'domain'    => $dominio,
        'secure'    => $segura,
        'httponly'  => $httponly));
}

/**
 * Retorna una página php rendereada como si las variables mandadas en $vars
 * fueran locales.
 * Las páginas php cargadas DEBEN tener la extensión .html.php
 * para localizarse.
 * Tomada de noodlehaus/dispatch
 */
function page($path, array $vars = []) {
  ob_start();
  extract($vars, EXTR_SKIP);
  require "{$path}.html.php";
  return trim(ob_get_clean());
}

/**
 * Crea una url con la ruta
 */
function url($req, $params = null){
    $url = $req['script'] . "?";
    if (is_array($params)){
        return $url . implode("&", array_map(function($k, $v){
            return "$k=$v";
        }, array_keys($params), array_values($params)));
    }else{
        throw new Exception("Se espera que los parametros de la URL
                             sean un arreglo.");
    }
}

/**
 * Retorna el texto de una pagina dentro de un layout donde $contenido es el
 * texto de la página para agregarse al layout.
 */
function view($path, array $vars = [], $layout_path = "", array $lvars = []){
    if (empty($layout_path)){
        return page($path, $vars);
    }else{
        $nlvars = array_merge($lvars, array('content' => page($path, $vars)));
        return page($layout_path, $nlvars);
    }
}




/**
 * Define el contenido del response y si se va a debugear o no.
 */
function render($res, $cadena, $debug = false){
    if (!is_string($cadena)){
        $cadena = strval($cadena);
    }

    $res['content'] = $cadena;
    $res['debug']   = $debug;
    return $res;
}

/**
 * Define el contenido del response como un json y lo codifica de la misma
 * manera.
 */
function jrender($res, $data, $debug = false){
    $res['content'] = json_encode($data, JSON_PRETTY_PRINT);
    $res['debug']   = $debug;
    return rheader($res, 'Content-Type', 'text/json');
}

/**
 * Procesa un response y lo mete al buffer se salida de PHP. Este es el que
 * pinta o agrega las cosas a la respuesta que se le va a dar al cliente.
 */
function serve($req, $res){
    // Debugeando. Nota: No procesa datos a sesión ni nada.
    if ($res['debug']){
        echo "<h3>RESPONSE</h3>";
        echo "<pre>";
        print_r($res);
        echo "</pre>";
        echo "<h3>REQUEST</h3>";
        echo "<pre>";
        print_r($req);
        echo "</pre>";

        exit();
    }

    if (is_response($res)){
        foreach ($res['session'] as $k => $v){
            $_SESSION[$k] = $v;
        }

        foreach ($res['headers'] as $he){
            header($he);
        }

        foreach ($res['cookies'] as $c){
            setcookie($c['name'], $c['value'], $c['expire'], $c['path'],
                $c['domain'], $c['secure'], $c['httponly']);
        }

        http_response_code($res['status']);

        echo $res['content'];
    }else{
        $nres = strval($res);
        echo $nres;
    }
}

function init_extras($req, $res, $extras){
    if (!isset($extras['injectors']))
        $extras['injectors'] = [];
    if (!isset($extras['processors']))
        $extras['processors'] = [];
    if (!isset($extras['errorfn']))
        $extras['errorfn'] = function ($req, $res, $ex){
            return
                status(render($res,
                    "Hubo un error: {$ex->getMessage()}"), 500);
        };

    return $extras;
}


/**
 * Despacha una ruta a partir de las rutas.
 *
 *
 *
 * function baja($req, $req){ return render(view('vista', [...], 'layout')); }
 * function alta($db, $req, $res){ return render( ... ); }
 * function ruteador($req){ return $req['get']['r']; }
 *
 * $db    = new PDO( ... );
 * $rutas = array('baja' => 
 *                array('GET' => 'baja',
 *                   'POST' => partial('alta', $db)),
 *                'otra' => 'otra de todo');
 *
 * $config = array('app_url' => '', 'app_path' => '')
 * $req    = request($config);
 * $res    = response();
 * $errfn  = function($req, $res, $ex){ ... });
 *          // errfn debe recibir los parametros descritos y devolver un
            // response modificado.
 * dispatch($req, $res, $rutas, 'ruteador', $errfn);
 */
function dispatch($req, $res, $rutas, $ruteadorfn, $extras = []){
    try{
        $extras     = init_extras($req, $res, $extras);
        $errorfn    = $extras['errorfn'];
        $ruta       = $ruteadorfn($req);

        $nreq   = inject($req, $extras['injectors']);

        if (isset($rutas[$ruta])){
            if (is_array($rutas[$ruta])){
                $rt     = $rutas[$ruta];
                $method = $req['request_method'];

                if (isset($rt[$method]))
                    $rfn = $rt[$method];
                else
                    throw new Exception(
                        "La ruta '$ruta' no tiene un handler para el
                        m&eacute;todo $method.");
            }else{
                $rfn = $rutas[$ruta];
            }

            $nres  = $rfn($nreq, $res);
            if (!is_response($nres)){
                $nres = render($res, $nres);
            }

            $npres = process($req, $nres, $extras['processors']);

            // Envia el response procesado junto con el request ya inyectado.
            serve($nreq, $npres);
        }else{
            throw new Exception("Ruta $ruta desconocida");
        }
    }catch(Exception $ex){
        serve($req, $errorfn($req, $res, $ex));
    }
}
