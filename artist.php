<!DOCTYPE html>
<html>

<head>
    <?php include 'header.php'; ?>

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


        $artistGraph = init_graph();

        $artist = $artistGraph->get_artist_from_db_by_id($artistId);
    ?>
        <title><?php echo $artist->name; ?></title>
    </head>

    <body>

    <?php include 'searchbar.php' ?>
    <div class="main">
        <div class="artist-section">
            <div class="artist-profile">
                
                <!-- generate artist page -->
                <div class="artist-profile-image_container">
                    <?php 

                    $image_url = $artist->get_largest_image()['url'];

                    echo <<<EOL
                        <img class="artist-profile-img" src="$image_url">
                    EOL;
                    ?>
                </div>
                <div class="artist-profile-information">
                    <?php

                    echo <<<EOL
                        <h2>$artist->name</h2>
                        <p>Popularity: <i>$artist->popularity</i></p>    
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
                    ?>

                    
                </div>
            </div>
                
            <div class="artist-profile_songs">
                <h3><?php echo "$artist->name's Albums" ?> <button type="button" class="collapsible" onclick="toggleAlbums('artist-albums')">+</button></h3>
                <div class="artist-albums">
                    
                    <?php 
                        $albums = $artistGraph->get_artist_albums($artist);
                        foreach($albums as $album)
                        {
                            echo <<<EOL
                                <div class="artist-album">
                                    <a href="/album.php?id=$album->id" >
                                    <h4>$album->name</h4>
                                    <p>$album->release_date</p>
                            EOL;

                        
                            // get album image
                            if (count($album->images)) 
                            {
                                // get the first image
                                $image = $album->images[0]->url;
                                echo "<img  class=\"artist-album-img\" src=\"$image\">";
                            }
                            // echo print_r($album->images[0]);
                            echo "</a></div>";
                            // 
                            # echo json_encode($albums);
                        }
                    
                    ?>
                </div>
            </div>

            <div class="artist-profile_songs">
                <h3><?php echo "Artists Similar to $artist->name" ?> <button type="button" class="collapsible" onclick="toggleAlbums('artist-profile_related')">+</button></h3>
                <div class="artist-profile_related">
                    <?php  
                        /* get related artists */
                        foreach($artistGraph->get_related_artists($artist) as $relatedArtist) 
                        {

                            echo <<<EOL
                                <div class="related-artist">
                                    <a href="/artist.php?id=$relatedArtist->id">
                                    <h4>$relatedArtist->name</h4>
                            EOL;
                            
                            if (count($relatedArtist->images) > 0) 
                            {
                                $url = $relatedArtist->get_smallest_image()['url'];
                                echo "<img class='related-artist-img' src='$url'>";
                            }
                            echo "</a></div>";
                        }
                    ?>                          
                </div>
            </div>
        </div>
    </div>
        <script>

            function toggleAlbums(className) {
                let content = document.getElementsByClassName(className)[0];
                content.style.display = content.style.display === "block" ? "none" : "block";
            }
        </script>
    </body>
</html>