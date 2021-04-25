<?php


class Artist {
    public string $name;
    public string $uri;
    public string $id;
    public string $href;
    public int $popularity;

    // optional attributes may be used for rendering 
    public array $images;
    public array $genres;

    public function __construct(string $name, string $id, string $uri, int $popularity, string $href) {
        $this->name = $name;
        $this->id = $id;
        $this->uri = $uri;
        $this->href = $href;
        $this->popularity = $popularity;
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
}


?>