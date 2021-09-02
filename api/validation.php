<?php

function verifyAPIKey($connection, $apiKey) {
  if ($statement = $connection->prepare('SELECT id FROM apikeys WHERE api_key = ?')){

    $statement->bind_param('s', $apiKey);
    $statement->execute();
    $statement->store_result();

    if ($statement->num_rows > 0){
      return true;
    }
  }
  return false;
}

function verifyToken($connection, $internal_id, $token) {
  if ($statement = $connection->prepare('SELECT token, expiration FROM usertokens WHERE internal_id = ?')){

    $statement->bind_param('s', $internal_id);
    $statement->execute();
    $statement->store_result();

    if ($statement->num_rows > 0){
      $statement->bind_result($fetchedToken, $expiration);
      $statement->fetch();

      if (password_verify($token, $fetchedToken)) {
        return true;
      }
    }
  }
  return false;
}

//Check if list exists
function listExists($connection, $list_id) {
  if ($statement = $connection->prepare('SELECT id FROM listdata WHERE unique_id = ?')){

    $statement->bind_param('s', $list_id);
    $statement->execute();
    $statement->store_result();

    if ($statement->num_rows > 0){
      return true;
    }
  }
  return false;
}

//Check if list is private
function listIsPrivate($connection, $list_id) {
  if ($statement = $connection->prepare('SELECT private FROM listdata WHERE unique_id = ?')){

    $statement->bind_param('s', $list_id);
    $statement->execute();
    $statement->store_result();

    if ($statement->num_rows > 0){
      $statement->bind_result($is_private);
      $statement->fetch();
      return $is_private;
    }
  }
  return false;
}

function userIsCreator($connection, $list_id, $internal_id) {
  if ($statement = $connection->prepare('SELECT creator FROM listdata WHERE unique_id = ?')) {

    $statement->bind_param('s', $list_id);
    $statement->execute();
    $statement->store_result();

    if ($statement->num_rows > 0) {
      $statement->bind_param($creator_id);
      $statement->fetch();

      if ($creator_id == $internal_id) {
        return true;
      }
    }
  }
  return false;
}

//Check if user was invited to join a list
function userWasInvited($connection, $internal_id, $list_id) {
  if ($statement = $connection->prepare('SELECT id FROM listinvites WHERE (user_id = ? AND list_id = ?)')){

    $statement->bind_param('ss', $internal_id, $list_id);
    $statement->execute();
    $statement->store_result();

    if ($statement->num_rows > 0){
      return true;
    }
  }
  return false;
}

//User can view list if they're a member of it or the list is publicly visible (not private)
function canViewList($connection, $internal_id, $list_id) {
  if ($statement = $connection->prepare('SELECT can_edit FROM listaccess WHERE (user_id = ? AND list_id = ?)')){
    $statement->bind_param('ss', $internal_id, $list_id);
    $statement->execute();
    $statement->store_result();

    if ($statement->num_rows > 0){
      return true;
    }
  } else if ($statement = $connection->prepare('SELECT is_private FROM listdata WHERE list_id = ?')){
    $statement->bind_param('s', $list_id);
    $statement->execute();
    $statement->store_result();

    if ($statement->num_rows > 0){
      $statement->bind_result($is_private);
      $statement->fetch();
      if (!$is_private) {
        return true;
      }
    }
  }

  return false;
}

function isCreator($connection, $internal_id, $list_id) {
  if ($statement = $connection->prepare('SELECT is_creator FROM listaccess WHERE (user_id = ? AND list_id = ?)')){
    $statement->bind_param('ss', $internal_id, $list_id);
    $statement->execute();
    $statement->store_result();

    if ($statement->num_rows > 0){
      $statement->bind_result($is_creator);
      $statement->fetch();

      if ($is_creator) {
        return true;
      }
    }
  }
  return false;
}

//User can edit list if it is a member with is_edit permission
function canEditList($connection, $internal_id, $list_id) {
  if ($statement = $connection->prepare('SELECT can_edit FROM listaccess WHERE (user_id = ? AND list_id = ?)')){
    $statement->bind_param('ss', $internal_id, $list_id);
    $statement->execute();
    $statement->store_result();

    if ($statement->num_rows > 0){
      $statement->bind_result($can_edit);
      $statement->fetch();
      if ($can_edit) {
        return true;
      }
    }
  }
  return false;
}


function joinList($connection, $internal_id, $list_id, $can_edit, $is_creator) {
  if ($statement = $connection->prepare('INSERT INTO listaccess (list_id, user_id, can_edit, is_creator) VALUES (?, ?, ?, ?)')) {
    $statement->bind_param('ssii', $list_id, $internal_id, $can_edit, $is_creator);
    $statement->execute();
    $statement->store_result();
    return true;
  }
  return false;
}

//Ensures JSON string is valid
function validateJSON($input) {
  $json = json_decode($input);
  if ($json) {
    return $input;
  }
  return null;
}

function outputJSON($output) {
  $json = json_decode($output);
  if ($json && isset($json->success)) {
    echo($output);
  } else {
    http_response_code(500);
    header('Content-Type: application/json');
    echo('{"success": false, "error": "ERR_NOT_JSON", "message": "Unable to return valid JSON."}');
  }
}

function isBase64($stringVal) {
  if (base64_encode(base64_decode($stringVal, true)) === $stringVal) {
    return true;
  }
  return false;
}

function generateRandomToken($length) {
  $bytes = random_bytes($length * 2);
  $str = base64_encode($bytes);
  return str_replace("/","$",str_replace("+", "#", substr($str, 0, $length)));
}
?>
