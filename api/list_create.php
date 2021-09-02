<?php
require_once 'db.php';
require_once 'validation.php';

//Decode JSON POST request
$json = file_get_contents('php://input');
$params = json_decode($json);

//POST Parameters
$internal_id = $params->internal_id;
$token = $params->token;
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
} else if (!$name || !$description || !is_bool($private)) {
  http_response_code(400);
  header('Content-Type: application/json');
  outputJSON('{"success": false, "error": "ERR_INVALID_PARAMETERS", "message": "Invalid and/or missing parameters."}');
} else {
  createList($connection, $internal_id, $name, $description, $private);
}

function createList($connection, $internal_id, $name, $description, $private) {
  $unique_id = generateRandomToken(16);
  if ($statement = $connection->prepare('INSERT INTO listdata (unique_id, name, description, private, creator, creation_date, last_modified) VALUES (?, ?, ?, ?, ?, ?, ?)')) {
    $currentTime = round(microtime(true) * 1000);
    $statement->bind_param('sssisii', $unique_id, base64_encode($name), base64_encode($description), $private, $internal_id, $currentTime, $currentTime);
    $statement->execute();
    $statement->store_result();

    if (joinList($connection, $internal_id, $unique_id, true, true)) {
      http_response_code(200);
      header('Content-Type: application/json');
      outputJSON('{"success": true, "message": "Successfully created list."}');
    } else {
      http_response_code(500);
      header('Content-Type: application/json');
      outputJSON('{"success": false, "error": "ERR_LIST_JOIN", "message": "List created but user is unable to join."}');
    }

  } else {
    http_response_code(500);
    header('Content-Type: application/json');
    outputJSON('{"success": false, "error": "ERR_QUERY_FAIL", "message": "Unable to perform query."}');
  }
}
?>
