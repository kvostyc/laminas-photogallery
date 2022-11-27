<?php

namespace Application\Service;

class ImageManager 
{

    private $saveToDir = './data/upload/';
        
    public function getSaveToDir() 
    {
        return $this->saveToDir;
    }
    
    public function getImagePathByName($fileName) 
    {
        // Take some precautions to make file name secure
        $fileName = str_replace("/", "", $fileName);  // Remove slashes
        $fileName = str_replace("\\", "", $fileName); // Remove back-slashes
                
        // Return concatenated directory name and file name.
        return $this->saveToDir . $fileName;                
    }

    public function getSavedFiles() 
    {
        if(!is_dir($this->saveToDir)) {
            if(!mkdir($this->saveToDir)) {
                throw new \Exception('Could not create directory for uploads: '. error_get_last());
            }
        }

        $files = array();        
        $handle  = opendir($this->saveToDir);
        while (false !== ($entry = readdir($handle))) {
            
            if($entry=='.' || $entry=='..')
                continue; 
            
            $files[] = $entry;
        }
        return $files;
    }
    
    public function getImageFileInfo($filePath) 
    {     
        if (!is_readable($filePath)) {            
            return false;
        }

        $fileSize = filesize($filePath);
        $finfo = finfo_open(FILEINFO_MIME);
        $mimeType = finfo_file($finfo, $filePath);
        if($mimeType===false)
            $mimeType = 'application/octet-stream';
        
        return [
            'size' => $fileSize,
            'type' => $mimeType 
        ];
    }

    public function getImageFileContent($filePath) 
    {
        return file_get_contents($filePath);
    }

    public  function resizeImage($filePath, $desiredWidth = 240) 
    {
        list($originalWidth, $originalHeight) = getimagesize($filePath);
        $aspectRatio = $originalWidth/$originalHeight;
        $desiredHeight = $desiredWidth/$aspectRatio;
        $fileInfo = $this->getImageFileInfo($filePath); 
        $resultingImage = imagecreatetruecolor($desiredWidth, $desiredHeight);
        if (substr($fileInfo['type'], 0, 9) =='image/png')
            $originalImage = imagecreatefrompng($filePath);
        else
            $originalImage = imagecreatefromjpeg($filePath);
        imagecopyresampled($resultingImage, $originalImage, 0, 0, 0, 0, 
                $desiredWidth, $desiredHeight, $originalWidth, $originalHeight);

        $tmpFileName = @tempnam("/tmp", "FOO");
        imagejpeg($resultingImage, $tmpFileName, 80);

        return $tmpFileName;
    }
}
