<?php

namespace ProcessMaker\Enums;

enum ExporterMap
{
    const TYPES = [
        'screen' => [\ProcessMaker\Models\Screen::class, \ProcessMaker\ImportExport\Exporters\ScreenExporter::class],
        'process' => [\ProcessMaker\Models\Process::class, \ProcessMaker\ImportExport\Exporters\ProcessExporter::class],
        'script' => [\ProcessMaker\Models\Script::class, \ProcessMaker\ImportExport\Exporters\ScriptExporter::class],
        'process_templates' => [\ProcessMaker\Models\ProcessTemplates::class, \ProcessMaker\ImportExport\Exporters\TemplateExporter::class],
        'data_source' => [\ProcessMaker\Packages\Connectors\DataSources\Models\DataSource::class, \ProcessMaker\Packages\Connectors\DataSources\ImportExport\DataSourceExporter::class],
        'decision_table' => [\ProcessMaker\Package\PackageDecisionEngine\Models\DecisionTable::class, \ProcessMaker\Package\PackageDecisionEngine\ImportExport\DecisionTableExporter::class],
        'screen-template' => [\ProcessMaker\Models\ScreenTemplates::class, \ProcessMaker\ImportExport\Exporters\ScreenTemplatesExporter::class],
    ];

    public static function getModelClass(string $type): ?string
    {
        return self::TYPES[$type][0] ?? null;
    }

    public static function getExporterClass(string $type): ?string
    {
        return self::TYPES[$type][1] ?? null;
    }
}
