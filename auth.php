<?php declare(strict_types=1)

    require 'vendor/autoload.php';

    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL); 


    function connect_to_db() : mysqli {
        // connect to db
        $servername = "localhost";
        $username = "oleg";
        $password = "oleggo123";
        $dbname = "artistgraph";
        $conn = new mysqli($servername, $username, $password, $dbname);

        // check connection
        if ($conn -> connect_error) {
            die("Connection failed: " . $conn -> connect_error);
        }
        return $conn;
    }

    function get_client_keys(mysqli $conn) : array {

        $client_id = '';
        $client_secret = '';
        
        // get the client id and secret 
        $sql = "SELECT `name`, `value` FROM `variable_store` WHERE `name` = 'client_id' or `name` = 'client_secret'";
        $result = $conn->query($sql);


        // dumbest method of retrieving methods ive ever written
        while($row = $result->fetch_assoc()) {
            if($row['name'] == 'client_id') {
                $client_id = $row['value'];
            }
            else if ($row['name'] == 'client_secret') {
                $client_secret = $row['value'];
            }
        }

        return array(
            'client_id' => $client_id,
            'client_secret' => $client_secret
        );
    }


    function update_access_key(mysqli $conn, array $client_keys) : bool {
        // obtain the access token 
        $session = new SpotifyWebAPI\Session($client_keys['client_id'], $client_keys['client_secret']);
        $session->requestCredentialsToken();
        $accessToken = $session->getAccessToken();

        // enter access token into db
        $sql = "INSERT INTO `variable_store` VALUES ('access_token', '$accessToken') ON DUPLICATE KEY UPDATE `name`='access_token', `value`='$accessToken'"; 
        return $conn->query($sql);
    }


    $conn = connect_to_db();
    $client_keys = get_client_keys($conn);
    $updated = update_access_key($conn, $client_keys);


    if($conn -> close())
    {
        echo "Closed db";
    } else{
        echo "Failed to close db";
    }
    
    if ($updated) { 
        // pass artist_name into app 
        header('location: app.php?artist_name=' . urlencode($_POST['artist_name']));
        die();
    } else {
        die('Could not update the access key');
    }
?>