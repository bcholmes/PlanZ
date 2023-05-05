<?php

class EmailHistory {

    public static function write($db, $emailToAddress, $name, $emailFrom, $emailCC, $emailReplyTo, $subject, $statusCode) {
        $badgeName = $name == null ? null : $name->getBadgeName();
        $emailCCAddress = $emailCC == null ? null : $emailCC->address;
        $emailReplyToAddress = $emailReplyTo == null ? null : $emailReplyTo->address;
        $sql = "INSERT INTO EmailHistory(emailto, `name`, emailfrom, emailcc, emailreplyto, emailsubject, `status`) VALUES (?, ?, ?, ?, ?, ?, ?);";

        $stmt = mysqli_prepare($db, $sql);
        mysqli_stmt_bind_param($stmt, "ssssssi", $emailToAddress, $badgeName, $emailFrom->address, $emailCCAddress, $emailReplyToAddress, $subject, $statusCode);
        if ($stmt->execute()) {
            mysqli_stmt_close($stmt);
        } else {
            throw new DatabaseSqlException("There was a problem with the update: $sql");
        }
    }
}

?>