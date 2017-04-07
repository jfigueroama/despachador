<?php
/**
 * Pequeña API de abstracción de la base de datos usando PDO.
 *
 * @todo:
 * - implementar transacciones con closures :-)
 * @author jfigueroa <coloso@gmail.com>
 * @date 2016xxxx
 */

function conn($dsn, $user, $pass, $options = []){
    try {
        $opciones = array(
          // Creo que esta opcion no la usamos
          //PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'
        ); 
        $gbd = new PDO($dsn, $user, $pass, $options);
    } catch (PDOException $e) {
        echo 'Falló la conexión: ' . $e->getMessage();
    }
    return $gbd;
}

/**
 * Ejecuta una consulta $sql a la $conn usando los parámetros $params
 * y retorna un arreglo asociativo con los resultados.
 */
function q($conn, $sql, $params = array(), $mode = PDO::FETCH_ASSOC){
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);

    $ei  = $stmt->errorInfo();

    if ($ei[0] != '00000'){
        $err = implode($ei, ', ');
        throw new Exception("Error en consulta a la DB: $err\n");
    }
    $data = $stmt->fetchAll($mode);
    return $data;
}

/**
 * Inserta una tupla segun la sentencia $sql con los parámetros $params.
 * Retorna  el último id insertado en la conexión $conn.
 */
function insert($conn, $sql, $params = array()){
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);

    $ei  = $stmt->errorInfo();

    if ($ei[0] != '00000'){
        $err = implode($ei, ', ');
        throw new Exception("Error en insercion a la DB: $err\n");
    }
    $lid = $conn->lastInsertId();
    return $lid;
}

function execute($conn, $sql, $params = array()){
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);

    $ei  = $stmt->errorInfo();

    if ($ei[0] != '00000'){
        $err = implode($ei, ', ');
        throw new Exception("Error en operacin en la DB: $err\n");
    }

    $data = $stmt->rowCount();
    return $data;
}


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
}

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

function tablas($conn){
    $stmt = $conn->query('show tables');
    $tablas = array();
    foreach ($stmt->fetchAll(PDO::FETCH_NUM) as $t){
        $tablas[] = $t[0];
    }

    return $tablas;
}



?>
