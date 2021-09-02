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
$tag_name = $params->tag_name;
$tag_color = $params->tag_color;

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
    if (isCreator($connection, $internal_id, $list_id)) {
      createListTag($connection, $list_id, $tag_name, $tag_color);
    } else {
      http_response_code(400);
      header('Content-Type: application/json');
      outputJSON('{"success": false, "error": "ERR_NOT_CREATOR", "message": "Cannot create tag; you are not creator of list."}');
    }
  } else {
    http_response_code(400);
    header('Content-Type: application/json');
    outputJSON('{"success": false, "error": "ERR_INVALID_LIST", "message": "List does not exist."}');
  }
}

function createListTag($connection, $list_id, $tag_name, $tag_color) {
  if ($statement = $connection->prepare('SELECT id FROM listtags WHERE (list_id = ? AND tag_color = ?)')) {

    $statement->bind_param('ss', $list_id, $tag_color);
    $statement->execute();
    $statement->store_result();

    if ($statement->num_rows > 0) {
      modifyListTag($connection, $list_id, $tag_name, $tag_color);
    } else {
      makeNewListTag($connection, $list_id, $tag_name, $tag_color);
    }
  } else {
    http_response_code(500);
    header('Content-Type: application/json');
    outputJSON('{"success": false, "error": "ERR_QUERY_FAIL", "message": "Unable to perform query."}');
  }
}

function modifyListTag($connection, $list_id, $tag_name, $tag_color) {
  if ($statement = $connection->prepare('UPDATE listtags SET tag_name = ? WHERE (tag_color = ? AND list_id = ?)')) {
    $statement->bind_param('sss', $tag_name, $tag_color, $list_id);
    $statement->execute();
    $statement->store_result();

    if (updateListModificationDate($connection, $list_id)) {
      http_response_code(200);
      header('Content-Type: application/json');
      outputJSON('{"success": true, "message": "Successfully updated tag."}');
    } else {
      http_response_code(500);
      header('Content-Type: application/json');
      outputJSON('{"success": false, "error": "ERR_LIST_MODIFY_DATE", "message": "Unable to update list modification date."}');
    }
  } else {
    http_response_code(500);
    header('Content-Type: application/json');
    outputJSON('{"success": false, "error": "ERR_QUERY_FAIL", "message": "Unable to perform query."}');
  }
}

function makeNewListTag($connection, $list_id, $tag_name, $tag_color) {
  if ($statement = $connection->prepare('INSERT INTO listtags (list_id, tag_name, tag_color) VALUES (?, ?, ?)')) {
    $statement->bind_param('sss', $list_id, $tag_name, $tag_color);
    $statement->execute();
    $statement->store_result();

    if (updateListModificationDate($connection, $list_id)) {
      http_response_code(200);
      header('Content-Type: application/json');
      outputJSON('{"success": true, "message": "Successfully created new tag."}');
    } else {
      http_response_code(500);
      header('Content-Type: application/json');
      outputJSON('{"success": false, "error": "ERR_LIST_MODIFY_DATE", "message": "Unable to update list modification date."}');
    }

  } else {
    http_response_code(500);
    header('Content-Type: application/json');
    outputJSON('{"success": false, "error": "ERR_QUERY_FAIL", "message": "Unable to perform query."}');
  }
}

function updateListModificationDate($connection, $list_id) {
  $currentTime = round(microtime(true) * 1000);
  if ($statement = $connection->prepare('UPDATE listdata SET last_modified = ? WHERE unique_id = ?')) {
    $statement->bind_param('is', $currentTime, $list_id);
    $statement->execute();
    $statement->store_result();
    return true;
  }
  return false;
}

$connection->close();
?>
