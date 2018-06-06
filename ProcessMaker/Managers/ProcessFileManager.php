<?php

namespace ProcessMaker\Managers;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use ProcessMaker\Exception\ValidationException;
use ProcessMaker\Model\Process;
use ProcessMaker\Model\ProcessFile;
use ProcessMaker\Model\User;
use Ramsey\Uuid\Uuid;

class ProcessFileManager
{
    /**
     * Disk of the request.
     *
     * @var \Illuminate\Contracts\Filesystem\Filesystem $disk
     */
    private $disk;

    /**
     * drive of the request.
     *
     * @var string $drive
     */
    private $drive;

    /**
     *
     * @var string $pathForClient
     */
    private $pathForClient;

    /**
     *
     * @var string $relativePath
     */
    private $relativePath;

    /**
     * Read the contents of a path.
     *
     * @param string $path
     * @param Process $process
     * @param bool $getContents
     *
     * @return array
     */
    public function read($path, Process $process, $getContents = false)
    {
        $this->validate(
            [
                'path' => $path,
            ],
            [
                'path' => 'filemanager.drive_from_path',
            ]
        );
        if ($path != '') {
            $arrayData = $this->listContentsOfPath(
                $process,
                $path,
                $getContents
            );
        } else {
            $arrayData = $this->getRootFolders();
        }
        return $arrayData;
    }

    /**
     * Stores a process file.
     *
     * @param Process $process
     * @param User $user
     * @param array $data
     * @param bool $isImport
     *
     * @return array
     */
    public function store(Process $process, User $user, array $data, $isImport = false)
    {
        $this->validate(
            $data,
            [
                'filename' => [
                    'required',
                    'filemanager.filename_is_valid',
                    'filemanager.store_only_html_to_templates',
                    'filemanager.do_not_store_exe_in_public',
                    'filemanager.do_not_store_php_in_public',
                ],
                'path' => 'required|filemanager.drive_from_path',
            ],
            [],
            [
                'isImport' => $isImport
            ]
        );
        $processUid = $process->uid;
        $userId = $user->id;
        $this->initializeFromPath($data['path'], $processUid);
        if (!ProcessFile::withPath($this->relativePath)->exists()) {
            ProcessFile::create([
                'uid' => Uuid::uuid4(),
                'process_id' => $process->id,
                'user_id' => $userId,
                'update_user_id' => null,
                'path' => $this->disk->path($this->relativePath) . '/',
                'type' => 'folder',
                'editable' => false,
                'drive' => $this->drive,
                'path_for_client' => $this->pathForClient . '/',
            ]);
        }
        $filename = $this->relativePath . '/' . $data['filename'];
        $processFile = ProcessFile::create([
            'uid' => str_replace('-', '', Uuid::uuid4()),
            'process_id' => $process->id,
            'user_id' => $userId,
            'update_user_id' => null,
            'path' => $this->disk->path($filename),
            'type' => 'file',
            'drive' => $this->drive,
            'path_for_client' => $this->pathForClient . '/' . $data['filename'],
        ]);
        $this->disk->put($filename, $data['content']);
        return $this->format($processFile, true, false);
    }

    /**
     * Update an existing process file
     *
     * @param array $data
     * @param Process $process
     * @param ProcessFile $processFile
     * @param User $user
     *
     * @return array
     */
    public function update(array $data, Process $process, ProcessFile $processFile, User $user)
    {
        $this->validate(
            [
                'processFile' => $processFile
            ],
            [
                'processFile' => 'filemanager.file_is_editable'
            ]
        );
        $processFile->update_user_id = $user->id;
        $processFile->setContent($data['content']);
        $processFile->save();
        return $this->format($processFile, true, false);
    }

    /**
     * Remove a process file.
     *
     * @param Process $process
     * @param ProcessFile $processFile
     * @param bool $verifyingRelationship
     *
     * @return bool
     */
    public function remove(Process $process, ProcessFile $processFile, $verifyingRelationship = true)
    {
        $rules = [];
        if ($verifyingRelationship) {
            $rules['processFile'] = [
                'filemanager.file_is_not_used_as_routing_screen',
                'filemanager.file_is_not_used_at_email_events'
            ];
        }
        $this->validate(
            [
                'processFile' => $processFile
            ],
            $rules
        );
        return $processFile->delete();
    }

