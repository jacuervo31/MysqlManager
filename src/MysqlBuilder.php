<?php
namespace MysqlManager;

interface QueryBuilder
{

    //Insertar
    public function insert(string $table, array $fields): QueryBuilder;
    //Consultas
    public function select(String $table, array $fields): QueryBuilder;
    public function join(string $table, string $type, string $condition): QueryBuilder;
    public function where(string $field,string $operation, string $value): QueryBuilder;
    public function limit(int $offset, int $limit): QueryBuilder;
    public function orderBy(string $field, string $type): QueryBuilder;
    //Actualizar
    public function update(string $table, array $fields): QueryBuilder;
    //Eliminar
    public function delete(string $table): QueryBuilder;
    

    
    public function getSQL(): array;
}

class MysqlBuilder implements QueryBuilder
{
    protected $query;

    protected function cleanQuery(): void
    {
        $this->query = new \stdClass();
    }

    public function insert(string $table, array $fields): QueryBuilder
    {
        $this->cleanQuery();
        $this->query->type = "INSERT";
        $this->query->sqlMain = "INSERT INTO $table (".implode(",",array_keys($fields)).") ";

        //Valores
        $values = [];
        foreach($fields as $field){
            $target = uniqid();
            $values[":$target"] = "$field";
        }
        $this->query->bindParams = $values;
        $this->query->sqlMain .= " VALUES (".implode(",",array_keys($values)).")";
        return $this;
    }

    public function select(string $table, array $fields): QueryBuilder
    {
        $this->cleanQuery();
        $fields = (empty($fields))? ["*"]: $fields;
        $this->query->type = "SELECT";
        $this->query->sqlMain = "SELECT " . implode(",", $fields) . " FROM $table";
        return $this;
    }

    public function update(string $table, array $fields): QueryBuilder
    {
        $this->cleanQuery();
        $this->query->type = "UPDATE";
        $this->query->sqlMain = "UPDATE $table SET ";

        //Valores
        $values = [];
        $set = [];
        foreach($fields as $fieldName => $field){
            $target = uniqid();
            array_push($set,"$fieldName = :$target");
            $values[":$target"] = "$field";
        }
        $this->query->sqlMain .= implode(" , ", $set);
        $this->query->bindParams = $values;
        return $this;
    }

    public function delete(string $table): QueryBuilder
    {
        $this->cleanQuery();
        $this->query->type = "DELETE";
        $this->query->sqlMain = "DELETE FROM $table";
        
        return $this;
    }


    public function join(string $table, string $type, string $condition): QueryBuilder
    {
        if (in_array($this->query->type, ["SELECT"])){
            $this->query->joins[] = " $type JOIN $table ON $condition";
        }
        return $this;
    }

    public function where(string $field,string $operation, string $value): QueryBuilder
    {
        if (in_array($this->query->type, ["SELECT", "UPDATE","DELETE"])){
            $target = uniqid();
            $this->query->where[] = "$field $operation :$target";
            $this->query->bindParams[":$target"] = $value;
        }

        return $this;
    }

    public function orderBy(string $field, string $type): QueryBuilder
    {
        if (in_array($this->query->type, ["SELECT"])){
            $this->query->order = " ORDER BY $field $type ";
        }
        return $this;
    }

    public function limit(int $offset, int $limit): QueryBuilder
    {
        if (in_array($this->query->type, ["SELECT"])){
            $this->query->limit = " LIMIT $limit OFFSET $offset";
        }
        return $this;
    }

    public function getSQL(): array
    {
        $query = $this->query;
        $sql = $query->sqlMain;
        $bindParams = [];

        if(!empty($query->joins)){
            $sql .= implode(' ',$query->joins);
        }

        if (!empty($query->where)) {
            $sql .= " WHERE " . implode(' AND ', $query->where);
        }

        if (!empty($query->order)){
            $sql .= $query->order;
        }

        if (!empty($query->limit)){
            $sql .= $query->limit;
        }

        if(!empty($query->bindParams)){
            $bindParams = $query->bindParams;
        }

        $sql .= ";";
        return ["SQL" => $sql, "bindParams" => $bindParams];
    }
}