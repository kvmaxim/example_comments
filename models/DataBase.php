<?php

namespace app\models;
use Cassandra;
require __DIR__ . '/Const.php';

class DataBase
{
    private static $instance = null;
    private static $session = null;

    public static function getInstance()
    {
        if (!static::$instance) {
            static::$instance = new static;
        }

        return static::$instance;
    }

    private function __construct()
    {
        $cluster   = Cassandra::cluster()
            ->withCredentials(CASSANDRA_SERVER_USERNAME, CASSANDRA_SERVER_PASSWORD)
            ->withContactPoints(CASSANDRA_SERVER_HOST)
            ->withPort(CASSANDRA_SERVER_PORT)
            ->build();
        static::$session = $cluster->connect();
    }

    private function executeQueryAndGetResult($params)
    {

        $statement = new Cassandra\SimpleStatement($params);
        $future = static::$session->execute($statement);

        if (iterator_count($future) == 0) return false;

        $result = Array();
        foreach ($future as $row) {
            $result[] = $row;
        }

        return $result;
    }

// id_content - Post, Foto, Video, Lesson
// map<indx_comment, data_json>
//              data_json => [
//                    message,
//                    indx_comment_quote, // может быть равен -1, если нет цитаты
//                    quote_message, // max 100 symbol
//                    id_user,
//                    id_master_quote,
//                    id_set_likes,
//                    date_create,
//              ]
//
// format comment = id_user; date;index;data
// Создание Keyspace, Таблицы и функция для базы данных.
// CREATE KEYSPACE ks_comments WITH replication = {'class': 'SimpleStrategy', 'replication_factor': 1};
// CREATE TABLE ks_comments.tb_comments (id_content timeuuid, map_users_comments map<int, text>, number_comments int, pointer_last_comment int, PRIMARY KEY(id_content));
// CREATE FUNCTION check_exist_elem_in_map(in_map map<int, text>, in_indx_elem int) RETURNS NULL ON NULL INPUT RETURNS boolean LANGUAGE java AS 'return in_map.containsKey(in_indx_elem);';
// CREATE FUNCTION get_elems_interval_from_map(in_map map<int, text>, in_indx_last_elem int, in_count int) RETURNS NULL ON NULL INPUT RETURNS list<text> LANGUAGE java AS 'boolean fl=true; int count = 0; List<String> resList = new ArrayList<String>(); while (fl) { if (in_map.containsKey(in_indx_last_elem)) { String res = in_indx_last_elem + ":" + in_map.get(in_indx_last_elem); count++; resList.add(res); } in_indx_last_elem--; if (in_indx_last_elem < 0 || count == in_count) fl=false;} return resList;';

    public function tbCommentsGetNumberComments($id_content)
    {
        $params = "SELECT number_comments FROM ks_comments.tb_comments WHERE id_content = $id_content;";
        $result = $this->executeQueryAndGetResult($params);

        if ($result == false) return 0;

        return $result[0]['number_comments'];
    }

    public function tbCommentsGetPointerOnLastElem($id_content)
    {
        $params = "SELECT pointer_last_comment FROM ks_comments.tb_comments WHERE id_content = $id_content;";
        $result = $this->executeQueryAndGetResult($params);

        return $result[0]['pointer_last_comment'];
    }

    public function tbCommentsGetComments($id_content, $index_last_comment, $count_comments)
    {
        $params = "SELECT get_elems_interval_from_map(map_users_comments, $index_last_comment, $count_comments ) as result_list FROM ks_comments.tb_comments WHERE id_content = $id_content ;";
        $result = $this->executeQueryAndGetResult($params);

        if ($result[0]['result_list'] == null) return [];

        return $result[0]['result_list']->values();
    }

    public function tbCommentsCreateNewRowInTable($id_content)
    {
        $params = "INSERT INTO ks_comments.tb_comments (id_content, number_comments, pointer_last_comment) VALUES ($id_content , 0, 0) IF NOT EXISTS;";
        $this->executeQuery($params);
    }

    public function tbCommentsAddNewComment($id_content, $comment)
    {
        $params = "SELECT pointer_last_comment, number_comments FROM ks_comments.tb_comments WHERE id_content = $id_content;";
        $result = $this->executeQueryAndGetResult($params);

        $pointer_last_comment = $result[0]['pointer_last_comment'] + 1;
        $number_comments = $result[0]['number_comments'] + 1;

        $params = "UPDATE ks_comments.tb_comments SET map_users_comments = map_users_comments + { $pointer_last_comment: '$comment'}, pointer_last_comment = $pointer_last_comment, number_comments = $number_comments  WHERE id_content = $id_content;";
        $this->executeQuery($params);

        return true;
    }

    public function tbCommentsDeleteComment($id_content, $indx_comment)
    {
        if (!$this->tbCommentsCheckExistComment($id_content, $indx_comment)) return false;

        $params = "SELECT number_comments FROM ks_comments.tb_comments WHERE id_content = " . $id_content . ";";
        $result = $this->executeQueryAndGetResult($params);

        $number_comments = $result[0]['number_comments'] - 1;

        $params = "UPDATE ks_comments.tb_comments SET number_comments = $number_comments, map_users_comments = map_users_comments - { $indx_comment }  WHERE id_content = $id_content;";
        $this->executeQuery($params);

        return true;
    }

    public function tbCommentsCheckExistComment($id_content, $indx_comment)
    {
        $params = "SELECT check_exist_elem_in_map(map_users_comments, $indx_comment ) as result FROM ks_comments.tb_comments WHERE id_content = $id_content ;";
        $result = $this->executeQueryAndGetResult($params);

        if ($result[0]['result'] == null) return false;
        return  $result[0]['result'];
    }

    public function tbCommentsGetNumberElemsInList($id_content)
    {
        $params = "SELECT number_elems_in_list FROM ks_comments.tb_comments WHERE id_content = $id_content;";
        $result = $this->executeQueryAndGetResult($params);

        return $result[0]['number_elems_in_list'];
    }
}
