<?php


require 'vendor/autoload.php';
require 'backend/artist.php';

    /*
    class Artist {
        public string $name;
        public string $id;
        public string $uri; // spotify uri
        public int $popularity;
        public string $href; // a bit unnecessary 
        
        // todo: add image hrefs here, these should be embedded into main program

        public function __construct(string $name, string $id, string $uri, int $popularity, string $href) 
        {
            $this->name = $name;
            $this->id = $id;
            $this->uri = $uri;
            $this->popularity = $id;
            $this->href = $href;

        }
    }
    */

    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);


    class ArtistGraph {
        private mysqli $conn;
        private SpotifyWebAPI\SpotifyWebAPI $api;

        function __construct(mysqli $conn, SpotifyWebAPI\SpotifyWebAPI $api) 
        {
            $this->conn = $conn;
            $this->api = $api;

            // ensure that we have an access token 
            $accessToken = $this->retrieve_access_token();
            $this->api->setAccessToken($accessToken);
        }


        private function retrieve_access_token() : string {
            // retrieve access token from db
            $sql = "SELECT `value`, `last_updated` FROM `variable_store` WHERE `name` = 'access_token'";
            if ($stmt = $this->conn->prepare($sql)) 
            {
                $stmt->execute();
                $stmt->bind_result($accessToken, $lastUpdated);
                $stmt->fetch();
                $stmt->close();

                /*
                echo time() . " - " . strtotime($lastUpdated) . " =  " . (time() - strtotime($lastUpdated)); 
                echo "<br> $accessToken";
                */
                // access token needs to be refreshed 
                if (!$accessToken || 3600 <= time() - strtotime($lastUpdated)) {
                    $this->update_access_key();

                    $stmt = $this->conn->prepare($sql); 
                    $stmt->execute();
                    $stmt->bind_result($accessToken, $lastUpdated);
                    $stmt->fetch();
                    $stmt->close();
                }
                
                return $accessToken;
            }
        }

        /** Gets the client fkeys for the application
         * @return array client keys keyed in by `client_id`, `client_secret` 
         */
        private function get_client_keys() : array {
            $sql = "SELECT `name`, `value` FROM `variable_store` WHERE `name` = 'client_id' or `name` = 'client_secret'";
            $keys = array();
            if ($stmt = $this->conn->prepare($sql)) 
            {
                $stmt->execute();
                $stmt->bind_result($name, $value);
                while($stmt->fetch()) 
                {
                    $keys[$name] = $value;
                }
                $stmt->close();
            }
            return $keys;
        } 


        /** Updates Spotify Access Key
         * @return [type]
         */
        private function update_access_key() {
            $keys = $this->get_client_keys();
            $session = new SpotifyWebAPI\Session($keys['client_id'], $keys['client_secret']);
            $session->requestCredentialsToken();
            $accessToken = $session->getAccessToken();
            $sql = "INSERT INTO `variable_store` VALUES ('access_token', ?, CURRENT_TIMESTAMP()) ON DUPLICATE KEY UPDATE `name`='access_token', `value`=?, `last_updated`=CURRENT_TIMESTAMP()";
            if ($stmt = $this->conn->prepare($sql))
            {
                $stmt->bind_param("ss", $accessToken, $accessToken);
                $stmt->execute();
                $stmt->close();
            } 
        }


        private function get_artist_from_db(string $artistName) : Artist {
            // we have to get the artist whose name matches the string the closest 
            $sql = "SELECT `name`, `id`, `uri`, `popularity`, `href` FROM `artist` WHERE `name` LIKE ?";
            if ($stmt = $this->conn->prepare($sql)) {
                $stmt->bind_param('s', $artist_name);

                $artist_name = $artistName;

                $stmt->execute();
                $stmt->bind_result($name, $id, $uri, $popularity, $href);
                $stmt->fetch();

                $artist = new Artist($name, $id, $uri, $popularity, $href);
                $stmt->close();

                $artist->images = $this->get_images_for_id($artist->id);
                $artist->genres = $this->get_artist_genres_by_id($artist->id);

                // fetch artist images 
                return $artist;

            }
        }

        /** Get Artist Genres By ID
         * @param string $artistId
         * 
         * @return array
         */
        public function get_artist_genres_by_id(string $artistId) : array {
            $sql = "SELECT `genre` FROM `artist_genre` WHERE `artist_id` = ?";
            $genres = array();

            if ($stmt = $this->conn->prepare($sql)) {
                $stmt->bind_param('s', $artistId);
                $stmt->execute();
                $stmt->bind_result($genre);

                while ($stmt->fetch()) {
                    array_push($genres, $genre);
                }
                $stmt->close();
            }
            return $genres;
        }        

        /** Gets the artst from database by their ID
         * @param string $artistId artists ID
         * 
         * @return Artist
         */
        public function get_artist_from_db_by_id(string $artistId) : Artist {
            // we have to get the artist whose name matches the string the closest 
            $sql = "SELECT `name`, `id`, `uri`, `popularity`, `href` FROM `artist` WHERE `id` = ?";
            if ($stmt = $this->conn->prepare($sql)) {
                $stmt->bind_param('s', $artistId);

                $stmt->execute();
                $stmt->bind_result($name, $id, $uri, $popularity, $href);
                $stmt->fetch();

                $artist = new Artist($name, $id, $uri, $popularity, $href);
                $stmt->close();

                $artist->images = $this->get_images_for_id($artist->id);
                $artist->genres = $this->get_artist_genres_by_id($artist->id);
                // fetch artist images 
                return $artist;

            }
        }


        public function get_images_for_id(string $spotifyId) : array {
            $sql = "SELECT `height`, `width`, `url` FROM `image` WHERE `id` = ?";
            $images = array();

            if ($stmt = $this->conn->prepare($sql)) {
                $stmt->bind_param('s', $spotifyId);
                $stmt->execute();
                $stmt->bind_result($height, $width, $url);

                while ($stmt->fetch()) {
                    $image = array(
                        'url' => $url,
                        'height' => $height,
                        'width' => $width
                    );
                    array_push($images, $image);
                }
                $stmt->close();
            }
            return $images;
        }
        /** Checks if the provided artist is in the database already
         * @param string $artist Artist to search, name not normalized
         *  
         * @return bool
         */
        public function artist_in_db(string $artist) : bool {
            // setup the initial statement
            $sql = "SELECT COUNT(*) AS `artist_count` FROM `artist` WHERE `name` = ?";
            if ($stmt = $this->conn ->prepare($sql)) {
                // bind the parameters now 
                $stmt->bind_param('s', $artist_name);
                $artist_name = $artist;
                
                // execute 
                $stmt->execute();
                $stmt->bind_result($artist_count);
                $stmt->fetch();

                return $artist_count > 0;
            } else {
                return false;
            }
        }

        /** Retrives artists by the provided name. Attempts to search database before querying Spotify API
         * @param string $artistName
         * 
         * @return [type]
         */
        public function get_artist(string $artistName) : Artist {
            if (!($this->artist_in_db($artistName, $this->conn))) {
                // get artist from API 
                $artists = $this->api->search($artistName, ["artist"]);
                
                // store artists in db just cause :-> 
                foreach($artists->artists->items as $artist) {
                    $this->store_artist_in_db($artist);
                }
            } 

            return $this->get_artist_from_db($artistName);
            // get the artist from the database 

        }

        /** Checks if artist id is iin db
         * @param string $id
         * 
         * @return bool
         */
        public function artistid_in_db(string $id) : bool {
            $sql = "SELECT COUNT(*) AS `artist_count` FROM `artist` WHERE `id` = ?";
            if ($stmt = $this->conn ->prepare($sql)) {
                // bind the parameters now 
                $stmt->bind_param('s', $id);

                // execute 
                $stmt->execute();
                $stmt->bind_result($artist_count);
                $stmt->fetch();
                $stmt->close();
                return $artist_count > 0;
            } else {
                return false;
            }
        }

        public function get_related_artists(Artist $artist) : array {
            $relatedArtists = $this->api->getArtistRelatedArtists($artist->id);
            $artistList = array();
            // add each artist to the db
            foreach($relatedArtists->artists as $artist) {
                // add the artist to the database
                if (!$this->artistid_in_db($artist->id)) {
                    $this->store_artist_in_db($artist);
                }
                $relatedArtist = $this->get_artist_from_db_by_id($artist->id);
                array_push($artistList, $relatedArtist);

            }
            return $artistList;
        }

        public function get_artist_collabs(Artist $artist)  {
            // get music from artists 
        }


        /** Stores the provided artist in the database if they don't exist
         * @param object $artist The Artist object as provided from spotify
         * 
         * @return bool
         */
        public function store_artist_in_db(object $artist) : bool {
            /*
                {
                    "external_urls": {
                        "spotify": "https:\/\/open.spotify.com\/artist\/4u51rwAHPHpmoP5Z8pj1Qn"
                    },
                    "followers": {
                        "href": null,
                        "total": 3
                    },
                    "genres": [],
                    "href": "https:\/\/api.spotify.com\/v1\/artists\/4u51rwAHPHpmoP5Z8pj1Qn",
                    "id": "4u51rwAHPHpmoP5Z8pj1Qn",
                    "images": [
                        {
                            "height": 640,
                            "url": "https:\/\/i.scdn.co\/image\/ab67616d0000b273e0ad9fbd42cc9c6fab6aa187",
                            "width": 640
                        },
                        {
                            "height": 300,
                            "url": "https:\/\/i.scdn.co\/image\/ab67616d00001e02e0ad9fbd42cc9c6fab6aa187",
                            "width": 300
                        },
                        {
                            "height": 64,
                            "url": "https:\/\/i.scdn.co\/image\/ab67616d00004851e0ad9fbd42cc9c6fab6aa187",
                            "width": 64
                        }
                    ],
                    "name": "Offseth",
                    "popularity": 0,
                    "type": "artist",
                    "uri": "spotify:artist:4u51rwAHPHpmoP5Z8pj1Qn"
                }
            */
    
            // prepare the sql statement
            $sql = "INSERT IGNORE INTO `artist` (`id`, `name`, `uri`, `popularity`, `href`) VALUES (?, ?, ?, ?, ?)";
            if ($stmt = $this->conn->prepare($sql)) {
                $stmt->bind_param('sssis', $id, $name, $uri, $popularity, $href);
    
                $id = $artist->id;
                $name = $artist->name;
                $uri = $artist->uri;
                $popularity = $artist->popularity;
                $href = $artist->href;
                
                $stmt->execute();
    
                
                // insert genres if any 
                if (0 < count($artist->genres)) {
                    $sql = "INSERT IGNORE INTO `artist_genre` (`artist_id`, `genre`) VALUES (?, ?)";
                    $stmt = $this->conn->prepare($sql);
                    $stmt->bind_param('ss', $id, $genre);
                    $id = $artist -> id;
                    // transaction for bulk insert
                    $this->conn->query("START TRANSACTION");
                    foreach($artist -> genres as $genre) {
                            $stmt->execute();
                    }
                    $stmt->close();
                    $this->conn->query("COMMIT");
                }
    
                // insert images if any
                if (0 < count($artist->images)) {
                    $sql = "INSERT IGNORE INTO `image` (`id`, `height`, `width`, `url`) VALUES (?, ?, ?, ?)";
                    $stmt = $this->conn->prepare($sql);
                    $stmt->bind_param('siis', $id, $height, $width, $url);
    
                    // start bulk insertion
                    $this->conn ->query("START TRANSACTION");
                    foreach($artist->images as $image) {
                        $id = $artist->id;
                        $height = $image->height;
                        $width = $image->width;
                        $url = $image->url;
                        
                        $stmt->execute();
                    }
                    $stmt->close();
                    $this->conn->query("COMMIT");
                }
            
                return true;
            }
            return false;
        } 
    }
?>  