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

    public static function findById($db, $id) {
        $query = <<<EOD
        SELECT
            emailccid, description, display_order, emailaddress
             FROM EmailCC
            WHERE emailccid = ?
        ORDER BY display_order;
EOD;

        $stmt = mysqli_prepare($db, $query);
        mysqli_stmt_bind_param($stmt, "i", $id);
        $record = null;
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            while ($row = mysqli_fetch_object($result)) {
                $record = new EmailCC($row->emailccid, $row->description, $row->emailaddress);
            }
            mysqli_stmt_close($stmt);
            return $record;
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

    public static function findById($db, $id) {
        $query = <<<EOD
        SELECT
            emailfromid, emailfromdescription, display_order, emailfromaddress
        FROM EmailFrom
        WHERE emailfromid = ?
        ORDER BY display_order;
EOD;

        $stmt = mysqli_prepare($db, $query);
        mysqli_stmt_bind_param($stmt, "i", $id);
        $record = null;
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            while ($row = mysqli_fetch_object($result)) {
                $record = new EmailFrom($row->emailfromid, $row->emailfromdescription, $row->emailfromaddress);
            }
            mysqli_stmt_close($stmt);
            return $record;
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

class SimpleEmailTo {
    public $badgeId;
    public $name;
    public $address;

    function __construct($badgeId, $name, $address) {
        $this->badgeId = $badgeId;
        $this->name = $name;
        $this->address = $address;
    }
}

class EmailTo {
    public $id;
    public $name;
    public $query;

    function __construct($id, $name, $query) {
        $this->id = $id;
        $this->name = $name;
        $this->query = $query;
    }

    public static function findAll($db) {
        $query = <<<EOD
        SELECT
        emailtoid, emailtodescription, display_order, emailtoquery
             FROM EmailTo
        ORDER BY display_order;
EOD;

        $stmt = mysqli_prepare($db, $query);
        $records = array();
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            while ($row = mysqli_fetch_object($result)) {
                $record = new EmailTo($row->emailtoid, $row->emailtodescription, $row->emailtoquery);
                $records[] = $record;
            }
            mysqli_stmt_close($stmt);
            return $records;
        } else {
            throw new DatabaseSqlException("Query could not be executed: $query");
        }
    }

    public function resolveEmailAddresses($db) {
        $stmt = mysqli_prepare($db, $this->query);
        $records = array();
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            while ($row = mysqli_fetch_object($result)) {
                $emailAddress = $row->email;
                $badgeId = $row->badgeid;
                $name = PersonName::from($row);
                $records[] = new SimpleEmailTo($badgeId, $name, $emailAddress);
            }
            mysqli_stmt_close($stmt);
            return $records;
        } else {
            throw new DatabaseSqlException("Query could not be executed: $this->query");
        }
    }

    public static function findById($db, $id) {
        $query = <<<EOD
        SELECT
            emailtoid, emailtodescription, display_order, emailtoquery
        FROM EmailTo
        WHERE emailtoid = ?
        ORDER BY display_order;
EOD;

        $stmt = mysqli_prepare($db, $query);
        mysqli_stmt_bind_param($stmt, "i", $id);
        $record = null;
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            while ($row = mysqli_fetch_object($result)) {
                $record = new EmailTo($row->emailtoid, $row->emailtodescription, $row->emailtoquery);
            }
            mysqli_stmt_close($stmt);
            return $record;
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