<?php
/**
* PDO Database Connection Class
* 
* @author 			Jules Rau <admin@jules-rau.de>
* @copyright 		Jules Rau
* @license 			MIT license
* @origin 			https://github.com/ammerzone/webcam-login
* @version 	1.0		20.03.2017
*/

class DatabaseConnection{
	/**
	* Name of the Database
	* 
	* @var 		string
	* @access 	private
	*/
	private $login_dbname = 'database';
	
	/**
	* Name of the host
	* 
	* @var 		string
	* @access 	private
	*/
	private $login_host = 'localhost';
	
	/**
	* Name of the database user
	* 
	* @var 		string
	* @access 	private
	*/
	private $login_user = 'root';
	
	/**
	* Passwort of the database user
	* 
	* @var 		string
	* @access 	private
	*/
	private $login_password = '';
	
	/**
	* @var 		object
	* @access 	private
	*/
    private $pdo;
	
	/**
	* @var 		object
	* @access 	private
	*/
    private $query;
	
	/**
	* @var 		array
	* @access 	private
	*/
    private $settings;
	
	/**
	* @var 		boolean
	* @access 	private
	*/
    private $connected = false;
	
	/**
	* @var 		array
	* @access 	private
	*/
    private $parameters;
	
	/**
	* Constructs the Object
	* 
	* @access 	public
	* @return 	void
	*/
    public function __construct(){
        $this->connect();
        $this->parameters = array();
    }
	
	/** 
	* Initialize the request 
	* 
	* @access 	
	* @param 	string	$qry
	* @param 	string 	$params
	* @return 	void
	* @see 		init()
	*/
    private function init($qry, $params = ""){
        if(!$this->connected){ 
			$this->connect();
        }
		
		/* try to execute query */
		try{
            $this->query = $this->pdo->prepare($qry);
            $this->bindMore($params);
			
            if(!empty($this->$params))
                foreach($this->$params as $param => $value){
                    $type = PDO::PARAM_STR;
                    switch ($value[1]){
                        case is_int($value[1]) :   	$type = PDO::PARAM_INT;  break;
                        case is_bool($value[1]) : 	$type = PDO::PARAM_BOOL; break;
                        case is_null($value[1]) :  	$type = PDO::PARAM_NULL; break;
						case is_string($value[1]) : $type = PDO::PARAM_STR;	 break;
                    }
                    $this->query->bindValue($value[0], $value[1], $type);
                }
            $this->query->execute();
        }catch(PDOException $e){
            echo "<script>alert('".$this->setException($e->getMessage(), $qry)."');</script>";
        }
        $this->$params = array();
    }
	
	/** 
	* Connect to databaseserver with ODBC 
	* 
	* @access 	private
	* @return 	void
	* @see 		connect()
	*/
    private function connect(){
        $dsn = "mysql:";
		$dsn .= "dbname=" . $this->login_dbname . ";";
		$dsn .= "host=" . $this->login_host . ";";
		
		/* Try to make connection to database */
        try{
            $this->pdo = new PDO($dsn, $this->login_user, $this->login_password, array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"));
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
            $this->connected = true;
        }catch(PDOException $e){
            echo $this->setException($e->getMessage());
        }
    }
	
	/** 
	* Close connection to databaseserver 
	* 
	* @access 	public
	* @return 	void
	* @see 		closeConnection()
	*/
	public function closeConnection(){ 
		$this->pdo = null; 
	} 
	
	/** 
	* Get last insert Id 
	* 
	* @access 	public
	* @return 	void
	* @see 		lastInsertId()
	*/
    public function lastInsertId(){
		return $this->pdo->lastInsertId(); 
	}
	
	/** 
	* Begin transaction width database 
	* 
	* @access 	public
	* @return 	void
	* @see 		beginTransaction()
	*/
    public function beginTransaction(){
		return $this->pdo->beginTransaction(); 
	}
	
	/** 
	* Execute/commit database request
	* 
	* @access 	public
	* @return 	void
	* @see 		executeTransaction()
	*/
    public function executeTransaction(){
		return $this->pdo->commit(); 
	}
	
	/** 
	* Rollback PDO
	* 
	* @access 	public
	* @return 	void
	* @see 		rollBack()
	*/
	public function rollBack(){
		return $this->pdo->rollBack(); 
	}
	
	/** 
	* Print actual exception
	* 
	* @access 	private
	* @param 	string 	$msg
	* @param 	string 	$sql
	* @return 	string
	* @see 		setException()
	*/
	private function setException($msg, $sql = ""){
        $exception = "Unhandled Exception. " . "\n";
        $exception .= $msg . "\n";
		$exception .= "Raw SQL : " . $sql;
		
        return $exception;
    }
	
	/** 
	* Bind request (with parameters) 
	* 
	* @access 	public
	* @param 	array 	$arr
	* @return 	void
	* @see 		bindMore()
	*/
    public function bindMore($arr){
        if(empty($this->parameters) && is_array($arr)){
            $columns = array_keys($arr);
            foreach($columns as $i => &$col){ $this->bind($col, $arr[$col]); }
        }
    }
	
	/** 
	* Query request 
	* 
	* @access 	
	* @param 	string 	$qry
	* @param 	array 	$params
	* @param 	string 	$fetch
	* @return 	mixed
	* @see 		
	*/
	public function query($qry, $params = null, $fetch = PDO::FETCH_ASSOC){
        $qry = trim(str_replace("\r", " ", $qry));
        $this->init($qry, $params);
        $rawStatement = explode(" ", preg_replace("/\s+|\t+|\n+/", " ", $qry));
        $statement = strtoupper($rawStatement[0]);
        
        if($statement === 'SELECT' || $statement === 'SHOW')
            return $this->query->fetchAll($fetch);
        elseif($statement === 'INSERT' || $statement === 'UPDATE' || $statement === 'DELETE')
            return $this->query->rowCount();
		//elseif($statement === 'CREATE')
		//	return $this->query->exec($qry);
        else
            return NULL;
    }
	
	/** 
	* Bind request (friendly) 
	* 
	* @access 	public
	* @param  	string 	$param
	* @param  	string 	$val
	* @return 	void
	* @see 		bind()
	*/
    public function bind($param, $val){
		$this->parameters[sizeof($this->parameters)] = [":" . $param , $val];
	}
	
	/** 
	* Column request 
	* 
	* @access 	public
	* @param  	string 	$qry
	* @param  	array 	$params
	* @return 	mixed
	* @see 		column()
	*/
	public function column($qry, $params = null){
        $this->init($qry, $params);
        $Columns = $this->query->fetchAll(PDO::FETCH_NUM);
        $column = null;
		
        foreach($Columns as $cells){ $column[] = $cells[0]; }
		
		return $column;
    }
	
	/** 
	* Single row request 
	* 
	* @access 	public
	* @param  	string 	$qry
	* @param  	array  	$params
	* @param  	string 	$fetch
	* @return 	array
	* @see 		row()
	*/
    public function row($qry, $params = null, $fetch = PDO::FETCH_ASSOC){
        $this->init($qry, $params);
        $result = $this->query->fetch($fetch);
        $this->query->closeCursor();
        return $result;
    }
	
	/** 
	* Single value request 
	* 
	* @access 	public
	* @param  	string 	$qry
	* @param  	array  	$params
	* @return 	array
	* @see 		single()
	*/
	public function single($qry, $params = null){
        $this->init($qry, $params);
        $result = $this->query->fetchColumn();
        $this->query->closeCursor();
        return $result;
    }
}
?>
