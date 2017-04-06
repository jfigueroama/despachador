<?php
/**
 * Pequeña librería para crear aplicaciones en PHP con un solo punto de
 * entrada.
 *
 * Esta librería está basada en noodlehaus/dispatch
 * @date 20170404
 * @author jfigueroama
 * @license BSD 2-Clause
 * @todo:
 * - una funcionalidad para inyectar en los parametros de la url (get) los
 *   parametros detectados por pattern matching en la url como
 *      /reporte/:anio/:periodo
 *      deberia devolver array('anio' => X, 'periodo' => 
 *  NOTA: Esta funcionalidad es discutible ya que depende de cuando aplicarla
 *        al request dado. Ademas de que necesita recibir las rutas tambien.
 *      De hecho, el ruteador es un INJECTOR que mete datos a los parametros
 *      GET en base a patrones en las rutas!, fuck!, bueno, no es problema,
 *      excepto que se tiene que correr con el ruteador. Hay que definir mejor
 *      esta parte, sale? ademas hay que comprobar que las rutas tienen un
 *      formato correcto y tirar una excepcion si no para no joder al
 *      cliente ni al desarrollador.
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
 * Es un print_r en <pre> tags.
 */
function pprint_r($data, $titulo = null){
    if ($titulo){
        echo "<h4>$titulo</h4>";
    }

    echo "<pre>";
    print_r($data);
    echo "</pre>";
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
        throw new Exception("No se ha recibido una configuracion", 500);
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
 *
 */
function cfg($req, $key){
    if (isset($req['config']) && isset($req['config'][$key]))
        return $req['config'][$key];
    else
        return null;
}

/**
 * Crea una url basada en el app_url buscando una $ruta con $paramaetros
 * extras.
 */
function url($req, $ruta, $parametros = ''){
    $app_url = cfg($req, 'app_url');

    if (!empty($parametros))
        $p = "&$parametros";
    else
        $p = '';

    return "$app_url$ruta&$pp";
}

/**
 * Crea una url basada en el path_url de la configuracion.
 */
function surl($req, $url){
    $path_url = dirname(cfg($req, 'app_url'));
    return $path_url.$url;

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
    if (!is_array($cadena) && !is_string($cadena)){
            $cadena = strval($cadena);
    }else if (is_array($cadena)){
        throw new Exception ("Error al renderear valor.");
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

function errorfn_default($req, $res, $ex){
    return render(status($res, $ex->getCode()),
        "Hubo un error:<br/>\n {$ex->getMessage()}");
}

function router_default($crutas, $req, $rdefault = null){
    $url = null;
    $vars = array_keys($req['get']);

    if (count($vars) > 0){
        $url = $vars[0];

        $ruta = null;
        foreach ($crutas as $r){
            $params = match($r, $url);
            if (is_array($params)){
                $req['get'] = array_merge($req['get'],
                    array_combine($r['params'], $params));
                $ruta = $r[0];

                break;
            }
        }

        if (is_null($ruta)){
            throw new Exception(
                "La url '$url' no coincide con ninguna ruta
                establecida.");

        }
    }else{
        $ruta = $rdefault;
    }
    return array('route' => $ruta, 'req' => $req, 'url' => $url);
}


/**
 * Inicializa los parametros extras:
 * injectors
 * processors
 * errorfn
 * router
 * default_route
 */
function init_extras($req, $res, $extras = array()){
    if (!isset($extras['injectors']))
        $extras['injectors'] = [];
    if (!isset($extras['processors']))
        $extras['processors'] = [];
    if (!isset($extras['errorfn']))
        $extras['errorfn'] = 'errorfn_default';
    if (!isset($extras['router'])){
        $extras['router'] = 'router_default';
    }
    if (!isset($extras['default_route'])){
        $extras['default_route'] = null;
    }

    return $extras;
}

/**
 * Compila una arreglo que representa una ruta para que pueda encontrarse si
 * es que tiene parametros embedidos. Devuelve el arreglo de ruta con la info
 * necesaria para el matching.
 *
 * Los parametros deben comenzar con ':' y tener una letra al principio, luego
 * pueden contener '_' o letras o números. Ejemplos:
 * :anio, :a1, :a_b :a1_  son buenos, ya que pueden representar variables php.
 * 
 * Los valores que puede tomar de un parametros son cualquiera que no contenga
 * una diagonal como la que separa los paths en la url.
 *
 * Una ruta puede ser:
 * array('/reporte/:anio/:periodo',
 *       'GET' => 'reporte', 'POST' => 'destruir_app')
 * Retorna algo como:
 * array('/reporte/:anio/:periodo', 'GET' .....,
 *       'params'  => ['anio', 'periodo'],
 *       'matcher' => '/^\/reporte\/([^\/])+\/([^\/]+)$/')
 */
function compile_route($ruta){
    $r      = $ruta[0];
    $params = array();
    $ps     = array();

    preg_match_all('/:[a-zA-Z][_\w]*/', $r, $ps);
    $ps = $ps[0];

    foreach ($ps as $p){
        $params[] = substr($p, 1);
    }

    $pmatcher = '/^'.str_replace('/', '\/', $r).'$/';
    
    foreach ($ps as $p){
        $pmatcher = str_replace($p, '([^\/&]+)', $pmatcher);
    }

    $ruta['matcher'] = $pmatcher;
    $ruta['params']  = $params;

    return $ruta;
}

/**
 * Compila un arreglo de rutas.
 */
function compile_routes($rutas){
    $nrutas = array();

    foreach ($rutas as $r){
        $nr = compile_route($r);
        $nrutas[$nr[0]] = $nr;
    }

    return $nrutas;
}

/**
 * Checa si una url matchea con los datos de una ruta.
 * Retorna un arreglo con los valores de la ruta si es que los encuentra.
 * Si la ruta no tiene parámetros devuelve un arreglo vacío.
 * Si no coincide la ruta y la url retorna null.
 */
function match($ruta, $url){
    if (!isset($ruta['matcher'])){
        throw new Exception("La ruta '{$ruta[0]}' no esta compilada.");
    }

    $matcher = $ruta['matcher'];

    $ms = array();
    preg_match($matcher, $url, $ms);

    if (count($ms)> 0){
        array_shift($ms);
        return $ms;
    }else
        return null;
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
 * dispatch($req, $res, $rutas);
 */
function dispatch($req, $res, $rutas, $extras = array()){
    try{
        $extras     = init_extras($req, $res, $extras);
        $nreq       = inject($req, $extras['injectors']);


        $errorfn    = $extras['errorfn'];
        $router     = $extras['router'];
        $crutas     = compile_routes($rutas);
        $ruteo      = $router($crutas, $nreq, $extras['default_route']);
        $ruta       = $ruteo['route'];
        $nreq       = $ruteo['req'];


        if (isset($crutas[$ruta])){
            if (is_array($crutas[$ruta])){
                $rt     = $crutas[$ruta];
                $method = $nreq['request_method'];

                if (isset($rt[$method]))
                    $rfn = $rt[$method];
                else
                    throw new Exception(
                        "La url '{$ruteo['url']}' no tiene un handler para el
                        m&eacute;todo $method.", 500);
            }else{
                $rfn = $crutas[$ruta];
            }

            $nres  = $rfn($nreq, $res);
            if (!is_response($nres)){
                $nres = render($res, $nres);
            }

            $npres = process($nreq, $nres, $extras['processors']);

            // Envia el response procesado junto con el request ya inyectado.
            serve($nreq, $npres);
        }else{
            throw new Exception("La URL '{$ruteo['url']}' es desconocida", 404);
        }
    }catch(Exception $ex){
        serve($req, $errorfn($req, $res, $ex));
    }
}
