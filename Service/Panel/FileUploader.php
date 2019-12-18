<?php

namespace App\Service\Panel;

use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class FileUploader
{
    private $targetDirectory;

    public function __construct($targetDirectory = null)
    {
        $this->targetDirectory = $targetDirectory;
    }

    /**
     * Handles file upload process
     * @param UploadedFile $file An instance of uploaded file
     * @return type new uploaded file name is returned
     */
    public function upload(UploadedFile $file)
    {
        $fileName = $this->generateUniqueFileName($file);

        try {
            $file->move($this->getTargetDirectory(), $fileName);
        } catch (FileException $e) {
            
        }

        return $fileName;
    }

    /**
     * Returnes assigned target directory. by default /uploads
     * @return string
     */
    public function getTargetDirectory()
    {
        return $this->targetDirectory;
    }

    /**
     * Sets new target directory. By default /upload is set
     * @param string $targetDirectory new upload directory to be saved
     * @return $this
     */
    public function setTargetDirectory(string $targetDirectory)
    {
        $this->targetDirectory = $targetDirectory;
        return $this;
    }

    /**
     * Genarates unique filename
     * @param UploadedFile $file an instance of Uploaded file
     * @return string filename uploaded is returned
     */
    private function generateUniqueFileName(UploadedFile $file)
    {
        $fileName = md5(uniqid()).'.'.$file->guessExtension();
        return $fileName;
    }
}