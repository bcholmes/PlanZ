<?php

class EmailCC {
    public $id;
    public $name;
    public $address;

    function __construct($id, $name, $address) {
        $this->id = $id;
        $this->name = $name;
        $this->address = $address;
    }

    public static function findAll($db) {
        $query = <<<EOD
        SELECT
            emailccid, description, display_order, emailaddress
             FROM EmailCC
        ORDER BY display_order;
EOD;

        $stmt = mysqli_prepare($db, $query);
        $records = array();
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            while ($row = mysqli_fetch_object($result)) {
                $record = new EmailCC($row->emailccid, $row->description, $row->emailaddress);
                $records[] = $record;
            }
            mysqli_stmt_close($stmt);
            return $records;
        } else {
            throw new DatabaseSqlException("Query could not be executed: $query");
        }
    }

    function asSwiftAddress() {
        return [ $this->address => $this->name ];
    }

    function asArray() {
        return array("id" => $this->id,
            "name" => $this->name,
            "address" => $this->address);
    }
}


class EmailFrom {
    public $id;
    public $name;
    public $address;

    function __construct($id, $name, $address) {
        $this->id = $id;
        $this->name = $name;
        $this->address = $address;
    }

    public static function findAll($db) {
        $query = <<<EOD
        SELECT
            emailfromid, emailfromdescription, display_order, emailfromaddress
             FROM EmailFrom
        ORDER BY display_order;
EOD;

        $stmt = mysqli_prepare($db, $query);
        $records = array();
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            while ($row = mysqli_fetch_object($result)) {
                $record = new EmailFrom($row->emailfromid, $row->emailfromdescription, $row->emailfromaddress);
                $records[] = $record;
            }
            mysqli_stmt_close($stmt);
            return $records;
        } else {
            throw new DatabaseSqlException("Query could not be executed: $query");
        }
    }

    function asSwiftAddress() {
        return [ $this->address => $this->name ];
    }

    function asArray() {
        return array("id" => $this->id,
            "name" => $this->name,
            "address" => $this->address);
    }
}

class EmailTo {
    public $id;
    public $name;

    function __construct($id, $name) {
        $this->id = $id;
        $this->name = $name;
    }

    public static function findAll($db) {
        $query = <<<EOD
        SELECT
        emailtoid, emailtodescription, display_order
             FROM EmailTo
        ORDER BY display_order;
EOD;

        $stmt = mysqli_prepare($db, $query);
        $records = array();
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            while ($row = mysqli_fetch_object($result)) {
                $record = new EmailTo($row->emailtoid, $row->emailtodescription);
                $records[] = $record;
            }
            mysqli_stmt_close($stmt);
            return $records;
        } else {
            throw new DatabaseSqlException("Query could not be executed: $query");
        }
    }

    function asArray() {
        return array("id" => $this->id,
            "name" => $this->name);
    }
}
?>