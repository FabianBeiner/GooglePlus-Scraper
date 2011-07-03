<?php
/**
 * GooglePlus-Scraper -- a PHP Google+ scraper
 *
 * This class can be used to retrieve data from a Google+ profile with PHP.
 * It is rather a proof of concept than something for productive use.
 *
 * The technique used is called “web scraping”
 * (see http://en.wikipedia.org/wiki/Web_scraping for details). That means:
 * If Google+ changes anything on their HTML, the script is going to fail.
 *
 *
 * Can't wait to get my fingers on the official API. :)
 *
 *
 * @author     Fabian Beiner <mail@fabian-beiner.de>
 * @copyright  2011 -- Fabian Beiner
 * @license    Creative Commons Attribution-NonCommercial-ShareAlike 3.0 Germany
 *             (http://creativecommons.org/licenses/by-nc-sa/3.0/de/deed.en)
 * @link       http://fabian-beiner.de
 * @version    1.0
*/

class GooglePlusException extends Exception {}

class GooglePlus {
    // Define what to return if something is not found.
    public $strNotFound = 'n/A';
    // Define a timeout (in seconds) for the request of the Google+ page.
    const GPLUS_TIMEOUT = 15;
    // Set this to 'true' for debugging purposes only.
    const GPLUS_DEBUG   = false;

    // Regular expressions. Don't touch them, unless you know, what you're doing!
    const GPLUS_CONTENT      = '~<div class="a-f-i-p-r"><div class="a-f-i-p-qb a-b-f-i-p-qb"><div class="(?:a-b-f-i-u-ki a-f-i-u-ki|a-b-f-i-p-xb a-f-i-p-xb)"><div(?:.*)>(.*)</div></div>~Ui';
    const GPLUS_INTRODUCTION = '~<div class="a-c-B-F-Oa d-s-r note">(.*)</div>~Ui';
    const GPLUS_NAME         = '~<div class="a-c-da-G a-c-nc-M-i a-b-c-da-T-mb"><span class="fn">(.*)</span></div>~Ui';
    const GPLUS_OCCUPATION   = '~<div class="a-c-B-F-Oa d-s-r title">(.*)</div>~Ui';
    const GPLUS_PROFILE_ID   = '~(\d{21})~Ui';
    const GPLUS_PROFILE_URL  = '~https://plus.google.com/(\d{21}|\w+)\/~Ui';

    // cURL cookie file.
    private $_fCookie         = NULL;
    // Google+ url.
    private $_strUrl          = NULL;
    // Google+ source for posts.
    private $_strSourcePosts  = NULL;
    // Google+ source for about.
    private $_strSourceAbout  = NULL;
    // Fetching successful?
    public $isReady           = false;
    // Google+ profile id/name.
    public $strProfileId      = NULL;

    /**
     * GooglePlus constructor.
     *
     * @param string  $strProfile The url/id of the Google+ profile.
     * @param integer $intCache   The maximum age (in minutes) of the cache (default 4 hours).
     */
    public function __construct($strProfile, $intCache = 240) {
        // This script requires cURL.
        if (!function_exists('curl_init')) {
            throw new GooglePlusException('cURL not installed. See http://php.net/manual/en/book.curl.php for details.');
        }
        // Check if debug should be enabled or not.
        if (GooglePlus::GPLUS_DEBUG) {
            error_reporting(-1);
            ini_set('display_errors', 1);
        }
        else {
            error_reporting(0);
            ini_set('display_errors', 0);
        }
        // Set the global cache timeout (fallback to 4 hours if the value is crap).
        $this->_intCache = (int)$intCache || 240;
        // Fetch the HTML of the page.
        if (!GooglePlus::fetchUrl($strProfile)) {
            throw new GooglePlusException('Error fetching the HTML of this Google+ profile.');
        }
    }

    /**
     * Regular expressions helper function.
     *
     * @param  string  $strContent The content to search in.
     * @param  string  $strRegex   The regular expression.
     * @param  integer $intIndex   The index to return.
     * @return string  The match found.
     * @return array   The matches found.
     */
    private function matchRegex($strContent, $strRegex, $intIndex = null) {
        $arrMatches = null;
        preg_match_all($strRegex, $strContent, $arrMatches);
        if (GooglePlus::GPLUS_DEBUG) {
            //echo '<pre style="font-size:10px">--- DEBUG' . "\n";
            //print_r($arrMatches);
            //echo '--- /DEBUG</pre>';
        }
        if ($arrMatches === FALSE) return false;
        if ($intIndex != null && is_int($intIndex)) {
            if ($arrMatches[$intIndex]) {
              return $arrMatches[$intIndex][0];
            }
            return false;
        }
        return $arrMatches;
    }

    /**
     * Returns a shortened text.
     *
     * @param string  $strText   The text to shorten.
     * @param integer $intLength The new length of the text.
     */
    public function getShortText($strText, $intLength = 100, $bolDots = false) {
        $strText = trim($strText) . ' ';
        $strText = substr($strText, 0, $intLength);
        $strText = substr($strText, 0, strrpos($strText, ' '));
        return ($bolDots) ? $strText . '&hellip;' : $strText;
    }

