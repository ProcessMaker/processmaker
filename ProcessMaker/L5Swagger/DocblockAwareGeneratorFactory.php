<?php

declare(strict_types=1);

namespace ProcessMaker\L5Swagger;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Filesystem\Filesystem;
use L5Swagger\ConfigFactory;
use L5Swagger\Generator;
use L5Swagger\GeneratorFactory;
use L5Swagger\SecurityDefinitions;
use OpenApi\Analysers\AttributeAnnotationFactory;
use OpenApi\Analysers\DocBlockAnnotationFactory;
use OpenApi\Analysers\ReflectionAnalyser;

/**
 * L5-Swagger 11 uses a ReflectionAnalyser with only AttributeAnnotationFactory by default.
 * ProcessMaker documents the HTTP API with PHPDoc @OA\* blocks, which require DocBlockAnnotationFactory
 * and the doctrine/annotations package (Doctrine\Common\Annotations\DocParser).
 */
final class DocblockAwareGeneratorFactory extends GeneratorFactory
{
    public function __construct(
        private readonly ConfigFactory $documentationConfigFactory,
        private readonly Application $application,
    ) {
        parent::__construct($documentationConfigFactory);
    }

    public function make(string $documentation): Generator
    {
        $config = $this->documentationConfigFactory->documentationConfig($documentation);

        $paths = $config['paths'];
        $scanOptions = $config['scanOptions'] ?? [];

        if (!array_key_exists('analyser', $scanOptions) || $scanOptions['analyser'] === null) {
            $scanOptions['analyser'] = new ReflectionAnalyser([
                new DocBlockAnnotationFactory(),
                new AttributeAnnotationFactory(),
            ]);
        }

        $constants = $config['constants'] ?? [];
        $yamlCopyRequired = $config['generate_yaml_copy'] ?? false;

        $secSchemesConfig = $config['securityDefinitions']['securitySchemes'] ?? [];
        $secConfig = $config['securityDefinitions']['security'] ?? [];

        $security = new SecurityDefinitions($secSchemesConfig, $secConfig);

        return new Generator(
            $paths,
            $constants,
            $yamlCopyRequired,
            $security,
            $scanOptions,
            $this->application->make(Filesystem::class)
        );
    }
}
