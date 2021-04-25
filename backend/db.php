<?php 

    // singleton database class
    class ArtistDB {
        private static $instance = null;
        private mysqli $conn;

        private string $servername = "localhost";
        private string $username = "oleg";
        private string $password = "oleggo123";
        private string $dbname = "artistgraph";

        private function __construct()
        {
            echo "creating db<br>";
            $this->conn = new mysqli($this->servername, $this->username, $this->password, $this->dbname);

        }

        /** Destructor function for when the db is no longer being used
         * @return [type]
         */
        public function __destruct()
        {
            $this->conn->close();
            ArtistDB::$instance = null; // make sure to null out the instance
        }

        /** Main Constructor for this function
         * @return ArtistDB
         */
        public static function getInstance() : ArtistDB {
            if (!self::$instance) {
                self::$instance = new ArtistDB();
            }

            return self::$instance;
        }

        public function getConnection() : mysqli 
        {
            return $this->conn;
        }
    }    
    

?>