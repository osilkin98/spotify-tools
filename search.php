<!DOCTYPE html>
<html>

<head>
    <!-- Include Style Sheets -->
    <link rel="stylesheet" type="text/css" href="style.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>

<body>


    <?php
    require_once 'vendor/autoload.php';
    require_once 'backend/artistgraph.php';
    require_once 'backend/db.php';


    /** Responsible for creating an artist listing 
     * @param Artist $artist
     * 
     * @return [type]
     */
    function display_artist(Artist $artist)
    {
        $image_url = $artist->get_smallest_image()['url'];
        echo <<<EOL
        <div class="artist-result" id="$artist->id">
            <div class="artist_img-container">
                <img class="artist_img" src="$image_url">
            </div>
            <div class="artist_info-container">
                <div class="artist-name">
                    <a href="/artist.php?id=$artist->id"><b>$artist->name</b></a>
                </div>
                <div><a href="$artist->href">Spotify Url</a></div>
                <i>Popularity: $artist->popularity</i><br>
        EOL;
        // print out genres here
        echo "<i>";
        $i = 0;
        foreach ($artist->genres as $genre) {
            echo $genre;
            // formatting the commas
            $i++;
            if ($i < sizeof($artist->genres)) {
                echo ", ";
            }
        }
        echo "</i>";

        // print out the artists button 

        echo <<<EOL

            </div>
                <br>
        </div>
        EOL;
    }

    /** Parent function which creates the main artist lists 
     * @param array $artists
     * 
     * @return [type]
     */
    function display_artist_list(array $artists)
    {
        echo "<ul class='artist_list'>";
        foreach ($artists as $artist) {
            echo "<li classname='artist_entry'>";
            display_artist($artist);
            echo "</li>";
        }
        echo "</ul>";
    }

    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);

    // get the artist_name from the url
    $artistname = urldecode($_GET['artist_name']);


    // get the database connection
    $artisdb = ArtistDB::getInstance();
    $conn = $artisdb->getConnection();

    // initialize the spotify api 
    $api = new SpotifyWebAPI\SpotifyWebAPI();
    // setup the graph API
    $artistGraph = new ArtistGraph($conn, $api);
    $artist = $artistGraph->get_artist($artistname);
    // display_artist($artist);


    $related = $artistGraph->get_related_artists($artist);
    // add first result to the related
    $related = array_merge([$artist,], $related);
    display_artist_list($related);



    ?>
</body>

</html>