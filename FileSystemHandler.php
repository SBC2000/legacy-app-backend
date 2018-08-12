<?php

class FileSystemHandler {
    private $folder;
    private $extension;

    private $folders;
    private $files;

    function __construct($folder, $ext = "") {
        $this->folder = $folder;
        $this->extension = $ext;
        $this->load();
    }

    private function load() {
        $this->files = array();
        $this->folders = array();

        $folderContents = scandir($this->folder);

        foreach ($folderContents as $item) {
            if (!($item === '.' || $item === '..')) {
                if (substr($item, -strlen($this->extension)) === $this->extension) {
                    $this->files[] = $item;
                } else if (!strpos($item, ".")) {
                    $this->folders[] = $item;
                } else {
                    throw new Exception("Unknown file format: " . $item);
                }
            }
        }

        rsort($this->folders, SORT_NUMERIC);
        rsort($this->files, SORT_NUMERIC);
    }

    function getLatestFolderVersion() {
        return count($this->folders) > 0 ? $this->folders[0] : 0;
    }

    function getLatestFileVersion() {
        return count($this->files) > 0 ? $this->getVersionFromFileName($this->getLatestFileName()) : 0;
    }

    function getLatestFileContents() {
        return file_get_contents($this->folder . '/' . $this->getLatestFileName());
    }

    function getCombinedFileContents($minVersion) {
        if ($minVersion >= $this->getLatestFileVersion()) {
            throw new Exception("Cannot retrieve combined file contents because there are no newer files. ({$this->folder})");
        }

        $allFileContents = array();
        foreach ($this->files as $file) {
            if ($this->getVersionFromFileName($file) > $minVersion) {
                $fileContents = file_get_contents($this->folder . '/' . $file);
                if (!empty($fileContents)) {
                    $allFileContents[] = $fileContents;
                }
            }
        }
        return implode(",", $allFileContents);
    }

    function storeNewFile($contents) {
        $newVersion = (count($this->files) == 0 ? 0 : $this->getLatestFileVersion()) + 1;

        $fileName = $newVersion . $this->extension;
        $filePath = $this->folder . "/" . $fileName;

        file_put_contents($filePath, $contents);

        array_unshift($this->files, $fileName);
    }

    private function getLatestFileName() {
        if (count($this->files) <= 0) {
            throw new Exception("Cannot get last file name because there are no files. ({$this->folder})");
        }

        return $this->files[0];
    }

    private function getVersionFromFileName($fileName) {
        return substr($fileName, 0, -strlen($this->extension));
    }
}

