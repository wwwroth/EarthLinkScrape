<?

/*
 * Grab start and end date from URL. If they are not set default to today.
 */
$startDate = $_GET['startDate'];
$endDate = $_GET['endDate'];
if(strlen($startDate) == 0) $startDate = date("Y-m-d"); else $startDate = $_GET['startDate'];
if(strlen($endDate) == 0) $endDate = date("Y-m-d"); else $endDate = $_GET['endDate'];

$startDate = '2013-11-01';
$endDate = '2013-11-21';

/*
 * Set initial variables for the requests to EarthLink.
 */
$earthLinkLoginEndPoint = "https://voip.elnk.us/login";
$earthLinkLoginNumber = "xxx";
$earthLinkLoginPass = "xxx";
$earthLinkVersion = "7.4";

/*
 * Build array of post parameters for login.
 */
$earthLinkLoginParameters = array(
    "DirectoryNumber" => $earthLinkLoginNumber,
    "Password" => $earthLinkLoginPass,
    "version" => $earthLinkVersion,
    "ApplicationID" => "MS_WebClient",
    "ContextInfo" => "version 7.3",
    "UserType" => "bgAdmin",
    "errorRedirectTo" => "/bg/login.html?redirectTo=%%/bg%/main.html",
    "redirectTo" => "/bg/main.html?justLoggedIn=1385053439"	
);

/*
 * Init curl wrapper class
 * https://github.com/shuber/curl
 */
require_once("curl.php");
require_once("curl_response.php");
$curl = new Curl;

/*
 * make cookies work out of /tmp and clear out all exsiting cookies
 */
$curl->cookie_file = "/tmp/earthlink_curl_cookie.txt"; // set cookies to tmp dir 
@unlink($curl->cookie_file); // delete cookied if a file is already there

/*
 * Post to EarthLink's login end point with correct
 * parameters and extract session identifier from response.
 */
echo "logging into {$earthLinkLoginEndPoint}...\r\n";
$loginAttemptResponse = $curl->post(
    $earthLinkLoginEndPoint,
    $vars = $earthLinkLoginParameters
);

/*
 * find session identifier. This ends up being in the response body in any one of the redirect ("Location: XXXXXXX") pieces
 */
$sessionBefore = "/session";
$sessionAfter = "/";
$sessionIdentifier = substr($loginAttemptResponse->body, ($pos = strpos($loginAttemptResponse->body, $sessionBefore)) !== false ? $pos + strlen($sessionBefore) : 0);
$sessionIdentifier = substr($sessionIdentifier, 0, strpos($sessionIdentifier, $sessionAfter));

echo "Session ID: {$sessionIdentifier} \r\n";

/*
 * Come up with a unique request id, basically just the unix date
 */
$cbTokenIdentifier = number_format(microtime(true), 3, '', '');
echo "creating new cb: {$cbTokenIdentifier} \r\n";

/*
 * Now download the CSV file
 */
$Download_Link = "https://appsrv-ne.voip.elnk.us/session{$sessionIdentifier}/bg/calllogs.csv?initialDate={$startDate}T04%3A00%3A00Z&endDate={$endDate}T04%3A59%3A59Z&cb={$cbTokenIdentifier}&version={$earthLinkVersion}&downloadTo=calllogs.csv";
echo "fetching url: {$Download_Link} \r\n";
$callLogDownloadAttemptResponse = $curl->get(
    $Download_Link
);

/*
 * Finished, here is our data
 */
$data = $callLogDownloadAttemptResponse->body;

/*
 * Save contents of file to tmp folder so we have a physical file on the server
*/
$fileName = uniqid() . '.csv';
$saveFile = file_put_contents('/tmp/' . $fileName, $data);

if($saveFile == false) {
	echo "There was an error saving the downloaded data to the /tmp/ folder."; exit;	
}

/*
 * You now have the file saved in the tmp folder of your server to do what you want with.
*/

?>
