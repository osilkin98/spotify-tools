<?php


class Album {
    public string $name;
    public string $uri;
    public string $id;
    public string $releaseDate;
    public int $totalTracks;

    // optional attributes may be used for rendering 
    public array $images;
    public array $tracks;

    public function __construct(string $name, string $id, string $uri, string $releaseDate, int $totalTracks, array $images=[], array $tracks=[]) {
        $this->name = $name;
        $this->id = $id;
        $this->uri = $uri;
        $this->releaseDate = $releaseDate;
        $this->totalTracks = $totalTracks;
        $this->images = $images;
        $this->tracks = $tracks;
    }

    public function get_smallest_image() {
        $smallest = PHP_INT_MAX;
        $smallestImage = null;
        foreach($this->images as $image) {
            if ($image['width'] < $smallest) {
                $smallest = $image['width'];
                $smallestImage = $image;
            } 
        }
        return $smallestImage;
    }

    public function get_largest_image() {
        $largestHeight = 0;
        $largestWidth = 0;
        $largestImage = null;
        foreach($this->images as $image) 
        {
            if($image->width * $image->height >= $largestHeight * $largestWidth) 
            {
                $largestWidth = $image->width;
                $largestHeight = $image->height;
                $largestImage = $image;
            }
        }
        
        return $largestImage;
    }
}

    
?>