    /**
     * Fetch the HTML of thhe Google+ profile.
     *
     * @param  string  $strProfile The url of the Google+ profile
     * @param  string  $strWhat Either 'posts' or 'about' (Google+ pages)
     * @return boolean
     */
    private function fetchUrl($strProfile, $strWhat = 'posts') {
        // Remove whitespaces and possible HTML stuff.
        $strProfile = trim(strip_tags((string)$strProfile));

        // Find a proper Google+ id.
        $this->strProfileId = $this->matchRegex($strProfile, GooglePlus::GPLUS_PROFILE_ID, 1);
        if (!$this->strProfileId) {
            $this->strProfileId = $this->matchRegex($strProfile, GooglePlus::GPLUS_PROFILE_URL);
        }
        if (!$this->strProfileId) {
            throw new GooglePlusException('Unable to find a Google+ id.');
        }

        // Set the URL.
        $this->_strUrl = 'https://plus.google.com/' . $this->strProfileId . '/posts';

        // Cookie path.
        if (function_exists('sys_get_temp_dir')) {
            $this->_fCookie = tempnam(sys_get_temp_dir(), 'GooglePlus');
        }

        // Initialize and run the request.
        $oCurl = curl_init($this->_strUrl);
        curl_setopt_array($oCurl, array (
                                        CURLOPT_VERBOSE => false
                                       ,CURLOPT_HEADER => true
                                       ,CURLOPT_FRESH_CONNECT => true
                                       ,CURLOPT_RETURNTRANSFER => true
                                       ,CURLOPT_TIMEOUT => GooglePlus::GPLUS_TIMEOUT
                                       ,CURLOPT_CONNECTTIMEOUT => 0
                                       ,CURLOPT_REFERER => 'http://www.google.com'
                                       ,CURLOPT_USERAGENT, 'Googlebot/2.1 (+http://www.google.com/bot.html)'
                                       ,CURLOPT_FOLLOWLOCATION => false
                                       ,CURLOPT_COOKIEFILE => $this->_fCookie
                                       ,CURLOPT_SSL_VERIFYPEER => false
                                        ));
        $strSource  = curl_exec($oCurl);
        $intCurlErr = curl_errno($oCurl);
        $strCurlErr = curl_error($oCurl);
        curl_close($oCurl);

        // Remove cookie.
        if ($this->_fCookie) {
            unlink($this->_fCookie);
        }

        if ($intCurlErr > 0) {
            $this->isReady = false;
            throw new GooglePlusException('cURL error (' . $intCurlErr . '):' .  $strCurlErr);
        }
        else {
            $this->isReady = true;
        }

        // Remove line breaks.
        if ($strWhat == 'about') {
            $this->_strSourceAbout = preg_replace('~(\r|\n|\r\n)~', '', $strSource);
        } else {
            $this->_strSourcePosts = preg_replace('~(\r|\n|\r\n)~', '', $strSource);
        }

        return true;
    }

    public function getName() {
        if ($this->isReady) {
            if ($strName = $this->matchRegex($this->_strSourcePosts, GooglePlus::GPLUS_NAME, 1)) {
                return trim($strName);
            }
            return $this->strNotFound;
        }
        return $this->strNotFound;
    }

    public function getPosts() {
        if ($this->isReady) {
            if ($arrFetch = $this->matchRegex($this->_strSourcePosts, GooglePlus::GPLUS_CONTENT)) {
                $arrReturn = array();
                foreach ($arrFetch[1] as $strPost) {
                    $strPost = str_replace('<br>', ' ', $strPost);
                    $strPost = html_entity_decode(strip_tags($strPost), ENT_QUOTES);
                    $arrReturn[] = $strPost;
                }
                return $arrReturn;
            }
            return $this->strNotFound;
        }
        return $this->strNotFound;
    }

    public function getLinks() {
        if ($this->isReady) {
            // Dirty dirty… This fails sometimes.
            $arrPosts  = $this->getPosts();
            $arrReturn = array();
            $i         = 0;
            foreach ($arrPosts as $strPost) {
                $strRegex  = '~,"(?:' . $this->getShortText($strPost, 30) . '.*)","' . $this->strProfileId . '/posts/(\w+)",0~Ui';
                if ($strUrl = $this->matchRegex($this->_strSourcePosts, $strRegex)) {
                    if (isset($strUrl[1][0])) {
                        $arrReturn[$i] = $strUrl[1][0];
                    }
                    else {
                        $arrReturn[$i] = NULL;
                    }
                    if (isset($arrReturn[$i-1]) && ($arrReturn[$i-1] == $arrReturn[$i])) {
                        $arrReturn[$i-1] = NULL;
                    }
                }
                else {
                    $arrReturn[$i] = $this->strNotFound;
                }
                $i++;
            }
            return $arrReturn;
        }
        return $this->strNotFound;
    }

    public function getIntroduction() {
        if (!$this->_strSourceAbout) {
            $this->fetchUrl($this->strProfileId, 'about');
        }
        if ($this->isReady) {
            if ($strReturn = $this->matchRegex($this->_strSourceAbout, GooglePlus::GPLUS_INTRODUCTION, 1)) {
                return trim(strip_tags($strReturn));
            }
            return $this->strNotFound;
        }
        return $this->strNotFound;
    }

    public function getOccupation() {
        if (!$this->_strSourceAbout) {
            $this->fetchUrl($this->strProfileId, 'about');
        }
        if ($this->isReady) {
            if ($strReturn = $this->matchRegex($this->_strSourceAbout, GooglePlus::GPLUS_OCCUPATION, 1)) {
                return trim(strip_tags($strReturn));
            }
            return $this->strNotFound;
        }
        return $this->strNotFound;
    }

}
