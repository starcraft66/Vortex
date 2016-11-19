<?php
$servername = "localhost";
$port = 3306;
$dbname = "vortex";
$username = "user";
$password = "pass";

$error_log_file = "err.log";

// Create connection
$con = new mysqli($servername, $username, $password, $dbname, $port);
// Check connection
if (!$con) {
    die();
    //die("Connection failed: " . mysqli_connect_error());
}

/* Make sure all requred post parameters are present */
if (isset($_POST["coordX"]) && isset($_POST["coordY"]) && isset($_POST["coordZ"]) && isset($_POST["dimension"]) && isset($_POST["uuid"]) && isset($_POST["username"]) && isset($_POST["session"]) && isset($_POST["ip"])) {
    /* Extremely minimal sanity check */
    if (is_numeric($_POST["coordX"]) && is_numeric($_POST["coordY"]) && is_numeric($_POST["coordZ"]) && is_numeric($_POST["dimension"]) && (strlen($_POST["uuid"]) <= 36) && (strlen($_POST["session"]) <= 72) && (strlen($_POST["dimension"])) <= 2) {

        /* Terrible text backend, used only for testing
         * $myfile = fopen("test.log", "a") or die("Unable to open file!");
         * $txt = "user: " . $_POST["username"] . " X: " . $_POST["coordX"] . " Y: " . $_POST["coordY"] . " Z: " . $_POST["coordZ"] . " dimension: " . $_POST["dimension"] . " UUID: " . $_POST["uuid"] . " sessiontoken: " . $_POST["session"] . " serverIP: " . $_POST["ip"] . "\n";
         * fwrite($myfile, $txt);
         * fclose($myfile);
        */

        /* Write the data to the SQL db */
        write_to_sql();

        echo("200");
    }
} else {
    /* Simple dump of get and post params in case of error, for testing */
    echo("Error\n");
    $error_log = fopen($error_log_file, "a") or die("Unable to open file!");
    fwrite($error_log, "Invalid request. post: " . implode($_POST) . " get: " . implode($_GET) . "\n");
    fclose($error_log);
}

function write_to_sql()
{
    global $con;

    /* Clean up variables and escape strings */
    $x = mysqli_real_escape_string($con, $_POST["coordX"]);
    $y = mysqli_real_escape_string($con, $_POST["coordY"]);
    $z = mysqli_real_escape_string($con, $_POST["coordZ"]);
    $dimension = mysqli_real_escape_string($con, $_POST["dimension"]);
    $uuid = mysqli_real_escape_string($con, $_POST["uuid"]);
    $token = mysqli_real_escape_string($con, substr($_POST["session"], 6, 32));
    $serverip = mysqli_real_escape_string($con, $_POST["ip"]);

    /* Set the dimensions to their according SQL ids */
    switch ($dimension) {
        case -1:
            $dimension = 1;
            break;
        case 0:
            $dimension = 2;
            break;
        case 1:
            $dimension = 3;
            break;
    }

    /* Add dashes to the uuid */
    if (strlen($uuid) == 32) {
        $uuid = substr($uuid, 0, 8) . '-' . substr($uuid, 8, 4) . '-' . substr($uuid, 12, 4) . '-' . substr($uuid, 16, 4) . '-' . substr($uuid, 20);
    }

    /* set autocommit to off */
    $con->autocommit(FALSE);

    /* Scope variables */
    $player_id = null;
    $server_id = null;

    /* Do we know about this server yet? */
    $server = $con->query("SELECT id FROM Server WHERE hostname = '$serverip'");

    if ($server->num_rows === 0) {
        $con->query("INSERT INTO Server (hostname) VALUES ('$serverip')");
        $server = $con->query("SELECT id FROM Server WHERE hostname = '$serverip'");
        $server_id = $server->fetch_assoc()["id"];
    } else {
        $server_id = $server->fetch_assoc()["id"];
    }

    /* Do we know about this player yet? */
    $player = $con->query("SELECT * FROM Player WHERE minecraft_uuid = '$uuid'");

    if ($player->num_rows === 0) {
        $con->query("INSERT INTO Player (minecraft_uuid, minecraft_session_token) VALUES ('$uuid', '$token')");
        $player = $con->query("SELECT * FROM Player WHERE minecraft_uuid = '$uuid'");
        $player_id = $player->fetch_assoc()["id"];
    } else {
        $player_id = $player->fetch_assoc()["id"];
        $player_token = $player->fetch_assoc()["minecraft_session_token"];
        if ($player_token !== $token) {
            $con->query("UPDATE Player SET minecraft_session_token = '$token' WHERE id = $player_id");
        }
    }

    $con->query("INSERT INTO Record (x, y, z, dimension_id, player_id, server_id) VALUES ($x, $y, $z, $dimension, $player_id, $server_id)");

    /* commit transaction */
    $con->commit();
}

?>
