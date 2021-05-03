<!DOCTYPE html>
<html>
    <head>
        <?php include 'header.php' ?>
        <?php
        
        
            // set debugging
            ini_set('display_errors', 1);
            ini_set('display_startup_errors', 1);
            error_reporting(E_ALL);
    

            include_once 'backend/artistgraph.php';
            $id = $_GET["id"];
            $graph = init_graph();
            $track = $graph->get_track($id);

            if ($track != null)
            {
            echo "<title>$track->name</title>";
            }

        ?>
    </head>
    <body>
        <?php include 'searchbar.php' ?>
        <div class="main">
            <div class="artist-section">
                <div class="artist-profile">
                    <div class="song-info">
                    <?php 
                                        
                    if($track != null)
                    {
                        
                        $album = $graph->get_album_from_track($track->id);
                        if ($album != null)
                        {
                            // $id = $_GET['id'];
                            // at this point the track will already be in the db so no need to worry lol
                            // $track = $graph->get_track($id);
                            // print_r($album)
                            if (0 < count($album->images)) 
                            {
                                $image_url = $album->images[1]['url'];
                                echo <<<EOL
                                <div class="artist_img">
                                    <img class="artist-profile-img" src="$image_url">
                                </div>
                                EOL;
                            }

                            // print_r($track);

                            /* display information about the song as well as a preview link */
                        }

                        echo <<<EOL
                        <div class="artist-info">
                        <h2 class="black-text">$track->name</h2>
                        EOL;
                        
                        echo '<div class="song-artists"><i>';

                        $i = 0;
                        foreach($track->artists as $artist)
                        {
                            echo "$artist->name";
                            $i++;
                            if ($i < count($track->artists))
                            {
                                echo ", ";
                            }
                        }

                        echo "</i></div>";
                        
                    }
                    ?>

                    </div>
                </div>

            </div>


            <!-- reuse css classes here cause the functionality is the same -->
            <div class="artist-profile_songs">
                <h3><?php echo "Artists That Appear on '$track->name':"; ?></h3>
                <div class="artist-profile_related">
                    <?php 
                        foreach($track->artists as $artist) 
                        {
                            echo <<<EOL
                            <div class="appears-artist">
                                <a href="/artist.php?id=$artist->id">
                                <h4>$artist->name</h4>
                            EOL;
                            
                            if (0 < count($artist->images))
                            {
                                // get biggest image
                                $url = $artist->images[count($artist->images)-1]['url'];
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