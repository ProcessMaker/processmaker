<?php

return [
    /*
    |--------------------------------------------------------------------------
    | File Upload Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains configuration options for file uploads including
    | allowed file extensions and MIME types for security validation.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Allowed File Extensions
    |--------------------------------------------------------------------------
    |
    | List of file extensions that are allowed to be uploaded.
    | Only files with these extensions will be accepted.
    | Archive formats (.zip, .rar, .tar, .7z) are explicitly NOT allowed for security.
    |
    */
    'allowed_extensions' => [
        // Documents
        'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx',
        'txt', 'csv',

        // Images
        'jpg', 'jpeg', 'png', 'gif', 'svg',

        // Audio
        'mp3',

        // Video
        'mp4',
    ],
    /*
    |--------------------------------------------------------------------------
    | Extension to MIME Type Mapping
    |--------------------------------------------------------------------------
    |
    | An associative array that maps each allowed file extension to one or more
    | corresponding MIME types. This provides a strong validation to ensure that
    | a file's content type (MIME type) matches its declared extension,
    | preventing malicious files (like a script disguised as an image) from being uploaded.
    |
    */
    'extension_mime_map' => [
        // Documents
        'pdf'  => ['application/pdf'],
        'doc'  => ['application/msword'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
        'xls'  => ['application/vnd.ms-excel'],
        'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        'ppt'  => ['application/vnd.ms-powerpoint'],
        'pptx' => ['application/vnd.openxmlformats-officedocument.presentationml.presentation'],
        'txt'  => ['text/plain'],
        'csv'  => ['text/csv', 'application/csv'],

        // Audio
        'jpg'  => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'png'  => ['image/png'],
        'gif'  => ['image/gif'],
        'svg'  => ['image/svg+xml'],

        // Audio
        'mp3'  => ['audio/mpeg'],

        // Video
        'mp4'  => ['video/mp4'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Enable MIME Type Validation
    |--------------------------------------------------------------------------
    |
    | Whether to enable MIME type validation against allowed_mime_types list
    | AND validate that MIME type corresponds to file extension using extension_mime_map.
    | This provides comprehensive validation to prevent malicious files.
    | Recommended to keep this enabled for security.
    |
    */
    'enable_mime_validation' => env('ENABLE_MIME_VALIDATION', true),

    /*
    |--------------------------------------------------------------------------
    | Enable Extension Validation
    |--------------------------------------------------------------------------
    |
    | Whether to enable basic file extension validation against allowed_extensions list.
    | This validates that the file extension is in the allowed list.
    | Recommended to keep this enabled for security.
    |
    */
    'enable_extension_validation' => env('ENABLE_EXTENSION_VALIDATION', true),

    /*
    |--------------------------------------------------------------------------
    | Security Dangerous File Extensions
    |--------------------------------------------------------------------------
    |
    | Archive formats (.zip, .rar, .tar, .7z, .gz, etc.) are explicitly
    | NOT allowed for security reasons. These file types can contain
    | malicious content and are blocked by default.
    |
    */
    'dangerous_extensions' => [
        'zip', 'rar', '7z', 'tar', 'gz', 'bz2', 'xz', 'lzma',
        'cab', 'ar', 'iso', 'dmg', 'pkg', 'deb', 'rpm',
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Dangerous MIME Types
    |--------------------------------------------------------------------------
    |
    | A list of MIME types associated with archives and executables.
    | This provides an additional layer of security to prevent the upload of
    | compressed files or other potentially dangerous content, even if their
    | file extension has been tampered with.
    |
    */
    'dangerous_mime_types' => [
        'application/zip',
        'application/x-rar-compressed',
        'application/x-7z-compressed',
        'application/x-tar',
        'application/gzip',
        'application/x-bzip2',
        'application/x-xz',
        'application/x-lzma',
        'application/vnd.ms-cab-compressed',
        'application/x-iso9660-image',
    ],
];
