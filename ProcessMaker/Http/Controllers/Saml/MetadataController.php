<?php

namespace ProcessMaker\Http\Controllers\Saml;

use CodeGreenCreative\SamlIdp\Http\Controllers\MetadataController as SamlIdpMetadataController;
use ProcessMaker\Exception\MetadataFileNotFoundException;

class MetadataController extends SamlIdpMetadataController
{
    public function __construct()
    {
        $file = $this->getMetadataFilePath();

        if ($this->fileExists($file)) {
            $this->modifyFileContent($file);
        } else {
            throw new MetadataFileNotFoundException('Unable to find metadata file');
        }
    }

    private function getMetadataFilePath()
    {
        $basePath = base_path();
        $directory = $basePath . '/vendor/codegreencreative/laravel-samlidp/resources/views';
        return $directory . '/metadata.blade.php';
    }

    private function fileExists($file)
    {
        $directory = dirname($file);
        return is_dir($directory) && file_exists($file);
    }

    private function modifyFileContent($file)
    {
        $lines = file($file);

        // check if the first line is the xml tag
        if (strpos($lines[0], '<?xml') === 0) {
            // remove break lines and add the php tag
            $lines[0] = str_replace(PHP_EOL, '', $lines[0]);
            $lines[0] = "@php echo '" . $lines[0] . "' @endphp" . PHP_EOL;

            // save the contents
            file_put_contents($file, $lines);
        }
    }
}
