<?php

require_once 'backend/album.php';
require_once 'backend/artist.php';

class Track 
{
    public string $name;
    public string $id;
    public string $uri;
    public string $preview_url;

    public array $artists;  // artists collabing on the song
    public Album $album;   // array

    public function __construct(string $name, string $id, string $uri, string $preview_url, array $artists = [], Album $album = null) 
    {   
        $this->name = $name;
        $this->id = $id;
        $this->uri = $uri;
        $this->preview_url = $preview_url;

        $this->artists = $artists;
        if ($album != null) {
            $this->album = $album;
        }
    }
}
?>