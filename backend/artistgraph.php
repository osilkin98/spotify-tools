<?php


require_once 'vendor/autoload.php';
require_once 'backend/artist.php';
require_once 'backend/album.php';
require_once 'backend/artist.php';
require_once 'backend/db.php';
require_once 'backend/track.php';


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


    function init_graph() : ArtistGraph 
    {
        $artistdb = ArtistDB::getInstance();
        $conn = $artistdb->getConnection();
        $api = new SpotifyWebAPI\SpotifyWebAPI();
        return new ArtistGraph($conn, $api);

    }


    class ArtistGraph {
        public mysqli $conn;
        public SpotifyWebAPI\SpotifyWebAPI $api;

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


        private function get_artist_from_db(string $artistName) : ?Artist {
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
        public function get_artist_from_db_by_id(string $artistId) : ?Artist {
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


        /** Gets the album from the database & returns 
         * @param string $albumId 
         * 
         * @return [type]
         */
        public function get_album(string $albumId) : ?Album {
            $album = $this->api->getAlbum($albumId);
            if($album != null) 
            {
                if (!$this->album_in_db($albumId)) {
                    $this->insert_album_into_db($album);
                }
                /* insert the album's songs into the database */
                foreach($album->tracks->items as $song) {
                    $this->insert_track($song);
                    $this->attach_track_to_album($song->id, $album->id, $song->track_number);
                    // should attach the artists here as well but w/e
                }
                
                return new Album($album->name, $album->id, $album->uri, $album->release_date, $album->total_tracks, $album->images, $album->tracks->items);
            }
        }


        /** Gets all collaborating artists on album - filters out duplicate values
         * @param string $albumId
         * 
         * @return array
         */
        public function get_artists_on_album(string $albumId) : array {
            $artists = array();

            $result = $this->api->getAlbumTracks($albumId);
            if (count($result->items)) 
            {
                foreach($result->items as $track)
                {
                    $this->insert_track($track);
                    $this->attach_track_to_album($track->id, $albumId, $track->track_number);
                    
                    foreach($track->artists as $artist) 
                    {
                        if (!array_search($artist->id, $artists))
                        {
                            $artists[$artist->id] = $this->get_artist_from_db_by_id($artist->id);
                        }
                    }
                }
            }
            return array_values($artists);
        }

        /** Stores song in database
         * @param object $song
         * 
         * @return [type]
         */
        private function insert_track(object $track) {
            $sql = "INSERT IGNORE INTO `track` (`id`, `name`, `uri`, `preview_url`) VALUES (?, ?, ?, ?)";
            if ($stmt = $this->conn->prepare($sql)) {
                $stmt->bind_param("ssss", $track->id, $track->name, $track->uri,$track->preview_url);
                $stmt->execute();
                $stmt->close();
                foreach($track->artists as $artist) 
                {
                    $this->attach_artist_to_track($track, $artist);
                }
            }
        }

        private function attach_artist_to_track(object $track, object $artist) 
        {
            // to ensure we have the correct images & genre
            if (!$this->artistid_in_db($artist->id))
            {
                $fullArtist = $this->api->getArtist($artist->id);
                $this->store_artist_in_db($fullArtist);
            }

            $sql = "INSERT IGNORE INTO `artists_on_track` (`artist_id`, `track_id`) VALUES (?, ?)";
            if ($stmt = $this->conn->prepare($sql))
            {
                $stmt->bind_param('ss', $artist->id, $track->id);
                $stmt->execute();
                $stmt->close();
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
        public function get_artist(string $artistName) : ?Artist {
            // get artist from API 
            $artists = $this->api->search($artistName, ["artist"]);
            
            // print_r($artists);
            // store artists in db just cause :-> 
            foreach($artists->artists->items as $artist) {
                if (!($this->artist_in_db($artistName, $this->conn))) {
                    $this->store_artist_in_db($artist);
                }
            }

            if (count($artists->artists->items)) {
                // relevant artists name 
                $relArtistName = $artists->artists->items[0]->name;
                return $this->get_artist_from_db($relArtistName);
            } 
            return null;
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

        
        /** Checks if album id is iin db
         * @param string $id
         * 
         * @return bool
         */
        private function album_in_db(string $id) : bool {
            $sql = "SELECT COUNT(*) AS `album_count` FROM `album` WHERE `id` = ?";
            if ($stmt = $this->conn ->prepare($sql)) {
                // bind the parameters now 
                $stmt->bind_param('s', $id);

                // execute 
                $stmt->execute();
                $stmt->bind_result($album_count);
                $stmt->fetch();
                $stmt->close();
                return $album_count > 0;
            } else {
                return false;
            }
        }

        private function track_in_db(string $trackId) : bool {
            $sql = "SELECT COUNT(*) AS `track_count` FROM `track` WHERE `id` = ?";
            if ($stmt = $this->conn ->prepare($sql)) {
                // bind the parameters now 
                $stmt->bind_param('s', $trackId);

                // execute 
                $stmt->execute();
                $stmt->bind_result($track_count);
                $stmt->fetch();
                $stmt->close();
                return $track_count > 0;
            } else {
                return false;
            }
        }

        public function get_related_artists(Artist $artist) : array {
            $relatedArtists = $this->api->getArtistRelatedArtists($artist->id);
            
            // echo json_encode($relatedArtists);
            
            $artistList = array();
            // add each artist to the db
            foreach($relatedArtists->artists as $related) {

                // add the artist to the database
                if (!$this->artistid_in_db($related->id)) {
                    $this->store_artist_in_db($related);
                }
                
                $relatedArtist = $this->get_artist_from_db_by_id($related->id);         

                array_push($artistList, $relatedArtist);

            }
            return $artistList;
        }


        private function insert_images(array $images, string $attachId) {
            $sql = "INSERT IGNORE INTO `image` (`id`, `height`, `width`, `url`) VALUES (?, ?, ?, ?)";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param('siis', $id, $height, $width, $url);

            // start bulk insertion
            $this->conn ->query("START TRANSACTION");
            foreach($images as $image) {
                $id = $attachId;
                $height = $image->height;
                $width = $image->width;
                $url = $image->url;
                
                $stmt->execute();
            }
            $stmt->close();
            $this->conn->query("COMMIT");
        }

        /** Insert the following albums into the database
         * @param object $albums
         * 
         * @return [type]
         */
        private function insert_album_into_db(object $album) {
            if (!$this->album_in_db($album->id))
            {
                $sql = "INSERT IGNORE INTO `album` (`id`, `name`, `total_tracks`, `release_date`, `uri`) VALUES (?, ?, ?, ?, ?)";
                if ($stmt = $this->conn->prepare($sql)) 
                {
                    $stmt->bind_param('ssiss', $album->id, $album->name, $album->total_tracks, $album->release_date, $album->uri);
                    $stmt->execute();
                    $stmt->close();

                    // insert images if any
                    if (0 < count($album->images)) {
                        $this->insert_images($album->images, $album->id);
                    }
                }
            }
        }

        private function attach_artist_to_album(Artist $artist, object $album) {
            $sql = "INSERT IGNORE INTO `appears_on` (`artist_id`, `album_id`) VALUES (?, ?)";
            if ($stmt = $this->conn->prepare($sql))
            {
                $stmt->bind_param("ss", $artist->id, $album->id);
                $stmt->execute();
                $stmt->close();
            }
        }

        /** Binds track to album
         * @param string $trackId
         * @param string $albumId
         * @param int $trackNo
         * 
         * @return [type]
         */
        private function attach_track_to_album(string $trackId, string $albumId, int $trackNo = 1) {
            $sql = "INSERT IGNORE INTO `track_on_album` (`track_id`, `album_id`, `track_no`) VALUES (?, ?, ?)";
            if ($stmt = $this->conn->prepare($sql))
            {
                $stmt->bind_param("ssi", $trackId, $albumId, $trackNo);
                $stmt->execute();
                $stmt->close();
            }
        }


        /** Gets the artists albums from the Spotify Database
         * @param Artist $artist
         * @param int $limit=10
         * @param mixed $offset=1
         * 
         * @return array
         */
        public function get_artist_albums(Artist $artist, int $limit=10, $offset=1) : array {
            $albums = $this->api->getArtistAlbums($artist->id, [
                "limit" => $limit,
                "offset" => $offset
            ]);
            
            foreach($albums->items as $album) {
                $this->insert_album_into_db($album);
                // bind artist to this database
                $this->attach_artist_to_album($artist, $album);
            }

            // fix this later
            return $albums->items;
        }


        public function get_track(string $trackId) {
            // only get track from spotify if not already in db 
            $track = $this->api->getTrack($trackId);
            if ($track->external_urls) {
                // make sure track gets inserted into the database
                $this->insert_track($track);
                try
                {
                    // get the album ID from the external link
                    $externalLink = $track->external_urls->spotify;
                    $strings = explode('/', $externalLink);
                    $lastId = count($strings) - 1;
                    $albumId = $strings[$lastId];
                    
                    // first insert the album into the database
                    $album = $this->get_album($albumId);
                    $this->insert_album_into_db($album);

                    // attach song to album 
                    $this->attach_track_to_album($trackId, $albumId);
                
                    echo "Artists on track: " . count($track->artists);

                    // attach artists to album 
                    foreach($track->artists as $artist) 
                    {
                        if (!$this->artistid_in_db($artist->id))
                        {
                            // get artist from API so we have images & genres
                            $fullArtist = $this->api->getArtist($artist->id);
                            $this->store_artist_in_db($fullArtist);
                        }
                        $this->attach_artist_to_album($artist, $album);
                    }
                }
                catch (\Throwable $th) {
                    echo "Couldn't retreive external URL from spotify<br>";
                }
            } 
            else 
            {
                echo "Track doesn't have an external URL<br>";
            }
            //
            // retrieve track info from DB 
            return $this->get_track_from_db($trackId);
        }



        /** Gets the track ID from the database if exists 
         * @param string $trackId
         * 
         * @return Artist or null 
         */
        private function get_track_from_db(string $trackId) : ?Track {
            $sql = "SELECT `id`, `name`, `uri`, `preview_url` FROM `track` WHERE `id` = ?";
            if ($stmt = $this->conn->prepare($sql))
            {
                $stmt->bind_param('s', $trackId);
                $stmt->execute();
                $stmt->bind_result($id, $name, $uri, $preview_url);
                $stmt->fetch(); 
                $stmt->close();

                if ($id != null) 
                {   
                    $trackArtists = $this->get_track_artists($trackId);
                    return new Track($name, $id, $uri, $preview_url, $trackArtists);
                }
                else {
                    return null;
                }
            }
        }

        /** Get artists on TrackId
         * @param string $trackId
         * 
         * @return array
         */
        public function get_track_artists(string $trackId) : array 
        {
            $artists = array();

            $sql = <<<EOD
            SELECT `artist`.`id` 
            FROM `artists_on_track`
            INNER JOIN `artist` ON `artist`.`id` = `artists_on_track`.`artist_id`
            WHERE `artists_on_track`.`track_id` = ?
            EOD;

            if ($stmt = $this->conn->prepare($sql))
            {
                $stmt->bind_param('s', $trackId);
                $stmt->execute();
                $stmt->bind_result($id);
                
                $artistIds = array();

                while($stmt->fetch())
                {
                    
                    array_push($artistIds, $id);
                }
                $stmt->close();

                foreach($artistIds as $artistId)
                {
                    array_push($artists, $this->get_artist_from_db_by_id($artistId));
                }
            }

            return $artists;
        }


        /** Gets all the artists appearing on the album 
         * @param string $albumId
         * 
         * @return array
         */
        public function get_album_artists(string $albumId) : array 
        {

            $sql = "SELECT `artist_id` FROM `appears_on` WHERE `album_id` = ?";
            $artists = array();
            if ($stmt = $this->conn->prepare($sql))
            {
                $artistIds = array();
                $stmt->bind_param('s', $albumId);
                $stmt->execute();
                $stmt->bind_result($artistId);
                while($stmt->fetch()) 
                {
                    array_push($artistIds, $artistId);
                }
                $stmt->close();

                foreach($artistIds as $artistId) 
                {
                    array_push($artists, $this->get_artist_from_db_by_id($artistId));
                }
            }
            return $artists;
        } 


        /** Stores the provided artist in the database if they don't exist
         * @param object $artist The Artist object as provided from spotify
         * 
         * @return bool
         */
        public function store_artist_in_db(object $artist) : bool {
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
                    $this->insert_images($artist->images, $artist->id);
                }
            
                return true;
            }
            return false;
        } 

        /** Gets the album information from the provided TrackId
         * @param string $trackId
         * 
         * @return Album
         */
        public function get_album_from_track(string $trackId) {
            $sql = "SELECT `album`.`id`, `album`.`name`, `album`.`release_date`, `album`.`uri`, `album`.`total_tracks` FROM `track_on_album` INNER JOIN `album` ON `track_on_album`.`album_id` = `album`.`id` WHERE `track_on_album`.`track_id` = ?";
            if ($stmt = $this->conn->prepare($sql))
            {
                $stmt->bind_param('s', $trackId);
                $stmt->execute();
                $stmt->bind_result($albumId, $name, $releaseDate, $uri, $totalTracks);
                $stmt->fetch();
                $stmt->close();
                
                if ($albumId != null)
                {
                    $album = new Album($name, $albumId, $uri, $releaseDate, $totalTracks);
                    $album->images = $this->get_images_for_id($albumId);
                    
                    $tracklist = array();

                    // get tracklist
                    $sql = <<<EOD
                    SELECT `track`.`id`, `track`.`name`, `track`.`uri`, `track`.`preview_url` 
                    FROM `track_on_album` 
                    INNER JOIN `track` ON `track`.`id` = `track_on_album`.`track_id`
                    WHERE `track_on_album`.`album_id` = ?
                    EOD;

                    $stmt = $this->conn->prepare($sql);
                    $stmt->bind_param('s', $albumId);
                    $stmt->execute();
                    $stmt->bind_result($trackId, $name, $uri, $preview_url);

                    while($stmt->fetch())
                    {
                        array_push($tracklist, new Track($name, $trackId, $uri, $preview_url));
                    }

                    
                    return $album;
                }
            }
        }
    }
?>  