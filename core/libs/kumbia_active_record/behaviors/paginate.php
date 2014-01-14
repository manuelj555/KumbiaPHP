<?php
/**
 * KumbiaPHP web & app Framework
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://wiki.kumbiaphp.com/Licencia
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@kumbiaphp.com so we can send you a copy immediately.
 *
 * @category   Kumbia
 * @package    Db
 * @subpackage Behaviors 
 * @copyright  Copyright (c) 2005-2012 Kumbia Team (http://www.kumbiaphp.com)
 * @license    http://wiki.kumbiaphp.com/Licencia     New BSD License
 */

/**
 * Componente para p�ginar
 *
 * Permite paginar arrays y modelos
 *
 * @category   Kumbia
 * @package    Db
 * @subpackage Behaviors
 */
class Paginator
{

    /**
     * paginador
     *
     * page: n�mero de p�gina a mostrar (por defecto la p�gina 1)
     * per_page: cantidad de registros por p�gina (por defecto 10 registros por p�gina)
     *
     * Para p�ginacion por array:
     *  Par�metros sin nombre en orden:
     *    Par�metro1: array a p�ginar
     *
     * Para p�ginacion de modelo:
     *  Par�metros sin nombre en orden:
     *   Par�metro1: nombre del modelo o objeto modelo
     *   Par�metro2: condici�n de busqueda
     *
     * Par�metros con nombre:
     *  conditions: condici�n de busqueda
     *  order: ordenamiento
     *  columns: columnas a mostrar
     *
     * Retorna un PageObject que tiene los siguientes atributos:
     *  next: n�mero de p�gina siguiente, si no hay p�gina siguiente entonces es FALSE
     *  prev: numero de p�gina anterior, si no hay p�gina anterior entonces es FALSE
     *  current: n�mero de p�gina actual
     *  total: total de p�ginas que se pueden mostrar
     *  items: array de registros de la p�gina
     *  count: Total de registros
     *  per_page: cantidad de registros por p�gina
     *
     * @example
     *  $page = paginate($array, 'per_page: 5', "page: $page_num"); <br>
     *  $page = paginate('usuario', 'per_page: 5', "page: $page_num"); <br>
     *  $page = paginate('usuario', 'sexo="F"' , 'per_page: 5', "page: $page_num"); <br>
     *  $page = paginate('Usuario', 'sexo="F"' , 'per_page: 5', "page: $page_num"); <br>
     *  $page = paginate($this->Usuario, 'conditions: sexo="F"' , 'per_page: 5', "page: $page_num"); <br>
     *
     * @return object
     * */
    public static function paginate($model)
    {
        $params = Util::getParams(func_get_args());
        $page_number = isset($params['page']) ? (int) $params['page'] : 1;
        $per_page = isset($params['per_page']) ? (int) $params['per_page'] : 10;
        //Si la p�gina o por p�gina es menor de 1 (0 o negativo)
        if ($page_number < 1 && $per_page < 1) {
            throw new KumbiaException("La p�gina $page_number no existe en el p�ginador");
        }
        $start = $per_page * ($page_number - 1);
        //Instancia del objeto contenedor de p�gina
        $page = new stdClass();
        //Si es un array, se hace p�ginacion de array
        if (is_array($model)) {
            $items = $model;
            $n = count($items);
            //si el inicio es superior o igual al conteo de elementos,
            //entonces la p�gina no existe, exceptuando cuando es la p�gina 1
            if ($page_number > 1 && $start >= $n) {
                throw new KumbiaException("La p�gina $page_number no existe en el p�ginador");
            }
            $page->items = array_slice($items, $start, $per_page);
        } else {
            //Arreglo que contiene los argumentos para el find
            $find_args = array();
            $conditions = null;
            //Asignando Par�metros de busqueda
            if (isset($params['conditions'])) {
                $conditions = $params['conditions'];
            } elseif (isset($params[1])) {
                $conditions = $params[1];
            }
            if (isset($params['columns'])) {
                $find_args[] = "columns: {$params['columns']}";
            }
            if (isset($params['join'])) {
                $find_args[] = "join: {$params['join']}";
            }
            if (isset($params['group'])) {
                $find_args[] = "group: {$params['group']}";
            }
            if (isset($params['having'])) {
                $find_args[] = "having: {$params['having']}";
            }
            if (isset($params['order'])) {
                $find_args[] = "order: {$params['order']}";
            }
            if (isset($params['distinct'])) {
                $find_args[] = "distinct: {$params['distinct']}";
            }
            if (isset($conditions)) {
                $find_args[] = $conditions;
            }
            //contar los registros
            $n = call_user_func_array(array($model, 'count'), $find_args);
            //si el inicio es superior o igual al conteo de elementos,
            //entonces la p�gina no existe, exceptuando cuando es la p�gina 1
            if ($page_number > 1 && $start >= $n) {
                throw new KumbiaException("La p�gina $page_number no existe en el p�ginador");
            }
            //Asignamos el offset y limit
            $find_args[] = "offset: $start";
            $find_args[] = "limit: $per_page";
            //Se efectua la busqueda
            $page->items = call_user_func_array(array($model, 'find'), $find_args);
        }
        //Se efectuan los calculos para las p�ginas
        $page->next = ($start + $per_page) < $n ? ($page_number + 1) : false;
        $page->prev = ($page_number > 1) ? ($page_number - 1) : false;
        $page->current = $page_number;
        $page->total = ceil($n / $per_page);
        $page->count = $n;
        $page->per_page = $per_page;
        return $page;
    }

