<?php

declare(strict_types=1);

namespace ProcessMaker\Mcp\Migration\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use ProcessMaker\Mcp\Migration\Writer;

#[Description('Create a PM4 collection from a PM3 report table definition.')]
class CreateCollectionFromReportTableTool extends Tool
{
    protected string $name = 'create_collection_from_report_table';

    public function __construct(private readonly Writer $writer)
    {
    }

    public function handle(Request $request): Response|ResponseFactory
    {
        $data = $request->validate([
            'report_table' => 'required|array',
            'fields' => 'nullable|array',
        ]);

        $result = $this->writer->createCollectionFromReportTable(
            $data['report_table'],
            $data['fields'] ?? ($data['report_table']['fields'] ?? [])
        );

        return Response::structured([
            'collection_id' => $result['collection']->id,
            'name' => $result['collection']->name,
            'warnings' => $result['warnings'],
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'report_table' => $schema->object()->description('Normalized PM3 report table')->required(),
            'fields' => $schema->array()->description('Optional report table field definitions'),
        ];
    }
}
