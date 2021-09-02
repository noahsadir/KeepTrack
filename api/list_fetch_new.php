<?php
require_once 'db.php';
require_once 'validation.php';

//Decode JSON POST request
$json = file_get_contents('php://input');
$params = json_decode($json);

//POST Parameters
$internal_id = $params->internal_id;
$token = $params->token;
$last_fetch = $params->last_fetch;

//Connect to database
$connection = mysqli_connect($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);

if (mysqli_connect_errno()) {
  http_response_code(500);
  header('Content-Type: application/json');
  outputJSON('{"success": false, "error": "ERR_DATABASE_ACCESS", "message": "Unable to access database."}');
} else if (!verifyToken($connection, $internal_id, $token)) {
  http_response_code(401);
  header('Content-Type: application/json');
  outputJSON('{"success": false, "error": "ERR_UNAUTHORIZED", "message": "Unable to authenticate."}');
} else if (is_null($last_fetch)) {
  http_response_code(400);
  header('Content-Type: application/json');
  outputJSON('{"success": false, "error": "ERR_INVALID_PARAMETERS", "message": "Invalid name and/or description parameters."}');
} else {
  fetchUpdatedLists($connection, $internal_id, $last_fetch);
}

function fetchUpdatedLists($connection, $internal_id, $last_fetch) {
  if ($statement = $connection->prepare('SELECT list_id FROM listaccess WHERE user_id = ?')) {
    $statement->bind_param('s', $internal_id);
    $statement->execute();
    $statement->store_result();
    $statement->bind_result($list_id);

    $output = '{';

    while ($statement->fetch()) {

      $listData = getUpdatedListData($connection, $list_id, $last_fetch);
      if (!is_null($listData)) {
        $output = $output.'"'.$list_id.'":'.$listData.',';
      }
    }

    //Remove last comma from array, if it exists.
    if (substr($output, -1) == ',') {
      $output = substr($output, 0, -1);
    }

    $output = $output.'}';

    http_response_code(200);
    header('Content-Type: application/json');
    outputJSON('{"success": true, "fetch_time": '.strval(round(microtime(true) * 1000)).', "lists":'.$output.'}');
  } else {
    http_response_code(500);
    header('Content-Type: application/json');
    outputJSON('{"success": false, "error": "ERR_QUERY_FAIL", "message": "Unable to perform query."}');
  }
}

function getUpdatedListData($connection, $list_id, $last_fetch) {
  if ($statement = $connection->prepare('SELECT last_modified, name, description, private, creator, creation_date FROM listdata WHERE unique_id = ?')) {
    $statement->bind_param('s', $list_id);
    $statement->execute();
    $statement->store_result();

    if ($statement->num_rows > 0) {
      $statement->bind_result($last_modified, $name, $description, $private, $creator, $creation_date);
      $statement->fetch();

      if ($last_modified >= $last_fetch) {
        return '{"name": "'.base64_decode($name).'", "description": "'.base64_decode($description).'","private": '.$private.',"creator": "'.$creator.'","creation_date": '.$creation_date.',"last_modified": '.$last_modified.',"tags":'.getTags($connection, $list_id).'}';
      }
    }
  }
  return null;
}

function getTags($connection, $list_id) {
  if ($statement = $connection->prepare('SELECT tag_name, tag_color FROM listtags WHERE list_id = ?')) {
    $statement->bind_param('s', $list_id);
    $statement->execute();
    $statement->store_result();
    $statement->bind_result($tag_name, $tag_color);

    $output = '{';

    while ($statement->fetch()) {
      $output = $output.'"'.$tag_color.'":"'.$tag_name.'",';
    }

    //Remove last comma from array, if it exists.
    if (substr($output, -1) == ',') {
      $output = substr($output, 0, -1);
    }

    $output = $output.'}';

    if (validateJSON($output)) {
      return $output;
    }
  }
  return "{}";
}

?>
