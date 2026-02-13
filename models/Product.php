<?php
class Product
{
    private $conn;
    private $table_name = "productos";

    public $id;
    public $sku;
    public $name;
    public $description;
    public $price;
    public $stock;
    public $created_at;
    public $updated_at;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function create()
    {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET name=:name,  sku=:sku, price=:price, stock=:stock ,created_at=:created_at";

        $stmt = $this->conn->prepare($query);

        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->sku = htmlspecialchars(strip_tags($this->sku));
        $this->price = number_format((float)$this->price, 2, '.','');
        $this->stock = (int)$this->stock;
        $this->created_at = date('Y-m-d H:i:s');

        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":sku", $this->sku);
        $stmt->bindParam(":price", $this->price);
        $stmt->bindParam(":stock", $this->stock);
        $stmt->bindParam(":created_at", $this->created_at);

        if ($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    public function read()
    {
        $query = "SELECT id, name, sku, price, stock, created_at 
                  FROM " . $this->table_name . " 
                  ORDER BY created_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function readOne()
    {
        $query = "SELECT id, name, sku, price, stock, created_at 
                  FROM " . $this->table_name . " 
                  WHERE id = :id 
                  LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $this->name = $row['name'];
            $this->sku = $row['sku'];
            $this->price = $row['price'];
            $this->stock = $row['stock'];
            $this->created_at = $row['created_at'];
            return true;
        }
        return false;
    }

    public function update()
    {
        $query = "UPDATE " . $this->table_name . " 
                  SET name = :name, sku = :sku, price = :price, stock = :stock, description = :description, updated_at = :updated_at
                  WHERE id = :id";

        $stmt = $this->conn->prepare($query);

        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->sku = htmlspecialchars(strip_tags($this->sku));
        $this->price = number_format((float)$this->price, 2, '.','');
        $this->stock = (int)$this->stock;
        $this->description = htmlspecialchars(strip_tags($this->description));
        $this->id = htmlspecialchars(strip_tags($this->id));
        $this->updated_at = date('Y-m-d H:i:s');

        $stmt->bindParam(':name', $this->name);
        $stmt->bindParam(":sku", $this->sku);
        $stmt->bindParam(":price", $this->price);
        $stmt->bindParam(":stock", $this->stock);
        $stmt->bindParam(":description", $this->description);
        $stmt->bindParam(':id', $this->id);
        $stmt->bindParam(":updated_at", $this->created_at);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }
    public function delete()
    {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";

        $stmt = $this->conn->prepare($query);
        $this->id = htmlspecialchars(strip_tags($this->id));
        $stmt->bindParam(':id', $this->id);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }
}
?>