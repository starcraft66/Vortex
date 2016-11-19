<?php
$myfile = fopen("import.txt", "r") or die("Unable to open file!");
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
$index = 0;
while (!feof($myfile)) {
    $input = explode(' ', fgets($myfile));

    if (sizeof($input) != 16) {
        continue;
    }
    /* Borderline scary data parsing */
    echo("Line " . $index . " length " . sizeof($input) . "\n");
    $x = mysqli_real_escape_string($con, $input[3]);
    $y = mysqli_real_escape_string($con, $input[5]);
    $z = mysqli_real_escape_string($con, $input[7]);
    $dimension = mysqli_real_escape_string($con, $input[9]);
    $uuid = mysqli_real_escape_string($con, $input[11]);
    $token = mysqli_real_escape_string($con, substr($input[13], 6, 32));
    $serverip = mysqli_real_escape_string($con, substr($input[15], 0, -1));
    $index++;

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
    $player_id = null;
    $server_id = null;
    /* Insert some values */
    $server = $con->query("SELECT id FROM Server WHERE hostname = '$serverip'");
    if ($server->num_rows === 0) {
        $con->query("INSERT INTO Server (hostname) VALUES ('$serverip')");
        $server = $con->query("SELECT id FROM Server WHERE hostname = '$serverip'");
        $server_id = $server->fetch_assoc()["id"];
        echo("got the server id (" . $server_id . ")\n");
    } else {
        $server_id = $server->fetch_assoc()["id"];
        echo("got the server id (" . $server_id . ")\n");
    }
    $player = $con->query("SELECT * FROM Player WHERE minecraft_uuid = '$uuid'");
    if ($player->num_rows === 0) {
        $con->query("INSERT INTO Player (minecraft_uuid, minecraft_session_token) VALUES ('$uuid', '$token')");
        echo("inserted the player id (" . $uuid . ")\n");
        $player = $con->query("SELECT * FROM Player WHERE minecraft_uuid = '$uuid'");
        $player_id = $player->fetch_assoc()["id"];
        echo("got the player id (" . $player_id . ")\n");
    } else {
        $player_id = $player->fetch_assoc()["id"];
        echo("got the player id (" . $player_id . ")\n");
        $player_token = $player->fetch_assoc()["minecraft_session_token"];
        if ($player_token !== $token) {
            $con->query("UPDATE Player SET minecraft_session_token = '$token' WHERE id = '$player_id'");
        }
    }

    $con->query("INSERT INTO Record (x, y, z, dimension_id, player_id, server_id) VALUES ('$x', '$y', '$z', '$dimension', '$player_id', '$server_id')");

    /* commit transaction */
    $con->commit();
}
fclose($myfile);

?>
