<?php

namespace ProcessMaker\Package;

use Illuminate\Support\Facades\File;

class PackageLanguage
{
    /**
     * @var string
     */
    private $path = '';

    /**
     * @var array
     */
    private $languages = [];

    /**
     * Register Path and languages of package
     *
     * @param $path
     */
    public function registerPath($path)
    {
        $this->path = null;
        if (File::exists($path)) {
            $this->path = $path;

            //Search by file of languages
            foreach (glob($path . '*.json') as $filename) {
                $this->languages[] = basename($filename);
            }
        }
    }

    /**
     * Merge Language package with language bpm
     *
     * @return bool
     */
    public function mergeLanguage()
    {
        try
        {
            $response = true;
            foreach ($this->languages as $language) {
                //Language local
                $resourceLocal = resource_path('lang') . '/' . $language;
                $localLanguage = [];
                if (File::exists($resourceLocal)) {
                    $localLanguage = json_decode(file_get_contents($resourceLocal), true);
                    $response = $this->errors('BPM - ' . $language) ? $response : false;
                }

                //Language Package
                $resourcePackage = $this->path . $language;
                $packageLanguage = json_decode(utf8_encode(file_get_contents($resourcePackage)), true);
                $response = $this->errors($language) ? $response : false;

                if (!$packageLanguage) {
                    $packageLanguage = [];
                }
                $data = json_encode(array_merge($localLanguage, $packageLanguage), JSON_PRETTY_PRINT);

                //Merge languages
                file_put_contents($resourceLocal, stripslashes($data));
            }
            return $response;

        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Show error with file json.
     *
     * @param $language
     *
     * @return bool
     */
    private function errors($language)
    {
        $response = false;
        switch(json_last_error()) {
            case JSON_ERROR_NONE:
                //without errors.
                $response = true;
                break;
            case JSON_ERROR_DEPTH:
                echo $language . ' - ' . __('Exceeded maximum stack size') . "\n";
                break;
            case JSON_ERROR_STATE_MISMATCH:
                echo $language . ' - ' . __('Buffer overflow or modes do not match') . "\n";
                break;
            case JSON_ERROR_CTRL_CHAR:
                echo $language . ' - ' . __('Found unexpected control character') . "\n";
                break;
            case JSON_ERROR_SYNTAX:
                echo $language . ' - ' . __('Syntax error, malformed JSON') . "\n";
                break;
            case JSON_ERROR_UTF8:
                echo $language . ' - ' . __('Uformed UTF-8 characters, possibly incorrectly coded') . "\n";
                break;
            default:
                echo $language . ' - ' . __('Unknown error') . "\n";
                break;
        }

        return $response;
    }

}