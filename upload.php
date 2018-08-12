<?php

include "FileSystemHandler.php";
include "password.php";

$dataSaver = new DataSaver();
$dataSaver->run($_POST);

class DataSaver {
    private $extension = ".json";

    function run($parameters) {
        try {
            if (md5($parameters["password"]) === $password) {
                $mainLoader = new FileSystemHandler("data");
                $latestDatabaseVersion = $mainLoader->getLatestFolderVersion();

                switch ($parameters["type"]) {
                    case "database":
                        $folder = "databases";
                        break;
                    case "results":
                        $folder = "results";
                        break;
                    case "sponsors":
                        $folder = "sponsors";
                        break;
                    case "message":
                        $folder = "messages";
                        break;
                    default:
                        throw new Exception("Unknown upload type.");
                }

                $resultFolderHandler = new FileSystemHandler("data/$latestDatabaseVersion/$folder", $this->extension);
                $resultFolderHandler->storeNewFile($parameters["data"]);
            } else {
                throw new Exception("Incorrect password");
            }
        } catch (Exception $e) {
            header('X-PHP-Response-Code: 500', true, 500);
            throw $e;
        }
    }
}
