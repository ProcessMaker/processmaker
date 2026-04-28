<?php

namespace ProcessMaker\Traits;

use Illuminate\Http\UploadedFile;

trait ValidatesFileTrait
{
    /**
     * Validate uploaded file for security and type restrictions
     *
     * @param UploadedFile $file
     * @param array $errors
     * @return array
     */
    private function validateFile(UploadedFile $file, &$errors)
    {
        // Explicitly reject archive files for security
        if (config('files.enable_dangerous_validation')) {
            $this->rejectArchiveFiles($file, $errors);
        }

        // Validate file extension if enabled
        if (config('files.enable_extension_validation', true)) {
            $this->validateFileExtension($file, $errors);
        }

        // Validate MIME type vs extension if enabled
        if (config('files.enable_mime_validation', true)) {
            $this->validateExtensionMimeTypeMatch($file, $errors);
        }

        // Validate specific file types (e.g., PDF for JavaScript content)
        if (strtolower($file->getClientOriginalExtension()) === 'pdf') {
            $this->validatePDFFile($file, $errors);
        }

        return $errors;
    }

    /**
     * Explicitly reject archive files for security reasons
     *
     * @param UploadedFile $file
     * @param array $errors
     * @return void
     */
    private function rejectArchiveFiles(UploadedFile $file, &$errors)
    {
        $dangerousExtensions = config('files.dangerous_extensions');

        $fileExtension = strtolower($file->getClientOriginalExtension());

        if (in_array($fileExtension, $dangerousExtensions)) {
            $errors['message'] = __('Uploaded file type is not allowed');

            return;
        }

        // Also check MIME types for archive files
        $dangerousMimeTypes = config('files.dangerous_mime_types');

        $fileMimeType = $file->getMimeType();

        if (in_array($fileMimeType, $dangerousMimeTypes)) {
            $errors['message'] = __('Uploaded mime file type is not allowed');
        }
    }

    /**
     * Validate that file extension matches the MIME type
     *
     * @param UploadedFile $file
     * @param array $errors
     * @return void
     */
    private function validateExtensionMimeTypeMatch(UploadedFile $file, &$errors)
    {
        $fileExtension = strtolower($file->getClientOriginalExtension());
        $fileMimeType = $file->getMimeType();

        // Get extension to MIME type mapping from configuration
        $extensionMimeMap = config('files.extension_mime_map');

        // Check if extension exists in our map
        if (!isset($extensionMimeMap[$fileExtension])) {
            $errors['message'] = __('File extension not allowed');

            return;
        }

        // Check if MIME type matches any of the expected types for this extension
        if (!in_array($fileMimeType, $extensionMimeMap[$fileExtension])) {
            $errors['message'] = __('The file extension does not match the actual file content');
        }
    }

    /**
     * Validate file extension against allowed extensions
     *
     * @param UploadedFile $file
     * @param array $errors
     * @return void
     */
    private function validateFileExtension(UploadedFile $file, &$errors)
    {
        $allowedExtensions = config('files.allowed_extensions');

        $fileExtension = strtolower($file->getClientOriginalExtension());

        if (!in_array($fileExtension, $allowedExtensions)) {
            $errors['message'] = __('File extension not allowed');
        }
    }

    /**
     * Validate PDF files for dangerous content
     *
     * @param UploadedFile $file
     * @param array $errors
     * @return void
     */
    private function validatePDFFile(UploadedFile $file, &$errors)
    {
        $text = $file->get();

        $jsKeywords = ['/JavaScript', '<< /S /JavaScript'];

        foreach ($jsKeywords as $keyword) {
            if (strpos($text, $keyword) !== false) {
                $errors[] = __('Dangerous PDF file content');
                break;
            }
        }
    }
}
