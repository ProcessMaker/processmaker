<?php

namespace ProcessMaker\ImportExport\Exporters;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use ProcessMaker\ImportExport\DependentType;
use ProcessMaker\Models\EnvironmentVariable;
use ProcessMaker\Models\ScriptCategory;
use ProcessMaker\Models\User;

class ScriptExporter extends ExporterBase
{
    public $handleDuplicatesByIncrementing = ['title'];

    public static $fallbackMatchColumn = 'title';

    public function export() : void
    {
        $this->exportCategories();

        foreach ($this->getEnvironmentVariables() as $environmentVariable) {
            $this->addDependent(DependentType::ENVIRONMENT_VARIABLES, $environmentVariable, EnvironmentVariableExporter::class);
        }

        if ($this->model->runAsUser) {
            $this->addDependent('user', $this->model->runAsUser, UserExporter::class);
        }

        if ($this->model->scriptExecutor) {
            $this->addDependent('executor', $this->model->scriptExecutor, ScriptExecutorExporter::class);
        }
    }

    public function import() : bool
    {
        $this->associateCategories(ScriptCategory::class, 'script_category_id');

        foreach ($this->getDependents('user', true) as $dependent) {
            $scriptUser = $dependent->model;
            // Assign the user ID to run the script
            // If the user is not set in the dependent modal, default to the admin user
            $this->model->run_as_user_id = $scriptUser?->id ?? User::where('is_administrator', true)->first()->id;
        }

        foreach ($this->getDependents('executor', true) as $dependent) {
            $executor = $dependent->model;
            $this->model->script_executor_id = $executor->id;
        }

        // Update environment variable references in script code
        $this->updateEnvironmentVariableReferences();

        // Pre-save cleanup for data source scripts to prevent constraint violations
        if ($this->mode === 'update' && $this->model->exists) {
            // Check if this script has any data source relationships that might cause conflicts
            $hasDataSourceRelationships = DB::table('data_source_scripts')
                ->where('script_id', $this->model->id)
                ->exists();

            if ($hasDataSourceRelationships) {
                // Clean up any conflicting records in data_source_scripts
                DB::table('data_source_scripts')
                    ->where('script_id', $this->model->id)
                    ->where('id', '!=', $this->model->id)
                    ->delete();
            }
        }

        return $this->model->save();
    }

