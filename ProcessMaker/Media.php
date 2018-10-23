<?php

namespace ProcessMaker;

use Illuminate\Http\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\FileManipulator;
use Spatie\MediaLibrary\Filesystem\Filesystem;
use Spatie\MediaLibrary\Models\Media as BaseMedia;

class Media extends BaseMedia
{
    /**
     * Updates the Media with a new file
     *
     * @param UploadedFile $newFile
     * @param Models\Media $file
     */
    public function updateFile(UploadedFile $newFile, \ProcessMaker\Models\Media $file)
    {
        $originalFilePath = $file->id . '/' . $file->file_name;
        $newFileName = $this->sanitizeFileName($newFile->getClientOriginalName());
        $newFilePath = $file->id . '/' . $newFileName;

        Storage::disk('public')->delete($originalFilePath);
        Storage::disk('public')->put($newFilePath, $newFile);

        $file->file_name = $newFileName;
        $file->name = pathinfo($newFileName, PATHINFO_FILENAME);
        $file->mime_type = $newFile->getMimeType();
        $file->size = filesize($newFile->path());
        $file->save();
    }

    /**
     * Removes from file name non accepted characters
     *
     * @param string $fileName
     *
     * @return string
     */
    protected function sanitizeFileName(string $fileName)
    {
        return str_replace(['#', '/', '\\'], '-', $fileName);
    }
}