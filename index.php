<!DOCTYPE html>
<html>
  <head>
    <meta charset="UTF-8">
    <link rel="stylesheet" type="text/css" href="style.css">
    <title>ArtisTree</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

  </head>
  <body>

    <div class="container">
      <div class="center">
      <center>
      <div class="main-website-logo">
        <span class="main-website-logo_left">Artis</span><span class="main-website-logo_right">Tree</span>
      </div>
      </center>
      <div class="input-field">
        <center>
          <form action="/search.php" method="GET">
            <input type="text" id="artist_name" placeholder="Artist Name" name="artist_name">
            <input type="submit" id="artist-field" value="Go">
          </form>
        </center>
      </div>
      </div>
    </div>
    <?php 
      // phpinfo();
    ?>
  </body>
</html>