    /**
     * Remove folders from a specified path.
     *
     * @param string $path
     * @param Process $process
     */
    public function removeFolder($path, Process $process)
    {
        $processUid = $process->uid;
        $pathParts = explode('/', trim($path, '/'), 2);
        $drivePath = $processUid . '/' . (isset($pathParts[1]) ? $pathParts[1] : '');
        ProcessFile::withPath($drivePath . DIRECTORY_SEPARATOR)->each(
            function (ProcessFile $processFile) {
                if ($processFile->type !== 'folder') {
                    $processFile->delete();
                }
            }
        );
    }

    /**
     * Put the content of an uploaded file into a process file.
     *
     * @param UploadedFile $file
     * @param ProcessFile $processFile
     *
     * @return string|false
     */
    public function putUploadedFileIntoProcessFile(UploadedFile $file, ProcessFile $processFile)
    {
        $path = dirname($processFile->getPathInDisk());
        $name = basename($processFile->path_for_client);
        $res = $processFile->disk()->putFileAs($path, $file, $name);
        return $res;
    }

    /**
     * Initialize the drive, disk and relative path variables from the public path
     * and the owner process uid.
     *
     * @param string $publicPath Ex. templates/folder/file.html
     * @param string $processUid uid of the owner process.
     */
    private function initializeFromPath($publicPath, $processUid)
    {
        $pathParts = explode('/', ltrim($publicPath, '/'), 2);
        $this->drive = $pathParts[0];
        $this->disk = Storage::disk(ProcessFile::DISKS[$pathParts[0]]);
        $this->pathForClient = isset($pathParts[1]) ? $pathParts[1] : '';
        $this->relativePath = $processUid . '/' . $this->pathForClient;
    }

    /**
     * Get the list of root folders.
     *
     * @return array
     */
    private function getRootFolders()
    {
        return [
            [
                'name' => "templates",
                'type' => "folder",
                'path' => "/",
                'editable' => false
            ],
            [
                'name' => "public",
                'type' => "folder",
                'path' => "/",
                'editable' => false
            ]
        ];
    }

    /**
     * Get the list of contents of the given path.
     *
     * @param string $processUid
     * @param string $path
     * @param bool $includeFileContent
     *
     * @return array
     */
    private function listContentsOfPath($process, $path, $includeFileContent = true)
    {
        $this->initializeFromPath($path, $process->uid);
        $list = [];
        $directories = $this->disk->directories($this->relativePath);
        foreach ($directories as $dir) {
            $list[] = [
                'name' => basename($dir),
                'type' => 'folder',
                'path' => $this->drive,
            ];
        }
        $files = $this->disk->files($this->relativePath);
        foreach ($files as $filepath) {
            $processFile = ProcessFile::withPath($filepath)->firstOrNew(
                [],
                [
                    'uid' => '',
                    'process_id' => $process->id,
                    'user_id' => '',
                    'update_user_id' => null,
                    'path' => $this->disk->path($filepath),
                    'type' => 'file',
                    'drive' => $this->drive,
                    'path_for_client' => $this->pathForClient . '/' . basename($filepath),
                ]
            );
            $list[] = $this->format($processFile, $includeFileContent, true);
        }
        return $list;
    }

    /**
     * Format the process file as a json response.
     *
     * @param ProcessFile $processFile
     * @param bool $includeContent
     * @param bool $editableAsString
     *
     * @return array
     */
    public function format(ProcessFile $processFile, $includeContent = false, $editableAsString = false)
    {
        return [
            'uid' => $processFile->uid,
            'filename' => basename($processFile->path),
            'user_id' => $processFile->user_id,
            'update_user_id' => $processFile->update_user_id,
            'path' => dirname($processFile->drive . '/' . $processFile->path_for_client) . '/',
            'type' => $processFile->type,
            'editable' => $editableAsString ? json_encode($processFile->editable) : $processFile->editable,
            'content' => $includeContent ? $processFile->getContent() : '',
        ];
    }

