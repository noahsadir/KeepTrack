<?php
require_once 'db.php';
require_once 'validation.php';

//Decode JSON POST request
$json = file_get_contents('php://input');
$params = json_decode($json);

//POST Parameters
$internal_id = $params->internal_id;
$token = $params->token;
$list_id = $params->list_id;

//Connect to database
$connection = mysqli_connect($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);

if (mysqli_connect_errno()){
  http_response_code(500);
  header('Content-Type: application/json');
  outputJSON('{"success": false, "error": "ERR_DATABASE_ACCESS", "message": "Unable to access database."}');
} else if (!verifyToken($connection, $internal_id, $token)) {
  http_response_code(401);
  header('Content-Type: application/json');
  outputJSON('{"success": false, "error": "ERR_UNAUTHORIZED", "message": "Unable to authenticate."}');
} else if (!$list_id) {
  http_response_code(400);
  header('Content-Type: application/json');
  outputJSON('{"success": false, "error": "ERR_INVALID_PARAMETERS", "message": "Invalid list ID."}');
} else {
  if (listExists($connection, $list_id)) {
    leaveExistingList($connection, $internal_id, $list_id);
  } else {
    http_response_code(400);
    header('Content-Type: application/json');
    outputJSON('{"success": false, "error": "ERR_INVALID_LIST", "message": "List does not exist."}');
  }

}

function leaveExistingList($connection, $internal_id, $list_id) {
  if ($statement = $connection->prepare('SELECT is_creator FROM listaccess WHERE (user_id = ? AND list_id = ?)')){
    $statement->bind_param('ss', $internal_id, $list_id);
    $statement->execute();
    $statement->store_result();

    if ($statement->num_rows > 0){
      $statement->bind_result($is_creator);
      $statement->fetch();

      if ($is_creator) {
        http_response_code(400);
        header('Content-Type: application/json');
        outputJSON('{"success": false, "error": "ERR_IS_CREATOR", "message": "Unable to leave list; user is the creator."}');
      } else {
        leaveList($connection, $internal_id, $list_id);
      }
    } else {
      http_response_code(400);
      header('Content-Type: application/json');
      outputJSON('{"success": false, "error": "ERR_NOT_JOINED", "message": "User is not registered with list."}');
    }
  } else {
    http_response_code(500);
    header('Content-Type: application/json');
    outputJSON('{"success": false, "error": "ERR_QUERY_FAIL", "message": "Unable to perform query."}');
  }
}

function leaveList($connection, $internal_id, $list_id) {
  if ($statement = $connection->prepare('DELETE FROM listaccess WHERE (user_id = ? AND list_id = ?)')) {
    $statement->bind_param('ss', $internal_id, $list_id);
    $statement->execute();
    $statement->store_result();

    http_response_code(200);
    header('Content-Type: application/json');
    outputJSON('{"success": true, "message": "Successfully left list."}');
  } else {
    http_response_code(500);
    header('Content-Type: application/json');
    outputJSON('{"success": false, "error": "ERR_UNABLE_TO_LEAVE", "message": "Unable to leave list."}');
  }
}

?>
