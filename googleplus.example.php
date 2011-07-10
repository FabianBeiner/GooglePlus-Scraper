<?php
    header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>GooglePlus-Scraper - by Fabian Beiner</title>
  <style>
    body {
      background-color:#ebebeb;
      color:#111;
      font-family:Corbel, "Lucida Grande", "Lucida Sans Unicode", "Lucida Sans", "DejaVu Sans", "Bitstream Vera Sans", "Liberation Sans", Verdana, sans-serif;
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
  <link rel="alternate" type="application/rss+xml" title="G+ Posts RSS 2.0" href="feed.php" />
</head>
<body>
<?php
    require_once 'googleplus.class.php';

    $arrTests = array('https://plus.google.com/117918784076966584831/posts', 'https://plus.google.com/107117483540235115863/posts', 'https://plus.google.com/106189723444098348646/posts', 'https://plus.google.com/111091089527727420853/posts', '109813896768294978296', '109412257237874861202');

    foreach ($arrTests as $strTest) {
        $oGooglePlus = new GooglePlus($strTest);

        if ($oGooglePlus->isReady) {
            echo '<h1>Parsing Google+ profile of “' . $oGooglePlus->get('name') . '”</h1>';
            if (($strDesc = $oGooglePlus->get('description')) && ($strDesc != 'n/A')) {
                echo '<h2>' . $strDesc . '</h2>';
            }
            echo '<p><img src="' . $oGooglePlus->get('image') . '" width="100" height="100" alt="Profile Image" style="float:right;"></p>';
            echo '<p><b>Google+ id:</b> ' . $oGooglePlus->get('id') . '</p>';
            echo '<p><b>Google+ profile url:</b> <a href="' . $oGooglePlus->get('url') . '">' . $oGooglePlus->get('url') . '</a></p>';
            echo '<p><b>First name:</b> ' . $oGooglePlus->get('firstname') . ' <b>Last name:</b> ' . $oGooglePlus->get('lastname') . ' <b>Nickname:</b> ' . $oGooglePlus->get('nickname') . ' <b>Other names:</b> ' . $oGooglePlus->get('othernames') . '</p>';
            echo '<p><b>Occupation:</b> ' . $oGooglePlus->get('occupation') . '</p>';
            echo '<p><b>Introduction:</b> ' . $oGooglePlus->get('introduction') . '</p>';
            echo '<p><b>Links:</b> ';
            if ($oGooglePlus->get('links') == 'n/A') {
                echo 'n/A';
            } else {
                foreach ($oGooglePlus->get('links') as $arrLink) {
                    echo '<a href="' . $arrLink[1] . '">' . $arrLink[0] . '</a> ';
                }
            }
            echo '</p>';
            echo '<p><b>Posts as Plain (max. 200 chars):</b></p>';
            if ($oGooglePlus->get('plainposts') != 'n/A') {
                foreach ($oGooglePlus->get('plainposts') as $arrLink) {
                    echo '<p>' . GooglePlus::getShortText($arrLink[0], 200, true) . ' <a href="' . $arrLink[1] . '">Read full post!</a></p>';
                }
            }
            echo '<hr style="clear:right;">';
        }
    }
?>