    /**
     * Validate the given data with the given rules.
     *
     * @param array $data
     * @param array $rules
     * @param array $messages
     * @param array $customAttributes
     *
     * @throws ValidationException
     */
    private function validate(
        array $data,
        array $rules,
        array $messages = [],
        array $customAttributes = []
    )
    {
        /* @var $validator \Illuminate\Validation\Validator */
        $validator = Validator::make($data, $rules, $messages, $customAttributes);

        /**
         * Validate if the path points to a valid drive.
         */
        $validator->addExtension(
            'filemanager.drive_from_path',
            function ($attribute, $path, $parameters, $validator) {
                $pathParts = explode('/', ltrim($path, '/'), 2);
                return empty($path)
                    || array_key_exists($pathParts[0], ProcessFile::DISKS);
            }
        );
        /**
         * Validate if the path points to a valid drive.
         */
        $validator->addExtension(
            'filemanager.file_is_editable',
            function ($attribute, ProcessFile $processFile) {
                return $processFile->editable;
            }
        );
        /**
         * Validate if the file is not used.
         */
        $validator->addExtension(
            'filemanager.file_is_not_used_at_email_events',
            function ($attribute, ProcessFile $processFile) {
                return $processFile->emailEvents->count() === 0;
            }
        );
        /**
         * Validate if the file is not used.
         */
        $validator->addExtension(
            'filemanager.file_is_not_used_as_routing_screen',
            function ($attribute, ProcessFile $processFile) {
                return !$processFile->IS_USED_AS_ROUTING_SCREEN;
            }
        );
        /**
         * Validate if the filename does not include a path.
         */
        $validator->addExtension(
            'filemanager.filename_is_valid',
            function ($attribute, $filename) {
                return dirname($filename) === '.';
            }
        );
        /**
         * Validate to only store html files into templates.
         */
        $validator->addExtension(
            'filemanager.store_only_html_to_templates',
            function ($attribute, $filename, $parameters, \Illuminate\Validation\Validator $validator) {
                $data = $validator->getData();
                $pathParts = explode('/', ltrim($data['path'], '/'), 2);
                $drive = $pathParts[0];
                return !($drive === 'templates' && File::extension($filename) !== 'html');
            }
        );
        /**
         * Validate to do not store "exe" files into public.
         */
        $validator->addExtension(
            'filemanager.do_not_store_exe_in_public',
            function ($attribute, $filename, $parameters, \Illuminate\Validation\Validator $validator) {
                //check if the file is an exe file
                $isExe = in_array(File::extension($filename), ['exe', 'bat', 'app']);

                //Check if the file is being uploaded to the public drive.
                $data = $validator->getData();
                $pathParts = explode('/', ltrim($data['path'], '/'), 2);
                $isPublic = $pathParts[0] === 'public';

                return !($isPublic && $isExe);
            }
        );
        /**
         * Validate to do not store "php" files into public.
         */
        $validator->addExtension(
            'filemanager.do_not_store_php_in_public',
            function ($attribute, $filename, $parameters, \Illuminate\Validation\Validator $validator) {
                //check if the file is a php file
                $isPhp = File::extension($filename) === 'php';

                //check if the php file upload is disabled in the config.
                $phpUploadIsDisabled = config('app.disable_php_upload_execution');

                //Check if the file is being uploaded to the public drive.
                $data = $validator->getData();
                $pathParts = explode('/', ltrim($data['path'], '/'), 2);
                $isPublic = $pathParts[0] === 'public';

                //Check if the file is uploaded from an import process.
                $isFromImport = $validator->customAttributes['isImport'];

                return !($isPhp && $isPublic && $phpUploadIsDisabled && !$isFromImport);
            }
        );
        $validator->addReplacer(
            'filemanager.file_is_not_used_at_email_events',
            function ($message, $attribute, $rule, $parameters, $validator) {
                $data = $validator->getData();
                return str_replace([':path'], [$data['processFile']->getPath()], $message);
            }
        );
        $validator->addReplacer(
            'filemanager.file_is_not_used_as_routing_screen',
            function ($message, $attribute, $rule, $parameters, $validator) {
                $data = $validator->getData();
                return str_replace([':path'], [$data['processFile']->getPath()], $message);
            }
        );

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }
}
