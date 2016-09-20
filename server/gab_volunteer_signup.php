<?php
date_default_timezone_set("America/Los_Angeles");
//$base = realpath(dirname($_SERVER["SCRIPT_FILENAME"]) . "/..") . "/";
require "autoload.php";
use Google\Spreadsheet\DefaultServiceRequest;
use Google\Spreadsheet\ServiceRequestFactory;
$scopes = implode(' ', array(
    Google_Service_Calendar::CALENDAR,
    "https://spreadsheets.google.com/feeds"));
$privateKey = file_get_contents("../keys/auth.p12");
$clientEmail = "gab2016@proud-shoreline-122419.iam.gserviceaccount.com";
$credentials = new Google_Auth_AssertionCredentials(
    $clientEmail,
    $scopes,
    $privateKey
);
$client = new Google_Client();
$client->setAssertionCredentials($credentials);
if ($client->getAuth()->isAccessTokenExpired()) {
    $client->getAuth()->refreshTokenWithAssertion();
}
$accessToken = json_decode($client->getAccessToken());
$accessToken = $accessToken->{"access_token"};
$serviceRequest = new DefaultServiceRequest($accessToken);
ServiceRequestFactory::setInstance($serviceRequest);
$spreadsheetService = new Google\Spreadsheet\SpreadsheetService();
$spreadsheetFeed = $spreadsheetService->getSpreadsheets();
$spreadsheet = $spreadsheetFeed->getByTitle('2016_GAB_VOLUNTEER_SIGNUP');
$worksheet = $spreadsheet->getWorksheets()->getByTitle("Sheet1");
$feed = $worksheet->getCellFeed();
$method = strtolower($_SERVER['REQUEST_METHOD']);
$columnToNameMapping = [0, "serial", "name", "phone", "email", "center", "area", "time", "date", "comments"];
if ($method === "get") {
    $entries = $feed->getEntries();
    $rows = array();
    foreach ($entries as $entry) {
        if (!array_key_exists($entry->getRow(), $rows)) {
            $rows[$entry->getRow()] = array();
        }
        if (array_key_exists($entry->getColumn(), $columnToNameMapping)) {
            $column = $columnToNameMapping[$entry->getColumn()];
        } else {
            $column = $entry->getColumn();
        }
        $rows[$entry->getRow()][$column] = $entry->getContent();
        $rows[$entry->getRow()]["row"] = $entry->getRow();
        $rows[$entry->getRow()]["col"] = $entry->getColumn();
    }
    $array = array();
    for ($i = 6, $max = max(array_keys($rows)); $i <= $max; ++$i) {
        $array[] = $rows[$i];
    }
    echo json_encode($array);
} else if ($method === "post") {
    $values = array();
    $row = $_REQUEST["when"];
    $url = $feed->getPostUrl();
    if (!empty($row)) {
        $properties = ["email", "name", "phone", "center"];
        foreach ($properties as $property) {
            if (empty ($_REQUEST[$property])) {
                echo json_encode(false);
                return 1;
            }
        }
        $flipped = array_flip($columnToNameMapping);
        foreach ($properties as $property) {
            $col = $flipped[$property];
            $entry = sprintf('
            <entry xmlns="http://www.w3.org/2005/Atom"
                xmlns:gs="http://schemas.google.com/spreadsheets/2006">
              <gs:cell row="%u" col="%u" inputValue="%s"/>
            </entry>',
                $row,
                $col,
                $_REQUEST[$property]
            );
            $serviceRequest->post($url, $entry);
        }
        echo json_encode(true);
        return 0;
    }
}
?>