    private function getEnvironmentVariables() : array
    {
        $environmentVariables = EnvironmentVariable::get();
        $environmentVariablesFound = [];

        // Search for environment variable present in the code
        foreach ($environmentVariables as $variable) {
            // Multiple patterns to catch different usage styles
            $patterns = [
                // JavaScript patterns
                '/\bprocess\.env\.' . preg_quote($variable->name, '/') . '\b/',               // process.env.VAR
                '/\bprocess\.env\[\s*[\'"]' . preg_quote($variable->name, '/') . '[\'"]\s*\]/', // process.env['VAR']

                // PHP patterns
                '/\bconfig\s*\(\s*[\'"]' . preg_quote($variable->name, '/') . '[\'"]\s*\)/',  // config('VAR')
                '/\benv\s*\(\s*[\'"]' . preg_quote($variable->name, '/') . '[\'"]\s*\)/',     // env('VAR')
                '/\$_ENV\s*\[\s*[\'"]' . preg_quote($variable->name, '/') . '[\'"]\s*\]/',   // $_ENV['VAR']
                '/\$_SERVER\s*\[\s*[\'"]' . preg_quote($variable->name, '/') . '[\'"]\s*\]/', // $_SERVER['VAR']

                // Java patterns
                '/\bSystem\.getenv\s*\(\s*[\'"]' . preg_quote($variable->name, '/') . '[\'"]\s*\)/',     // System.getenv("VAR")
                '/\bSystem\.getProperty\s*\(\s*[\'"]' . preg_quote($variable->name, '/') . '[\'"]\s*\)/', // System.getProperty("VAR")

                // C# patterns
                '/\bEnvironment\.GetEnvironmentVariable\s*\(\s*[\'"]' . preg_quote($variable->name, '/') . '[\'"]\s*\)/', // Environment.GetEnvironmentVariable("VAR")
                '/\bConfigurationManager\.AppSettings\s*\[\s*[\'"]' . preg_quote($variable->name, '/') . '[\'"]\s*\]/',   // ConfigurationManager.AppSettings["VAR"]

                // Python patterns
                '/\bos\.environ\s*\[\s*[\'"]' . preg_quote($variable->name, '/') . '[\'"]\s*\]/',        // os.environ['VAR']
                '/\bos\.environ\.get\s*\(\s*[\'"]' . preg_quote($variable->name, '/') . '[\'"]\s*\)/',   // os.environ.get('VAR')
                '/\bos\.getenv\s*\(\s*[\'"]' . preg_quote($variable->name, '/') . '[\'"]\s*\)/',         // os.getenv('VAR')

                // Additional PHP patterns
                '/\bgetenv\s*\(\s*[\'"]' . preg_quote($variable->name, '/') . '[\'"]\s*\)/',             // getenv('VAR')
            ];

            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $this->model->code)) {
                    $environmentVariablesFound[] = $variable;
                    break;
                }
            }
        }

        return $environmentVariablesFound;
    }

    /**
     * Update environment variable references in script code when variables are renamed during import
     *
     * Note: This is a basic implementation. For a complete solution, you would need to:
     * 1. Track original variable names during export phase
     * 2. Map original names to new names after duplicate handling
     * 3. Update all references in the script code
     */
    private function updateEnvironmentVariableReferences(): void
    {
        // Only process in copy mode where variables might be renamed
        if ($this->mode !== 'copy') {
            return;
        }

        // Get the original variables that were detected during export
        $originalVariables = $this->getEnvironmentVariables();
        $variableMapping = [];

        // Compare with the imported variables to detect name changes
        foreach ($this->getDependents(DependentType::ENVIRONMENT_VARIABLES, true) as $dependent) {
            $importedVar = $dependent->model;

            // Find matching original variable by comparing the base name
            foreach ($originalVariables as $originalVar) {
                $mapped = false;

                // Check if this is a renamed version (has suffix like _1, _2, etc.)
                if (preg_match('/^' . preg_quote($originalVar->name) . '_\d+$/', $importedVar->name)) {
                    $variableMapping[$originalVar->name] = $importedVar->name;
                    $mapped = true;
                }
                // Check if it's exactly the same name (no rename needed)
                elseif ($originalVar->name === $importedVar->name) {
                    // Don't add to mapping since no replacement is needed
                    $mapped = true;
                }
                // Check if it has other suffixes like _copy, _duplicate, etc.
                elseif (preg_match('/^' . preg_quote($originalVar->name) . '_(copy|duplicate|\d+)$/i', $importedVar->name)) {
                    $variableMapping[$originalVar->name] = $importedVar->name;
                    $mapped = true;
                }

                if ($mapped) {
                    break;
                }
            }
        }

        // Update script code with new variable names
        if (!empty($variableMapping)) {
            $updatedCode = $this->model->code;

            foreach ($variableMapping as $oldName => $newName) {
                // Update common patterns for environment variable access
                $patterns = [
                    // JavaScript patterns - process.env.VARIABLE_NAME → process.env.VARIABLE_NAME_1
                    '/\bprocess\.env\.' . preg_quote($oldName, '/') . '\b/',
                    '/\bprocess\.env\[\s*[\'"]' . preg_quote($oldName, '/') . '[\'"]\s*\]/',
                    '/\bprocess\.env\[\s*"' . preg_quote($oldName, '/') . '"\s*\]/',  // Double quotes

                    // PHP patterns
                    // config('VARIABLE_NAME') → config('VARIABLE_NAME_1')
                    '/\bconfig\s*\(\s*[\'"]' . preg_quote($oldName, '/') . '[\'"]\s*\)/',
                    // env('VARIABLE_NAME') → env('VARIABLE_NAME_1')
                    '/\benv\s*\(\s*[\'"]' . preg_quote($oldName, '/') . '[\'"]\s*\)/',
                    // $_ENV['VARIABLE_NAME'] → $_ENV['VARIABLE_NAME_1']
                    '/\$_ENV\s*\[\s*[\'"]' . preg_quote($oldName, '/') . '[\'"]\s*\]/',
                    // $_SERVER['VARIABLE_NAME'] → $_SERVER['VARIABLE_NAME_1']
                    '/\$_SERVER\s*\[\s*[\'"]' . preg_quote($oldName, '/') . '[\'"]\s*\]/',
                    // getenv('VARIABLE_NAME') → getenv('VARIABLE_NAME_1')
                    '/\bgetenv\s*\(\s*[\'"]' . preg_quote($oldName, '/') . '[\'"]\s*\)/',
                    // Config::get('VARIABLE_NAME') → Config::get('VARIABLE_NAME_1')
                    '/\bConfig\s*::\s*get\s*\(\s*[\'"]' . preg_quote($oldName, '/') . '[\'"]\s*\)/',

                    // Java patterns
                    // System.getenv("VARIABLE_NAME") → System.getenv("VARIABLE_NAME_1")
                    '/\bSystem\.getenv\s*\(\s*[\'"]' . preg_quote($oldName, '/') . '[\'"]\s*\)/',
                    // System.getProperty("VARIABLE_NAME") → System.getProperty("VARIABLE_NAME_1")
                    '/\bSystem\.getProperty\s*\(\s*[\'"]' . preg_quote($oldName, '/') . '[\'"]\s*\)/',

                    // C# patterns
                    // Environment.GetEnvironmentVariable("VARIABLE_NAME") → Environment.GetEnvironmentVariable("VARIABLE_NAME_1")
                    '/\bEnvironment\.GetEnvironmentVariable\s*\(\s*[\'"]' . preg_quote($oldName, '/') . '[\'"]\s*\)/',
                    // ConfigurationManager.AppSettings["VARIABLE_NAME"] → ConfigurationManager.AppSettings["VARIABLE_NAME_1"]
                    '/\bConfigurationManager\.AppSettings\s*\[\s*[\'"]' . preg_quote($oldName, '/') . '[\'"]\s*\]/',

                    // Python patterns
                    // os.environ['VARIABLE_NAME'] → os.environ['VARIABLE_NAME_1']
                    '/\bos\.environ\s*\[\s*[\'"]' . preg_quote($oldName, '/') . '[\'"]\s*\]/',
                    // os.environ.get('VARIABLE_NAME') → os.environ.get('VARIABLE_NAME_1')
                    '/\bos\.environ\.get\s*\(\s*[\'"]' . preg_quote($oldName, '/') . '[\'"]\s*\)/',
                    // os.getenv('VARIABLE_NAME') → os.getenv('VARIABLE_NAME_1')
                    '/\bos\.getenv\s*\(\s*[\'"]' . preg_quote($oldName, '/') . '[\'"]\s*\)/',
                ];

                foreach ($patterns as $patternIndex => $pattern) {
                    $updatedCode = preg_replace_callback($pattern, function ($matches) use ($oldName, $newName, $patternIndex) {
                        $original = $matches[0];

                        // Handle different replacement patterns
                        switch ($patternIndex) {
                            case 0: // process.env.VARIABLE_NAME
                                return str_replace('process.env.' . $oldName, 'process.env.' . $newName, $original);
                            case 1: // process.env['VARIABLE_NAME']
                            case 2: // process.env["VARIABLE_NAME"]
                                return str_replace($oldName, $newName, $original);
                            case 6: // System.getenv for Java
                                return str_replace($oldName, $newName, $original);
                            case 7: // System.getProperty for Java
                                return str_replace($oldName, $newName, $original);
                            case 8: // Environment.GetEnvironmentVariable for C#
                                return str_replace($oldName, $newName, $original);
                            case 9: // ConfigurationManager.AppSettings for C#
                                return str_replace($oldName, $newName, $original);
                            case 10: // os.environ for Python
                            case 11: // os.environ.get for Python
                            case 12: // os.getenv for Python
                                return str_replace($oldName, $newName, $original);
                            default: // All other patterns (PHP)
                                return str_replace($oldName, $newName, $original);
                        }
                    }, $updatedCode);
                }
            }

            // Only update if changes were made
            if ($updatedCode !== $this->model->code) {
                $this->model->code = $updatedCode;

                // Log the variable name changes (summary)
                Log::info('ScriptExporter: Updated variable references in script', [
                    'script_id' => $this->model->id,
                    'script_title' => $this->model->title,
                    'variables_updated' => count($variableMapping),
                    'mappings' => $variableMapping,
                    'mode' => $this->mode,
                ]);
            }
        }
    }
}
