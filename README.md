# ArtisTree Graph Search
Project for CSCI373 (Advanced Web Tech)

Author: Oleg Silkin

## Info 

This is a website that implements a graph algorithm to aggregate artists with their collaborators.
The site first tries to search the local database for queries, if they aren't found then it tries to pull data using the Spotify API. 

### LAMP Stack:

- Linux: Ubuntu 20.04 LTS running in WSL
- Apache 2.4 
- MySQL 8.0
- PHP 7.4

## External Modules
This project made the use of the [PHP Spotify API Binding](https://github.com/jwilsson/spotify-web-api-php) to help access the Spotify API. 
If installing locally then you need to have composer installed and then run the following at the commandline:

`composer require jwilsson/spotify-web-api-php`


## Video Presentation:
(Should be viewed in a Chrome-based browser, Firefox seems to have an issue viewing .mkv files)

[Video Link](https://studentframingham-my.sharepoint.com/:v:/g/personal/osilkin_student_framingham_edu/Ee66N5jQSQJIjvIn0x1GCAcBau0GLDT2SSM2YeSCRBe7vw?e=m5xbfe)


## MySQL Database Dump
[Database File](./artistgraph.sql)
