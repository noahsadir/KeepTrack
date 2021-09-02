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
$item_id = $params->item_id;

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
} else if (is_null($list_id) || is_null($item_id)) {
  http_response_code(400);
  header('Content-Type: application/json');
  outputJSON('{"success": false, "error": "ERR_INVALID_PARAMETERS", "message": "Invalid and/or missing parameters."}');
} else {
  if (canEditList($connection, $internal_id, $list_id)) {
    if (itemExists($connection, $item_id)) {
      deleteListItem($connection, $internal_id, $list_id, $item_id);
    } else {
      http_response_code(400);
      header('Content-Type: application/json');
      outputJSON('{"success": false, "error": "ERR_ITEM_DOES_NOT_EXIST", "message": "The specified list item does not exist."}');
    }
  } else {
    http_response_code(400);
    header('Content-Type: application/json');
    outputJSON('{"success": false, "error": "ERR_EDIT_NOT_PERMITTED", "message": "User is not permitted to edit list."}');
  }
}

//list_id *technically* not needed, but additional measure for safety and security
function deleteListItem($connection, $internal_id, $list_id, $item_id) {
  if ($statement = $connection->prepare('DELETE FROM listitems WHERE (list_id = ? AND item_id = ?)')) {
    $statement->bind_param('ss', $list_id, $item_id);
    $statement->execute();
    $statement->store_result();

    if (!itemExists($connection, $item_id)) {
      http_response_code(200);
      header('Content-Type: application/json');
      outputJSON('{"success": true, "message": "Successfully deleted item."}');
    } else {
      http_response_code(500);
      header('Content-Type: application/json');
      outputJSON('{"success": false, "error": "ERR_ITEM_STILL_EXISTS", "message": "Item was still found; deletion was likely unsuccessful."}');
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
