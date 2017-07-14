<?php
/**
 * Pequeña API de abstracción de la base de datos usando PDO.
 *
 * Little abstraction API over PDO.
 *
 * @todo:
 * - implementar transacciones con closures :-)
 * @author jfigueroa <coloso@gmail.com>
 * @date 2016xxxx
 */

/**
 * Crea una conexión para PDO.
 *
 * Creates a PDO connection.
 *
 */
function conn($dsn, $user, $pass, $options = [], $err = 'Falló la conexión: '){
    try {
        // Creo que esta opcion no la usamos
        //PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'
        $gbd = new PDO($dsn, $user, $pass, $options);
    } catch (PDOException $e) {
        echo $err . $e->getMessage();
    }
    return $gbd;
}

/**
 * Ejecuta una consulta $sql a la $conn usando los parámetros $params
 * y retorna un arreglo asociativo con los resultados.
 *
 * Executes a $sql query on $conn using $params as parameters and returns
 * an associative array. Throw exception on sql error.
 */
function q($conn, $sql, $params = array(), $mode = PDO::FETCH_ASSOC,
           $emsg = 'Error en consulta a la DB:'){
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);

    $ei  = $stmt->errorInfo();

    if ($ei[0] != '00000'){
        $err = implode($ei, ', ');
        throw new Exception("$emsg $err\n");
    }
    $data = $stmt->fetchAll($mode);
    return $data;
}

/**
 * Inserta una tupla segun la sentencia $sql con los parámetros $params.
 * Retorna  el último id insertado en la conexión $conn.
 *
 * Insert a tuple as stated by $sql with $params parameters. Returns the
 * last inserted id on $conn.
 */
function insert($conn, $sql, $params = array(),
                $emsg = 'Error en insercion a la DB:'){
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);

    $ei  = $stmt->errorInfo();

    if ($ei[0] != '00000'){
        $err = implode($ei, ', ');
        throw new Exception("$emsg $err\n");
    }
    $lid = $conn->lastInsertId();
    return $lid;
}

/**
 * Ejecuta una sentencia $sql en $conn usando los parámetros $params.
 * Retorna las filas afectadas.
 *
 * Executes a $sql on $conn using $params as parameters and returns the
 * number of affected rows.
 */
function execute($conn, $sql, $params = array(),
                 $emsg = 'Error en operacin en la DB:'){
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);

    $ei  = $stmt->errorInfo();

    if ($ei[0] != '00000'){
        $err = implode($ei, ', ');
        throw new Exception("$emsg $err\n");
    }

    $data = $stmt->rowCount();
    return $data;
}


/**                                                                             
 * Obtiene una sola tupla a traves de un sql o nulo.
 *
 * Obtains a tuple (just one) from $conn with $sql and $params.
 * Otherwise returns null.
 */
function instance($conn, $sql, $params){
    $data = q($conn, $sql, $params);

    if (count($data) > 0)
        return $data[0];
    else
        return null;
}

/**
 * Retorna una instancia
 */
function inst($conn, $table, $params){
    $nparams = [];
    $awhere  = [];

    if (!is_array($params)){
        $id     = $params;
        $params = array('id' => $id);
    }

    $keys = array_keys($params);
    foreach($keys as $k){
        $nparams[":$k"] = $params[$k];
        $awhere[]       = "$k=:$k";
    }

    $where = implode(" AND ", $awhere);
    $sql = "SELECT * FROM $table WHERE $where";

    return instance($conn, $sql, $nparams);
}

/*
function get_schema($conn, $wsentity){
    $s = array();
    $q    = "select * from information_schema.columns 
                    where table_name='$wsentity'";
    $stmt = $conn->query($q);
    $dschema = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($dschema as $c){
        $s[$c['COLUMN_NAME']] = $c;
    }

    return $s;
}
*/
/*
function get_instances($conn, $wsentity, $id = null){
    $s  = array();
    $nid = intval($id);

    if (is_null($id))
        $q    = "select * from $wsentity";
    else
        $q    = "select * from $wsentity where id=$nid";
    $stmt = $conn->query($q);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    return $data;
}*/
/*
function coerce($schema, $data){
    $ndata = array();
    foreach ($data as $tupla){
        $nt = array();
        foreach ($schema as $k => $c){
            $cname = $c['COLUMN_NAME'];
            if (! is_null($tupla[$cname])){
                if (is_string($c['NUMERIC_PRECISION'])){
                    if ($c["NUMERIC_SCALE"] != "0"){
                        $nt[$cname] =
                            floatval($tupla[$cname]);
                    }else{
                        $nt[$cname] = intval($tupla[$cname]);
                    }
                }else{
                    $nt[$cname] = $tupla[$cname];
                }
            }else{
                $nt[$cname] = $tupla[$cname];
            }
        }
        $ndata[] = $nt;
    }

    return $ndata;
}
 */
/*
function tables($conn){
    $stmt = $conn->query('show tables');
    $tablas = array();
    foreach ($stmt->fetchAll(PDO::FETCH_NUM) as $t){
        $tablas[] = $t[0];
    }

    return $tablas;
}
*/



?>
