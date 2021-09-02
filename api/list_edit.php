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
$name = $params->name;
$description = $params->description;
$private = $params->private;

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
} else if (is_null($name) || is_null($description) || is_null($list_id) || !is_bool($private)) {
  http_response_code(400);
  header('Content-Type: application/json');
  outputJSON('{"success": false, "error": "ERR_INVALID_PARAMETERS", "message": "Invalid and/or missing parameters."}');
} else {
  if (listExists($connection, $list_id)) {
    if (canEditList($connection, $internal_id, $list_id)) {
      editList($connection, $internal_id, $list_id, $name, $description, $private);
    } else {
      http_response_code(400);
      header('Content-Type: application/json');
      outputJSON('{"success": false, "error": "ERR_EDIT_NOT_PERMITTED", "message": "User is not permitted to edit list."}');
    }
  } else {
    http_response_code(400);
    header('Content-Type: application/json');
    outputJSON('{"success": false, "error": "ERR_INVALID_LIST", "message": "List does not exist."}');
  }
}

function editList($connection, $internal_id, $list_id, $name, $description, $private) {

  if ($statement = $connection->prepare('UPDATE listdata SET name = ?, description = ?, private = ?, last_modified = ? WHERE unique_id = ?')) {
    $currentTime = round(microtime(true) * 1000);
    $statement->bind_param('ssiis', base64_encode($name), base64_encode($description), $private, $currentTime, $list_id);
    $statement->execute();
    $statement->store_result();

    http_response_code(200);
    header('Content-Type: application/json');
    outputJSON('{"success": true, "message": "Successfully updated list."}');

  } else {
    http_response_code(500);
    header('Content-Type: application/json');
    outputJSON('{"success": false, "error": "ERR_QUERY_FAIL", "message": "Unable to perform query."}');
  }
}
?>
