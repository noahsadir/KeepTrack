<?php
require_once 'db.php';
require_once 'validation.php';

//Decode JSON POST request
$json = file_get_contents('php://input');
$params = json_decode($json);

//POST Parameters
$internal_id = $params->internal_id;
$token = $params->token;
$user_id = $params->user_id;
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
    if (listIsPrivate($connection, $list_id)) {
      if (!userWasInvited($connection, $list_id, $user_id)) {
          if (isCreator($connection, $internal_id, $list_id)) {
            inviteToExistingList($connection, $user_id, $list_id);
          } else {
            http_response_code(400);
            header('Content-Type: application/json');
            outputJSON('{"success": false, "error": "ERR_NOT_CREATOR", "message": "Cannot invite; you are not creator of list."}');
          }
      } else {
        http_response_code(400);
        header('Content-Type: application/json');
        outputJSON('{"success": false, "error": "ERR_ALREADY_INVITED", "message": "User is already invited."}');
      }
    } else {
      http_response_code(400);
      header('Content-Type: application/json');
      outputJSON('{"success": false, "error": "ERR_LIST_IS_PUBLIC", "message": "List is public; no need to invite."}');
    }
  } else {
    http_response_code(400);
    header('Content-Type: application/json');
    outputJSON('{"success": false, "error": "ERR_INVALID_LIST", "message": "List does not exist."}');
  }
}

function inviteToExistingList($connection, $user_id, $list_id) {
  if ($statement = $connection->prepare('INSERT INTO listinvites (list_id, user_id) VALUES (?, ?)')){

    $statement->bind_param('ss', $list_id, $user_id);
    $statement->execute();
    $statement->store_result();

    http_response_code(200);
    header('Content-Type: application/json');
    outputJSON('{"success": true, "message": "Successfully invited user to join list."}');
  } else {
    http_response_code(500);
    header('Content-Type: application/json');
    outputJSON('{"success": false, "error": "ERR_QUERY_FAIL", "message": "Unable to perform query."}');
  }
}


?>
