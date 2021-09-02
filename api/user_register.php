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
  registerUser($connection, $email, $password);
}

function registerUser($connection, $email, $password) {
  //Make a query searching for members with generated ID (prevents duplicates)
  if ($statement = $connection->prepare('SELECT id FROM users WHERE email = ?')) {

    $statement->bind_param('s', $email);
    $statement->execute();
    $statement->store_result();

    if ($statement->num_rows > 0){ //Member already exists
      http_response_code(400);
      header('Content-Type: application/json');
      outputJSON('{"success": false, "error": "ERR_EMAIL_EXISTS", "message": "Unable to create user; email already registered."}');
    }else{
      //Member doesn't exist; hash password and add member to database
      $generated_id = generateRandomToken(16);
      $hashed_pass = password_hash($password, PASSWORD_DEFAULT);
      mysqli_query($connection, "INSERT INTO users (internal_id, email, password) VALUES ('$generated_id', '$email', '$hashed_pass')");
      http_response_code(200);
      header('Content-Type: application/json');
      outputJSON('{"success": true, "message": "Successfully created user."}');
    }
  } else {
    http_response_code(500);
    header('Content-Type: application/json');
    outputJSON('{"success": false, "error": "ERR_QUERY_FAIL", "message": "Unable to perform query."}');
  }
}

$connection->close();
 ?>
