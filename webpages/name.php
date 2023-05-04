<?php

class PersonName {
    public $firstName;
    public $lastName;
    public $badgeName;
    public $pubsName;

    function getPubsName() {
        if ($this->pubsName) {
            return $this->pubsName;
        } else {
            return $this->getBadgeName();
        }
    }

    function getBadgeName() {
        if ($this->badgeName) {
            return $this->badgeName;
        } else {
            return $this->getFirstNameLastName();
        }
    }

    function getFirstNameLastName() {
        $result = "";
        if ($this->firstName) {
            $result = $this->firstName;
        }

        if ($this->lastName) {
            if (mb_strlen($result) != 0) {
                $result = $result . " ";
            }
            $result = $result . $this->lastName;
        }
        return $result;
    }

    static function from($dbRow) {
        $name = new PersonName();
        if (property_exists($dbRow, 'firstname')) {
            $name->firstName = $dbRow->firstname;
        }
        if (property_exists($dbRow, 'lastname')) {
            $name->lastName = $dbRow->lastname;
        }
        if (property_exists($dbRow, 'badgename')) {
            $name->badgeName = $dbRow->badgename;
        }
        if (property_exists($dbRow, 'pubsname')) {
            $name->pubsName = $dbRow->pubsname;
        }
        return $name;
    }

    function asArray() {
        return array(
            "badgeName" => $this->getBadgeName(),
            "pubsName" => $this->getPubsName(),
            "firstName" => $this->firstName,
            "lastName" => $this->lastName,
        );
    }
}

?>