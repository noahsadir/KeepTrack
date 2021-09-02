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
$last_fetch = $params->last_fetch;

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
} else if (is_null($last_fetch)) {
  http_response_code(400);
  header('Content-Type: application/json');
  outputJSON('{"success": false, "error": "ERR_INVALID_PARAMETERS", "message": "Invalid name and/or description parameters."}');
} else {
  if (canViewList($connection, $internal_id, $list_id)) {
    fetchUpdatedListItems($connection, $internal_id, $list_id, $last_fetch);
  } else {
    http_response_code(400);
    header('Content-Type: application/json');
    outputJSON('{"success": false, "error": "ERR_VIEW_NOT_PERMITTED", "message": "User is not permitted to view the list due to privacy settings."}');
  }

}

function fetchUpdatedListItems($connection, $internal_id, $list_id, $last_fetch) {
  if ($statement = $connection->prepare('SELECT item_id, title, notes, start_date, end_date, repeat_interval, remind_before, duration, priority, tag, creator, last_modified FROM listitems WHERE list_id = ?')) {
    $statement->bind_param('s', $list_id);
    $statement->execute();
    $statement->store_result();

    $output = '{';
    $statement->bind_result($item_id, $title, $notes, $start_date, $end_date, $repeat_interval, $remind_before, $duration, $priority, $tag, $creator, $last_modified);

    while ($statement->fetch()) {

      if ($last_modified >= $last_fetch) {

        //Get item data
        if (!is_null($title)) {
          $title = '"'.base64_decode($title).'"';
        } else {
          $title= "null";
        }
        if (!is_null($notes)) {
          $notes = '"'.base64_decode($notes).'"';
        } else {
          $notes = "null";
        }
        if (!is_null($tag)) {
          $tag = '"'.$tag.'"';
        } else {
          $tag = "null";
        }
        if (!is_null($creator)) {
          $creator = '"'.$creator.'"';
        } else {
          $creator = "null";
        }

        $start_date = returnNullStringIfEmpty($start_date);
        $end_date = returnNullStringIfEmpty($end_date);
        $repeat_interval = returnNullStringIfEmpty($repeat_interval);
        $remind_before = returnNullStringIfEmpty($remind_before);
        $duration = returnNullStringIfEmpty($duration);
        $priority = returnNullStringIfEmpty($priority);
        $last_modified = returnNullStringIfEmpty($last_modified);

        $itemData = validateJSON('{"title": '.$title.', "notes": '.$notes.', "start_date": '.$start_date.', "end_date": '.$end_date.', "repeat_interval": '.$repeat_interval.', "remind_before": '.$remind_before.', "duration": '.$duration.', "priority": '.$priority.', "tag": '.$tag.', "creator": '.$creator.', "list_id": "'.$list_id.'", "last_modified": '.$last_modified.'}');
        if (!is_null($itemData)) {
          $output = $output.'"'.$item_id.'":'.$itemData.',';
        } else {
          $output = $output.'"'.$item_id.'": null,';
        }
      }
    }

    //Remove last comma from array, if it exists.
    if (substr($output, -1) == ',') {
      $output = substr($output, 0, -1);
    }

    $output = $output.'}';

    http_response_code(200);
    header('Content-Type: application/json');
    outputJSON('{"success": true, "fetch_time": '.strval(round(microtime(true) * 1000)).', "list_items":'.$output.'}');
  } else {
    http_response_code(500);
    header('Content-Type: application/json');
    outputJSON('{"success": false, "error": "ERR_QUERY_FAIL", "message": "Unable to perform query."}');
  }
}

function returnNullStringIfEmpty($value) {
  if (is_null($value)) {
    return "null";
  }
  return $value;
}

?>
