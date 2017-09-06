<?php

    include './i_databasehandler.php';
    include './columnitem.php';

    class PDOHandler implements DatabaseHandler {

        private $pdo;

        function __construct() {
            include './credentials.php';
            $this->pdo = new PDO('mysql:host='.$host.';dbname='.$db, $user, $pass);
        }

        function select($table, $where) {
            $sql_string = 'SELECT * FROM '.$table;

            if($where != null) {
                $sql_string .= ' WHERE '.$where.';';
            } else {
                $sql_string .= ';';
            }

            $response = array();

            foreach($this->pdo->query($sql_string) as $row) {
                array_push($response, $row);
            }

            return $response;
        }

        function insert($table, $column_items) {
            $sql_string = 'INSERT INTO '.$table.' (';
            $value_string = '';

            foreach($column_items as $index => $column_item) {

                if($index != 0) {
                    $sql_string = $sql_string.', ';
                    $value_string = $value_string.', ';
                }

                $sql_string .= $column_item->name;
                $value_string .= '"'.$column_item->value.'"';
            }
            
            $sql_string .= ') VALUES ('.$value_string.');';

            return $this->pdo->exec($sql_string);
        }

        function update($table, $column_items, $where) {
            $sql_string = 'UPDATE '.$table.' SET';

            foreach($column_items as $index => $column_item) {

                if($index != 0) {
                    $sql_string.=',';
                }

                $sql_string.= ' '.$column_item->name.' = "'.$column_item->value.'"';
            }
            
            $sql_string.= ' WHERE '.$where.';';
            return $this->pdo->exec($sql_string);
        }

        function delete($table, $where) {
            $sql_string = 'DELETE FROM '.$table.' WHERE '.$where.';';
            return $this->pdo->exec($sql_string);
        }
    }

?>