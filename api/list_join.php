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
    if (!listIsPrivate($connection, $list_id) || userWasInvited($connection, $list_id, $internal_id)) {
      joinExistingList($connection, $internal_id, $list_id);
    } else {
      http_response_code(400);
      header('Content-Type: application/json');
      outputJSON('{"success": false, "error": "ERR_NOT_INVITED", "message": "User does not have invitation to this private list."}');
    }
  } else {
    http_response_code(400);
    header('Content-Type: application/json');
    outputJSON('{"success": false, "error": "ERR_INVALID_LIST", "message": "List does not exist."}');
  }
}

function joinExistingList($connection, $internal_id, $list_id) {
  if ($statement = $connection->prepare('SELECT id FROM listaccess WHERE (user_id = ? AND list_id = ?)')){

    $statement->bind_param('ss', $internal_id, $list_id);
    $statement->execute();
    $statement->store_result();

    if ($statement->num_rows > 0){
      http_response_code(400);
      header('Content-Type: application/json');
      outputJSON('{"success": false, "error": "ERR_ALREADY_JOINED", "message": "User has already joined the list."}');
    } else {
      if (joinList($connection, $internal_id, $list_id, true, false)) {
        http_response_code(200);
        header('Content-Type: application/json');
        outputJSON('{"success": true, "message": "Successfully joined list."}');
      } else {
        http_response_code(500);
        header('Content-Type: application/json');
        outputJSON('{"success": false, "error": "ERR_LIST_JOIN", "message": "User is unable to join list."}');
      }
    }
  } else {
    http_response_code(500);
    header('Content-Type: application/json');
    outputJSON('{"success": false, "error": "ERR_QUERY_FAIL", "message": "Unable to perform query."}');
  }
}

?>
