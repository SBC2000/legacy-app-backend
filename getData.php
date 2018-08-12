<?php

include "FileSystemHandler.php";

header('Content-Type: application/json');

$dataLoader = new DataLoader();
print $dataLoader->run($_GET);

class DataLoader {
    private $folderSettings = array(
        "database" => array(
            "inParam" => "dataversion",
            "outParam" => "dataVersion",
            "folder" => "databases",
            "extension" => ".json",
            "combineFiles" => false,
        ),
        "results" => array(
            "inParam" => "resultversion",
            "outParam" => "resultVersion",
            "folder" => "results",
            "extension" => ".json",
            "combineFiles" => true,
            "header" => "results",
        ),
        "messages" => array(
            "inParam" => "messageversion",
            "outParam" => "messageVersion",
            "folder" => "messages",
            "extension" => ".json",
            "combineFiles" => true,
            "header" => "messages",
        ),
        "sponsors" => array(
            "inParam" => "sponsorsversion",
            "outParam" => "sponsorsVersion",
            "folder" => "sponsors",
            "extension" => ".json",
            "combineFiles" => false,
        ),
    );

    private $extension = ".json";

    public function run($parameters) {
        $requestedDatabaseVersion = $parameters["databaseversion"];

        // load databases
        $mainLoader = new FileSystemHandler("data");

        // check if current database is newer than requested database
        $latestDatabaseVersion = $mainLoader->getLatestFolderVersion();
        $isNewDatabaseVersion = $requestedDatabaseVersion < $latestDatabaseVersion;

        // load versions of other parameters
        foreach ($this->folderSettings as $key => $folderSetting) {
            $this->folderSettings[$key]["version"] = $isNewDatabaseVersion ? 0 : $parameters[$folderSetting["inParam"]];
        }

        // set latest database version in reponse
        $isNewDatabaseVersionString = $isNewDatabaseVersion ? "true" : "false";
        $json = array("\"databaseVersion\": $latestDatabaseVersion, \"newDatabaseVersion\": $isNewDatabaseVersionString");

        // load latest versions of other parameters and add to response
        foreach ($this->folderSettings as $folderSetting) {
            // load folder
            $folderLoader = new FileSystemHandler("data/$latestDatabaseVersion/" . $folderSetting["folder"], $this->extension);

            $latestFileVersion = $folderLoader->getLatestFileVersion();
            $requestedVersion = $folderSetting["version"];

            $version = $latestFileVersion <= $requestedVersion ? $requestedVersion : $latestFileVersion;
            $fileJson = "\"" . $folderSetting["outParam"] . "\": $version";

            if ($latestFileVersion > $requestedVersion) {
                if ($folderSetting["combineFiles"]) {
                    $header = $folderSetting["header"];
                    $fileJson .= ", \"$header\": [{$folderLoader->getCombinedFileContents($requestedVersion)}]";
                } else {
                    $fileJson .= "," . $folderLoader->getLatestFileContents();
                }
            }

            $json[] = $fileJson;
        }

        return "{" . implode(",", $json) . "}";
    }
}
