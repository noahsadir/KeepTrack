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
$title = $params->title;
$start_date = $params->start_date;
$notes = $params->notes; //optional
$tag = $params->tag; //optional
$end_date = $params->end_date; //optional
$duration = $params->duration; //optional
$repeat_interval = $params->repeat_interval; //optional
$remind_before = $params->remind_before; //optional
$priority = $params->priority; //optional

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
} else if (!$list_id || !$title) {
  http_response_code(400);
  header('Content-Type: application/json');
  outputJSON('{"success": false, "error": "ERR_INVALID_PARAMETERS", "message": "Invalid and/or missing parameters."}');
} else {
  $title = base64_encode($title);
  if (!is_null($notes)) {
    $notes = base64_encode($notes);
  }

  if (canEditList($connection, $internal_id, $list_id)) {
    createListItem($connection, $internal_id, $list_id, $title, $notes, $tag, $start_date, $end_date, $duration, $repeat_interval, $remind_before, $priority);
  } else {
    http_response_code(400);
    header('Content-Type: application/json');
    outputJSON('{"success": false, "error": "ERR_EDIT_NOT_PERMITTED", "message": "User is not permitted to edit list."}');
  }

}

function createListItem($connection, $internal_id, $list_id, $title, $notes, $tag, $start_date, $end_date, $duration, $repeat_interval, $remind_before, $priority) {
  $unique_id = generateRandomToken(16);
  if ($statement = $connection->prepare('INSERT INTO listitems (item_id, list_id, title, notes, tag, start_date, end_date, repeat_interval, remind_before, duration, priority, creator, last_modified) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)')) {
    $statement->bind_param('sssssiiiiiisi', $unique_id, $list_id, $title, $notes, $tag, $start_date, $end_date, $repeat_interval, $remind_before, $duration, $priority, $internal_id, round(microtime(true) * 1000));
    $statement->execute();
    $statement->store_result();

    if (itemExists($connection, $unique_id)) {
      http_response_code(200);
      header('Content-Type: application/json');
      outputJSON('{"success": true, "message": "Successfully created item."}');
    } else {
      http_response_code(500);
      header('Content-Type: application/json');
      outputJSON('{"success": false, "error": "ERR_NOT_CREATED", "message": "Could not find newly created item; creation was likely unsuccessful."}');
    }
  } else {
    http_response_code(500);
    header('Content-Type: application/json');
    outputJSON('{"success": false, "error": "ERR_QUERY_FAIL", "message": "Unable to perform query."}');
  }
}

function itemExists($connection, $item_id) {
  if ($statement = $connection->prepare('SELECT id FROM listitems WHERE item_id = ?')) {
    $statement->bind_param('s', $item_id);
    $statement->execute();
    $statement->store_result();

    if ($statement->num_rows > 0) {
      return true;
    }
  }
  return false;
}
?>
