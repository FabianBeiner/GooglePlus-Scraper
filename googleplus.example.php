<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>GooglePlus-Scraper - by Fabian Beiner</title>
  <style>
    body {
      background-color:#ebebeb;
      color:#111;
      font-family:Corbel, "Lucida Grande", "Lucida Sans Unicode",  "Lucida Sans", "DejaVu Sans", "Bitstream Vera Sans", "Liberation Sans", Verdana, sans-serif;
      font-size:14px;
      margin:20px auto;
      width:700px;
    }
    p {
      margin:0;
      padding:0;
      margin-bottom:5px;
    }
    hr {
      clear:both;
      margin:20px 0;
    }
  </style>
</head>
<body>
<?php
    require_once 'googleplus.class.php';

    $arrTests = array('https://plus.google.com/107117483540235115863/posts', 'https://plus.google.com/106189723444098348646/posts', 'https://plus.google.com/111091089527727420853/posts', '109813896768294978296', '109412257237874861202');

    foreach ($arrTests as $strTest) {
        $oGooglePlus = new GooglePlus($strTest);

        if ($oGooglePlus->isReady) {
            echo '<h1>Parsing Google+ profile of “' . $oGooglePlus->getName() . '”</h1>';
            echo '<p>Profile: <a href="https://plus.google.com/' . $oGooglePlus->strProfileId . '/posts">https://plus.google.com/' . $oGooglePlus->strProfileId . '/posts</a><b></b><br>Introduction: <b>' .  $oGooglePlus->getIntroduction() . '</b><br>Occupation: <b>' . $oGooglePlus->getOccupation() . '</b></p><hr>';
            $arrPosts = $oGooglePlus->getPosts();
            $arrLinks = $oGooglePlus->getLinks();
            $i        = 0;
            foreach ($arrPosts as $strPost) {
                // Found a link?
                if ($arrLinks[$i]) {
                    echo '<a href="https://plus.google.com/' . $oGooglePlus->strProfileId . '/posts/' . $arrLinks[$i] . '">' . $strPost . '</a><hr>';
                }
                else {
                    echo $strPost . '<hr>';
                }
                $i++;
            }
            $i++;
        }
    }
?>
</body>
</html>
