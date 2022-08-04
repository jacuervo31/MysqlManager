<?php
namespace MysqlManager;

use MysqlManager\MysqlBuilder;
use PDO;
use PDOException;

class DataBase{

    private PDO|FALSE $COM;
    private array $errors = [];
    public MysqlBuilder $queryBuilding;
    public $result = [];

    private function errorTracker(string $msg_error): void
    {
        $error = [
            "error" => "Error en la base de Datos",
            "reason" => $msg_error,
            "code" => 500

        ];
        $this->errors[] = $error;
    }

    public function getErrors(): array
    {
        $errors = $this->errors;
        return $errors;
    }

    public function __construct(array $access)
    {
        $host = $access["DBHOST"];
        $dataBaseName = $access["DBNAME"];
        $user = $access["DBUSER"];
        $pswd = $access["DBPSWD"];
        $charset = $access["DBCHARTSET"];
        try {
            $this->queryBuilding = new MysqlBuilder();
            $this->COM = new PDO(
                "mysql:host=$host;dbname=$dataBaseName",$user,$pswd,
                [PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES $charset"]
                );
        }catch (PDOException$e){
            $this->errorTracker($e->getMessage());
            $this->COM = FALSE;
        }
    }

    public function consult(): bool 
    {
        if($this->COM != FALSE){
            $sql = $this->queryBuilding->getSQL();
            $request = $this->COM->prepare($sql["SQL"]);
            if(!empty($sql["bindParams"])){
                foreach($sql["bindParams"] as $target => $bindParam){
                    $request->bindValue($target,$bindParam);
                }
            }
            try {
                $result = $request->execute();
                $this->result = ($result)? $request->fetchAll(PDO::FETCH_ASSOC): [];
                return $result;

            }catch (PDOException$e){
                $this->errorTracker($e->getMessage());
                return FALSE;
            }
        }else{
            return FALSE;
        }
    }

}