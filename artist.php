<!DOCTYPE html>
<html>
    <?php
        // get the artist from the graph 

        require_once 'backend/artistgraph.php';
        require_once 'backend/db.php';
        require_once 'vendor/autoload.php';

        // set debugging
        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
        error_reporting(E_ALL);

        // get page args 
        $artistId = urldecode($_GET['id']);

        // get db & spotify api 
        $artistdb = ArtistDB::getInstance();
        $conn = $artistdb->getConnection();
        $api = new SpotifyWebAPI\SpotifyWebAPI();
        $artistGraph = new ArtistGraph($conn, $api);

        $artist = $artistGraph->get_artist_from_db_by_id($artistId);
    ?>
    <head>
        <?php include 'header.php'; ?>
        <title><?php echo $artist->name; ?></title>
    </head>

    <body>
        <div class="artist-page_container">
            <?php echo "$artist->name"; ?>
        </div>
    </body>
</html>