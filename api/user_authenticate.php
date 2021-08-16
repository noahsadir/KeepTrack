<?php
require_once 'db.php';
require_once 'validation.php';

//Decode JSON POST request
$json = file_get_contents('php://input');
$params = json_decode($json);

//POST Parameters
$apiKey = $params->api_key;
$email = $params->email;
$password = $params->password;

//Connect to database
$connection = mysqli_connect($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);

if (mysqli_connect_errno()){
  http_response_code(500);
  header('Content-Type: application/json');
  outputJSON('{"success": false, "error": "ERR_DATABASE_ACCESS", "message": "Unable to access database."}');
} else if (!verifyAPIKey($connection, $apiKey)) {
  http_response_code(400);
  header('Content-Type: application/json');
  outputJSON('{"success": false, "error": "ERR_INVALID_API_KEY", "message": "Invalid API Key."}');
} else if (!$email) {
  http_response_code(400);
  header('Content-Type: application/json');
  outputJSON('{"success": false, "error": "ERR_INVALID_EMAIL", "message": "Invalid Email."}');
} else if (!$password) {
  http_response_code(400);
  header('Content-Type: application/json');
  outputJSON('{"success": false, "error": "ERR_INVALID_PASSWORD", "message": "Invalid Password."}');
} else {
  authenticateUser($connection, $email, $password);
}

function authenticateUser($connection, $email, $password) {
  //Make a query searching for members with generated ID (prevents duplicates)
  if ($statement = $connection->prepare('SELECT internal_id, password FROM users WHERE email = ?')){

    $statement->bind_param('s', $email);
    $statement->execute();
    $statement->store_result();

    if ($statement->num_rows > 0){ //Member exists
      //Fetch member key
      $statement->bind_result($fetchedID, $fetchedPasswordHash);
      $statement->fetch();

      if (password_verify($password, $fetchedPasswordHash)) {
        generateToken($connection, $fetchedID);
      } else {
        http_response_code(400);
        header('Content-Type: application/json');
        outputJSON('{"success": false, "error": "ERR_INVALID_PASSWORD", "message": "Invalid password."}');
      }
    }else{
      http_response_code(400);
      header('Content-Type: application/json');
      outputJSON('{"success": false, "error": "ERR_NOT_REGISTERED", "message": "User is not currently registered."}');
    }
  } else {
    http_response_code(500);
    header('Content-Type: application/json');
    outputJSON('{"success": false, "error": "ERR_TABLE_ACCESS", "message": "Unable to access table."}');
  }
}

function generateToken($connection, $internal_id) {
  $token = generateRandomToken(64);
  $expiration = (time() + 1800) * 1000;

  //Delete existing member IDs
  if ($statement = $connection->prepare('SELECT id FROM usertokens WHERE internal_id = ?')){
    $statement->bind_param('s', $internal_id);
    $statement->execute();
    $statement->store_result();

    if ($statement->num_rows > 0){ //Member already has token
      mysqli_query($connection, "DELETE FROM usertokens WHERE internal_id = '$internal_id'");
    }
  }

  //Create new token
  $hashed_token = password_hash($token, PASSWORD_DEFAULT);
  mysqli_query($connection, "INSERT INTO usertokens (internal_id, token, expiration) VALUES ('$internal_id', '$hashed_token', '$expiration')");

  if (verifyToken($connection, $internal_id, $token)) {
    http_response_code(200);
    header('Content-Type: application/json');
    outputJSON('{"success": true, "message": "Successfully authenticated user.", "internal_id": "'.$internal_id.'", "token": "'.$token.'"}');
  } else {
    http_response_code(500);
    header('Content-Type: application/json');
    outputJSON('{"success": false, "error": "ERR_TOKEN_VERIFICATION", "message": "Unable to verify token."}');
  }
}




$connection->close();
 ?>
