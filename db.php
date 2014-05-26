<?php
//header("Content-Type: application/json");


class dbora {

    private $config;
    private $con;

    public function __construct ( $db = 'defaultdb' ) {

        $this->config = parse_ini_file('config.ini.php',true);
        $this->connect( $db );


    }

    private function connect ( $db ) {
        $this->con = oci_pconnect (  $this->config[$db]['USERNAME'], $this->config[$db]['PASSWORD'],$this->config[$db]['DSN'], $this->config[$db]['CHARSET'] );
        if (!$this->con) {
            $e = oci_error();
            trigger_error(htmlentities($e['message'], ENT_QUOTES), E_USER_ERROR);
        }

    }

    public function executeSQL ( $sql ){

        $stid = oci_parse( $this->con, $sql);
        if (!$stid) {
            $e = oci_error($this->db);
            trigger_error(htmlentities($e['message'], ENT_QUOTES), E_USER_ERROR);
        }

        $r = oci_execute($stid, OCI_COMMIT_ON_SUCCESS );
        if (!$r) {
            $e = oci_error($stid);
            print_r($e);
            trigger_error(htmlentities($e['message'], ENT_QUOTES), E_USER_ERROR);
        } else {

            return true;
        }


    }


    public function getArray ( $sql, $add_data = null ) {


        $stid = oci_parse( $this->con, $sql);
        if (!$stid) {
            $e = oci_error($this->db);
            trigger_error(htmlentities($e['message'], ENT_QUOTES), E_USER_ERROR);
        }

        $r = oci_execute($stid);
        if (!$r) {
            $e = oci_error($stid);
            trigger_error(htmlentities($e['message'], ENT_QUOTES), E_USER_ERROR);
        }

        $data = array();
        // Fetch the results of the query
        while ($row = oci_fetch_array($stid, OCI_ASSOC+OCI_RETURN_NULLS)) {
            if( $add_data != null){
                $row[$add_data['KEY']] = $add_data['VALUE'];
            }

            $data[] = $row;
        }
        oci_free_statement($stid);
        //      oci_close($this->db);
        if( ! $data ){

        }
        return $data;

    }

    /*
     *  getArrayPage
     *  Function returns $count elements from $page_no page from $sql result table
     */
    public function getArrayPage ( $sql , $page_results, $page_no, $add_data ){

        $offset = $page_no * $page_results ;
        $limit = $offset + $page_results;
        $count = $this->getCount($sql);

        if( $limit > $count[0]['COUNT'] ){
            $offset += 1;
            $limit = $count[0]['COUNT'] + 1 ;
        }
        $exec_sql = "select * from (select videos.*, rownum  rnum from ($sql) videos where rownum <= $limit ) where rnum >= $offset";

        $result = $this->getArray( $exec_sql, $add_data );
        $data = Array ();
        $page_count = $count[0]['COUNT'] / $page_results;
        $data['total'] = $count[0]['COUNT'];
        $data['page'] = $page_no;
        $data['pages'] = ceil($page_count) ;

        $data['offset'] = $page_no * $page_results;
        $data['results'] = $result;

        return $data;

    }

    public function getCount ( $sql ) {

        $tail_sql =  explode('from', $sql);

        $count_query = "select count(*) as count from $tail_sql[1]";
        return $this->getArray($count_query);

    }


}

class dbmy extends mysqli{


    private $config;

    public function __construct ( $db = 'defaultdb' ) {

        $this->config = parse_ini_file('config.ini.php',true);
        $this->connecttodb( $db );


    }

    public function connecttodb ( $db ) {

        parent::init();

        if (! parent::options(MYSQLI_INIT_COMMAND, 'SET AUTOCOMMIT = 0')) {
            die('Setting MYSQLI_INIT_COMMAND failed');
        }
        if (!parent::options(MYSQLI_OPT_CONNECT_TIMEOUT, 5)) {
            die('Setting MYSQLI_OPT_CONNECT_TIMEOUT failed');
        }

        if (!parent::real_connect( $this->config[$db]['hostname'], $this->config[$db]['username'], $this->config[$db]['password'], $this->config[$db]['dbname'])) {
            die('Connect Error (' . mysqli_connect_errno() . ') '
                . mysqli_connect_error());
        }
    }

    public function getDB(){

        return parent;
    
    }

    public function getArray( $sql  ){

        $data = array();

        /* Select queries return a resultset */
        if ($result = parent::query($sql)) {

            while($obj = $result->fetch_array(  MYSQLI_ASSOC )){ 

                array_push($data, $obj);

            }

            /* free result set */
            $result->close();
        }

        return $data;

    }
    public function executeSQL( $query ){

        
        print "exec: $query \n";
        if( ! $stmt = parent::prepare($query)){

            print "problem ". $stmt->error ."\n";

        }
        if( ! $stmt->execute()){

            print "problem ". $stmt->error."\n";
        
        }
        parent::commit();

        return true;
    
    
    
    }

}

class dblite extends SQlite3 {


    public function __construct( $dbfile ){

        parent::open( $dbfile );
    
    }

    public function getArray( $sql  ){


        $res = array();

        $results = parent::query( $sql );
        while ($row = $results->fetchArray()) {
            array_push ($res, $row);
        }

        return $res;


    }




}


?>
