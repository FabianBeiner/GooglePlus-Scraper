<?php
/**
 * GooglePlus-Scraper -- a PHP Google+ scraper
 *
 * This class can be used to retrieve data from a Google+ profile with PHP.
 * It is rather a proof of concept than something for productive use.
 *
 * The technique used is called “web scraping”
 * (see http://en.wikipedia.org/wiki/Web_scraping for details). That means:
 * If Google+ changes anything on their HTML/JSON, the script is going to fail.
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
 * @version    1.1.0
*/

class GooglePlusException extends Exception {}

class GooglePlus {
    // Define what to return if something is not found.
    public $strNotFound = 'n/A';
    // Define a timeout (in seconds) for the request of the Google+ page.
    const GPLUS_TIMEOUT = 15;
    // Set this to 'true' for debugging purposes only.
    const GPLUS_DEBUG   = false;

    // cURL cookie file.
    private $_fCookie    = NULL;
    // Google+ url.
    private $_strUrl     = NULL;
    // Google+ source.
    private $_strSource  = NULL;
    // Google+ JSON.
    private $_objJson    = NULL;
    // Fetching successful?
    public $isReady      = false;

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
        $this->_intCache = (is_int($intCache)) ? $intCache : 240;
        // Fetch the HTML of the page.
        if (!GooglePlus::fetchUrl($strProfile)) {
            //throw new GooglePlusException('Error fetching the HTML of this Google+ profile.');
            echo 'Failed';
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
            echo '<pre style="font-size:10px">--- DEBUG' . "\n";
            var_dump($arrMatches);
            echo '--- /DEBUG</pre>';
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
    static function getShortText($strText, $intMaxLength = 100, $bolDots = false) {
        $strReturn = trim(strip_tags($strText)) . ' ';
        $strReturn = trim(preg_replace('/(http|https|ftp|ftps)\:\/\/[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,3}(\/\S*)?/', '', $strReturn));
        if (strlen($strReturn) <= $intMaxLength) {
            return $strReturn;
        }
        $strReturn = substr($strReturn, 0, $intMaxLength);
        $strReturn = trim(substr($strReturn, 0, strrpos($strReturn, ' ')));
        if ($bolDots && (strlen($strReturn) <= ($intMaxLength-1))) {
            return $strReturn . '…';
        }
        return $strReturn;
    }

    /**
     * Fetch the HTML of thhe Google+ profile.
     *
     * @param  string  $strProfile The url of the Google+ profile
     * @param  string  $strWhat Either 'posts' or 'about' (Google+ pages)
     * @return boolean
     */
    private function fetchUrl($strProfile) {
        // Remove whitespaces and possible HTML stuff.
        $strProfile = trim(strip_tags((string)$strProfile));

        // Find a proper Google+ id.
        $strProfileId = $this->matchRegex($strProfile, '~(\d{21})~Ui', 1);
        if (!$strProfileId) {
            $strProfileId = $this->matchRegex($strProfile, '~https://plus.google.com/(\d{21}|\w+)\/~Ui');
        }
        if (!$strProfileId) {
            throw new GooglePlusException('Unable to find a Google+ id.');
        }

        // Simple cache handling.
        $strCache = getcwd() . '/cache/' . $strProfileId . '.json';
        if (file_exists($strCache)) {
            $intChanged = filemtime($strCache);
            $intNow     = time();
            $intDiff    = $intNow - $intChanged;
            $intCache   = $this->_intCache * 60;
            if ($intCache >= $intDiff) {
                $this->_objJson = json_decode(file_get_contents($strCache), true);
                if ($this->_objJson) {
                    $this->isReady = true;
                    return true;
                }
            }
        }

        // Set the URL.
        $this->_strUrl = 'https://plus.google.com/' . $strProfileId . '/posts';

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
            throw new GooglePlusException('cURL error (' . $intCurlErr . '):' .  $strCurlErr);
        }

        // Remove line breaks.
        $this->_strSource = preg_replace('~(\r|\n|\r\n)~', '', $strSource);

        $intStart = strpos($this->_strSource, 'var OZ_initData = ');
        $intEnd   = strpos($this->_strSource, ";window.jstiming.load.tick('idp');</script>", $intStart);
        $strJson  = substr($this->_strSource, ($intStart + 18), -(strlen($this->_strSource) - $intEnd));

        // This paves the way to coding hell.
        $strJson = preg_replace('~(,){2,}~', ',', $strJson);
        $strJson = str_replace('[,', '[', $strJson);

        file_put_contents($strCache, $strJson);
        $this->_objJson = json_decode($strJson, true);
        if ($this->_objJson) {
            $this->isReady = true;
            return true;
        }
        return false;
    }

    public function get($strWhat) {
        if (!$strWhat) {
            throw new GooglePlusException('Methode get() needs a parameter: name, id, url');
        }
        if ($this->isReady) {
            switch(strtolower(trim($strWhat))) {
                case 'name':
                    $strReturn = $this->_objJson[5][2][2][3];
                    break;
                case 'id':
                    $strReturn = $this->_objJson[5][0];
                    break;
                case 'url':
                    $strReturn = $this->_objJson[5][2][0];
                    break;
                case 'image':
                    $strReturn = $this->_objJson[5][2][1];
                    break;
                case 'firstname':
                    $strReturn = $this->_objJson[5][2][2][1];
                    break;
                case 'lastname':
                    $strReturn = $this->_objJson[5][2][2][2];
                    break;
                case 'nickname':
                    $strReturn = $this->_objJson[5][2][34][1];
                    break;
                case 'othernames':
                    $strReturn = $this->_objJson[5][2][3][1][0][0];
                    break;
                case 'description':
                    $strReturn = $this->_objJson[5][2][24][1];
                    break;
                case 'occupation':
                    $strReturn = $this->_objJson[5][2][4][1];
                    break;
                case 'introduction':
                    $strReturn = $this->_objJson[5][2][12][1];
                    break;
                case 'links':
                    $strReturn = $this->_objJson[5][2][9][0];
                    $arrReturn = array();
                    foreach ($strReturn as $arrLink) {
                        $arrReturn[] = array($arrLink[3], $arrLink[1]);
                    }
                    return (count($arrReturn)) ? $arrReturn : $this->strNotFound;
                    break;
                case 'htmlposts':
                    $strReturn = $this->_objJson[4][0];
                    $arrReturn = array();
                    foreach ($strReturn as $arrPost) {
                        $arrReturn[] = array($arrPost[4], 'https://plus.google.com/' . $arrPost[19]);
                    }
                    return (count($arrReturn)) ? $arrReturn : $this->strNotFound;
                    break;
                case 'plainposts':
                    $strReturn = $this->_objJson[4][0];
                    $arrReturn = array();
                    foreach ($strReturn as $arrPost) {
                        $arrReturn[] = array($arrPost[18], 'https://plus.google.com/' . $arrPost[19]);
                    }
                    return (count($arrReturn)) ? $arrReturn : $this->strNotFound;
                    break;
                default:
                    return $this->strNotFound;
            }
            return (trim($strReturn)) ? trim($strReturn) : $this->strNotFound;
        }
        return $this->strNotFound;
    }
}
