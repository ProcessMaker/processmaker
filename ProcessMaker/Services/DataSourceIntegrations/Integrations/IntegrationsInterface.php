<?php

namespace ProcessMaker\Services\DataSourceIntegrations\Integrations;

interface IntegrationsInterface
{
    public function getParameters(): array;

    public function getCompanies(array $params = []): array;

    public function fetchCompanyDetails(string $source, string $companyId): array;
}
