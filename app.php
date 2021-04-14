<html>
<body>

<?php
    require 'vendor/autoload.php';

        
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);

    // get the artist_name from the url
    $artistname = urldecode($_GET['artist_name']);


    // get access token 
    $servername = "localhost";
    $username = "oleg";
    $password = "oleggo123";
    $dbname = "artistgraph";
    $conn = new mysqli($servername, $username, $password, $dbname);

    // retrieve access token from db
    $sql = "SELECT `name`, `value` FROM `variable_store` WHERE `name` = 'access_token'";
    $result = $conn->query($sql);

    // no access token
    if ($result->num_rows == 0) {
        echo 'No Access Token';
        die('no access token');
    } 

    $row = $result -> fetch_assoc();
    $myAccessToken = $row['value'];

    // initialize the spotify api 
    $api = new SpotifyWebAPI\SpotifyWebAPI();
    $api->setAccessToken($myAccessToken);

    echo "Artist name: '$artistname'";

    print_r(
        $api->search($artistname, ["artist"])
    );

?>
</body>
</html>
