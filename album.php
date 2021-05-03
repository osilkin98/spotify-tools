<!DOCTYPE html>
<html>
    <head>
        <?php include 'header.php'; ?>
        <?php 

            
            require_once 'backend/artistgraph.php';
            require_once 'backend/db.php';
            require_once 'vendor/autoload.php';

            // set debugging
            ini_set('display_errors', 1);
            ini_set('display_startup_errors', 1);
            error_reporting(E_ALL);
    
            // get page args 
            $albumId = urldecode($_GET['id']);

            $graph = init_graph();
            $album = $graph->get_album($albumId);

            echo "<title>$album->name</title>";
        ?>
    </head>
    <body>

        <?php include 'searchbar.php' ?>
        <div class="main">
            <div class="artist-section">
                <div class="artist-profile">
                    
                    <!-- generate artist page -->
                    <div class="artist-profile-image_container">
                        <?php 
                        $image_url = $album->get_largest_image()->url;
                        echo <<<EOL
                            <img class="artist-profile-img" src="$image_url">
                        EOL;
                        ?>
                    </div>
                    <div class="artist-profile-information">
                        <?php

                        echo <<<EOL
                            <h2>$album->name</h2>
                            <time class="black-text">$album->releaseDate</time>    
                            EOL;

                            echo "<ul class='tracklist'>";
                                // print out genres here
                            foreach ($album->tracks as $track) {
                                echo <<<EOL
                                <li><a href="track.php?id=$track->id"><span>$track->name</span></a></li>
                                EOL;
                                // formatting the commas
                            }

                            echo '</ul>';
                        ?>
                    </div>
                </div>
            </div>

            <!-- reuse css classes here cause the functionality is the same -->
            <div class="artist-profile_songs">
                <h3><?php echo "Artists That Appear on '$album->name':"; ?></h3>
                <div class="artist-profile_related">
                    <?php 
                       $artists = $graph->get_artists_on_album($album->id);
                        foreach($artists as $artist) 
                        {
                            echo <<<EOL
                            <div class="related-artist">
                                <a href="/artist.php?id=$artist->id">
                                <h4>$artist->name</h4>
                            EOL;
                            
                            if (0 < count($artist->images))
                            {
                                $url = $artist->get_smallest_image()['url'];
                                echo "<img class='related-artist-img' src='$url'>";
                            }
                            echo "</a></div>";
                        }
                    ?>
                </div>
            
            </div>

        </div>
    </body>
</html>