    /**
     * p�ginador por sql
     *
     * @param string $model Nombre del modelo
     * @param string $sql Consulta sql
     *
     * page: n�mero de p�gina a mostrar (por defecto la p�gina 1)
     * per_page: cantidad de registros por p�gina (por defecto 10 registros por p�gina)
     *
     *
     * Retorna un PageObject que tiene los siguientes atributos:
     *  next: numero de p�gina siguiente, si no hay p�gina siguiente entonces es false
     *  prev: numero de p�gina anterior, si no hay p�gina anterior entonces es false
     *  current: numero de p�gina actual
     *  total: total de p�ginas que se pueden mostrar
     *  items: array de registros de la p�gina
     *  count: Total de registros
     *
     * @example
     *  $page = paginate_by_sql('usuario', 'SELECT * FROM usuario' , 'per_page: 5', "page: $page_num");
     *
     * @return object
     * */
    public static function paginate_by_sql($model, $sql)
    {
        $params = Util::getParams(func_get_args());
        $page_number = isset($params['page']) ? (int) $params['page'] : 1;
        $per_page = isset($params['per_page']) ? (int) $params['per_page'] : 10;
        //Si la p�gina o por p�gina es menor de 1 (0 o negativo)
        if ($page_number < 1 || $per_page < 1) {
            throw new KumbiaException("La p�gina $page_number no existe en el p�ginador");
        }
        $start = $per_page * ($page_number - 1);
        //Instancia del objeto contenedor de p�gina
        $page = new stdClass();
        //Cuento las apariciones atraves de una tabla derivada
        $n = $model->count_by_sql("SELECT COUNT(*) FROM ($sql) AS t");
        //si el inicio es superior o igual al conteo de elementos,
        //entonces la p�gina no existe, exceptuando cuando es la p�gina 1
        if ($page_number > 1 && $start >= $n) {
            throw new KumbiaException("La p�gina $page_number no existe en el p�ginador");
        }
        $page->items = $model->find_all_by_sql($model->limit($sql, "offset: $start", "limit: $per_page"));
        //Se efectuan los calculos para las p�ginas
        $page->next = ($start + $per_page) < $n ? ($page_number + 1) : false;
        $page->prev = ($page_number > 1) ? ($page_number - 1) : false;
        $page->current = $page_number;
        $page->total = ceil($n / $per_page);
        $page->count = $n;
        $page->per_page = $per_page;
        return $page;
    }

}