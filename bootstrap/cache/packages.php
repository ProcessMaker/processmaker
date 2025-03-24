<?php

return  [
    'aacotroneo/laravel-saml2' => [
        'providers' => [
            0 => 'Aacotroneo\\Saml2\\Saml2ServiceProvider',
        ],
    ],
    'babenkoivan/elastic-client' => [
        'providers' => [
            0 => 'Elastic\\Client\\ServiceProvider',
        ],
    ],
    'babenkoivan/elastic-scout-driver' => [
        'providers' => [
            0 => 'Elastic\\ScoutDriver\\ServiceProvider',
        ],
    ],
    'codegreencreative/laravel-samlidp' => [
        'providers' => [
            0 => 'CodeGreenCreative\\SamlIdp\\LaravelSamlIdpServiceProvider',
        ],
    ],
    'darkaonline/l5-swagger' => [
        'aliases' => [
            'L5Swagger' => 'L5Swagger\\L5SwaggerFacade',
        ],
        'providers' => [
            0 => 'L5Swagger\\L5SwaggerServiceProvider',
        ],
    ],
    'igaster/laravel-theme' => [
        'aliases' => [
            'Theme' => 'Igaster\\LaravelTheme\\Facades\\Theme',
        ],
        'providers' => [
            0 => 'Igaster\\LaravelTheme\\themeServiceProvider',
        ],
    ],
    'jenssegers/agent' => [
        'aliases' => [
            'Agent' => 'Jenssegers\\Agent\\Facades\\Agent',
        ],
        'providers' => [
            0 => 'Jenssegers\\Agent\\AgentServiceProvider',
        ],
    ],
    'laravel/horizon' => [
        'aliases' => [
            'Horizon' => 'Laravel\\Horizon\\Horizon',
        ],
        'providers' => [
            0 => 'Laravel\\Horizon\\HorizonServiceProvider',
        ],
    ],
    'laravel/pail' => [
        'providers' => [
            0 => 'Laravel\\Pail\\PailServiceProvider',
        ],
    ],
    'laravel/scout' => [
        'providers' => [
            0 => 'Laravel\\Scout\\ScoutServiceProvider',
        ],
    ],
    'laravel/socialite' => [
        'aliases' => [
            'Socialite' => 'Laravel\\Socialite\\Facades\\Socialite',
        ],
        'providers' => [
            0 => 'Laravel\\Socialite\\SocialiteServiceProvider',
        ],
    ],
    'laravel/telescope' => [
        'providers' => [
            0 => 'Laravel\\Telescope\\TelescopeServiceProvider',
        ],
    ],
    'laravel/tinker' => [
        'providers' => [
            0 => 'Laravel\\Tinker\\TinkerServiceProvider',
        ],
    ],
    'laravel/ui' => [
        'providers' => [
            0 => 'Laravel\\Ui\\UiServiceProvider',
        ],
    ],
    'lavary/laravel-menu' => [
        'aliases' => [
            'Menu' => 'Lavary\\Menu\\Facade',
        ],
        'providers' => [
            0 => 'Lavary\\Menu\\ServiceProvider',
        ],
    ],
    'mateusjunges/laravel-kafka' => [
        'providers' => [
            0 => 'Junges\\Kafka\\Providers\\LaravelKafkaServiceProvider',
        ],
    ],
    'nesbot/carbon' => [
        'providers' => [
            0 => 'Carbon\\Laravel\\ServiceProvider',
        ],
    ],
    'nunomaduro/termwind' => [
        'providers' => [
            0 => 'Termwind\\Laravel\\TermwindServiceProvider',
        ],
    ],
    'openai-php/laravel' => [
        'providers' => [
            0 => 'OpenAI\\Laravel\\ServiceProvider',
        ],
    ],
    'pion/laravel-chunk-upload' => [
        'providers' => [
            0 => 'Pion\\Laravel\\ChunkUpload\\Providers\\ChunkUploadServiceProvider',
        ],
    ],
    'processmaker/connector-docusign' => [
        'providers' => [
            0 => 'ProcessMaker\\Package\\ConnectorDocusign\\PackageServiceProvider',
        ],
    ],
    'processmaker/connector-idp' => [
        'providers' => [
            0 => 'ProcessMaker\\Packages\\Connectors\\Idp\\PluginServiceProvider',
        ],
    ],
    'processmaker/connector-pdf-print' => [
        'providers' => [
            0 => 'ProcessMaker\\Packages\\Connectors\\Pdf\\PluginServiceProvider',
        ],
    ],
    'processmaker/connector-send-email' => [
        'providers' => [
            0 => 'ProcessMaker\\Packages\\Connectors\\Email\\PluginServiceProvider',
        ],
    ],
    'processmaker/connector-slack' => [
        'providers' => [
            0 => 'ProcessMaker\\Packages\\Connectors\\Slack\\PluginServiceProvider',
        ],
    ],
    'processmaker/docker-executor-lua' => [
        'providers' => [
            0 => 'ProcessMaker\\Package\\DockerExecutorLua\\DockerExecutorLuaServiceProvider',
        ],
    ],
    'processmaker/docker-executor-node' => [
        'providers' => [
            0 => 'ProcessMaker\\Package\\DockerExecutorNode\\DockerExecutorNodeServiceProvider',
        ],
    ],
    'processmaker/docker-executor-node-ssr' => [
        'providers' => [
            0 => 'ProcessMaker\\Package\\DockerExecutorNodeSSR\\DockerExecutorNodeSSRServiceProvider',
        ],
    ],
    'processmaker/docker-executor-php' => [
        'providers' => [
            0 => 'ProcessMaker\\Package\\DockerExecutorPhp\\DockerExecutorPhpServiceProvider',
        ],
    ],
    'processmaker/laravel-i18next' => [
        'providers' => [
            0 => 'ProcessMaker\\i18next\\ServiceProvider',
        ],
    ],
    'processmaker/package-ab-testing' => [
        'providers' => [
            0 => 'ProcessMaker\\Package\\PackageABTesting\\PackageServiceProvider',
        ],
    ],
    'processmaker/package-actions-by-email' => [
        'providers' => [
            0 => 'ProcessMaker\\Packages\\Connectors\\ActionsByEmail\\PluginServiceProvider',
        ],
    ],
    'processmaker/package-advanced-user-manager' => [
        'providers' => [
            0 => 'ProcessMaker\\Package\\AdvancedUserManager\\PackageServiceProvider',
        ],
    ],
    'processmaker/package-ai' => [
        'providers' => [
            0 => 'ProcessMaker\\Package\\PackageAi\\AiServiceProvider',
        ],
    ],
    'processmaker/package-analytics-reporting' => [
        'providers' => [
            0 => 'ProcessMaker\\Package\\PackageAnalyticsReporting\\PackageServiceProvider',
        ],
    ],
    'processmaker/package-api-testing' => [
        'providers' => [
            0 => 'ProcessMaker\\Package\\PackageApiTesting\\PackageServiceProvider',
        ],
    ],
    'processmaker/package-auth' => [
        'providers' => [
            0 => 'ProcessMaker\\Package\\Auth\\PackageServiceProvider',
        ],
    ],
    'processmaker/package-collections' => [
        'providers' => [
            0 => 'ProcessMaker\\Plugins\\Collections\\PluginServiceProvider',
        ],
    ],
    'processmaker/package-comments' => [
        'providers' => [
            0 => 'ProcessMaker\\Package\\PackageComments\\PackageServiceProvider',
        ],
    ],
    'processmaker/package-conversational-forms' => [
        'providers' => [
            0 => 'ProcessMaker\\Package\\ConversationalForms\\PackageServiceProvider',
        ],
    ],
    'processmaker/package-data-sources' => [
        'providers' => [
            0 => 'ProcessMaker\\Packages\\Connectors\\DataSources\\PluginServiceProvider',
        ],
    ],
    'processmaker/package-decision-engine' => [
        'providers' => [
            0 => 'ProcessMaker\\Package\\PackageDecisionEngine\\PackageServiceProvider',
        ],
    ],
    'processmaker/package-dynamic-ui' => [
        'providers' => [
            0 => 'ProcessMaker\\Package\\PackageDynamicUI\\PackageServiceProvider',
        ],
    ],
    'processmaker/package-files' => [
        'providers' => [
            0 => 'ProcessMaker\\Package\\Files\\FilesServiceProvider',
        ],
    ],
    'processmaker/package-googleplaces' => [
        'providers' => [
            0 => 'ProcessMaker\\Package\\GooglePlaces\\PluginServiceProvider',
        ],
    ],
    'processmaker/package-photo-video' => [
        'providers' => [
            0 => 'ProcessMaker\\Package\\PhotoVideo\\PluginServiceProvider',
        ],
    ],
    'processmaker/package-pm-blocks' => [
        'providers' => [
            0 => 'ProcessMaker\\Package\\PackagePmBlocks\\PackageServiceProvider',
        ],
    ],
    'processmaker/package-process-documenter' => [
        'providers' => [
            0 => 'ProcessMaker\\Package\\PackageProcessDocumenter\\PackageServiceProvider',
        ],
    ],
    'processmaker/package-process-optimization' => [
        'providers' => [
            0 => 'ProcessMaker\\Package\\PackageProcessOptimization\\PackageServiceProvider',
        ],
    ],
    'processmaker/package-product-analytics' => [
        'providers' => [
            0 => 'ProcessMaker\\Package\\ProductAnalytics\\PackageServiceProvider',
        ],
    ],
    'processmaker/package-projects' => [
        'providers' => [
            0 => 'ProcessMaker\\Package\\Projects\\PackageServiceProvider',
        ],
    ],
    'processmaker/package-rpa' => [
        'providers' => [
            0 => 'ProcessMaker\\Package\\Rpa\\PackageServiceProvider',
        ],
    ],
    'processmaker/package-savedsearch' => [
        'providers' => [
            0 => 'ProcessMaker\\Package\\SavedSearch\\SavedSearchServiceProvider',
        ],
    ],
    'processmaker/package-sentry' => [
        'providers' => [
            0 => 'ProcessMaker\\Package\\PackageSentry\\PackageServiceProvider',
        ],
    ],
    'processmaker/package-signature' => [
        'providers' => [
            0 => 'ProcessMaker\\Package\\Signature\\PluginServiceProvider',
        ],
    ],
    'processmaker/package-slideshow' => [
        'providers' => [
            0 => 'ProcessMaker\\Package\\PackageSlideshow\\PackageServiceProvider',
        ],
    ],
    'processmaker/package-testing' => [
        'providers' => [
            0 => 'ProcessMaker\\Package\\PackageTesting\\PackageServiceProvider',
        ],
    ],
    'processmaker/package-translations' => [
        'providers' => [
            0 => 'ProcessMaker\\Package\\Translations\\PackageServiceProvider',
        ],
    ],
    'processmaker/package-variable-finder' => [
        'providers' => [
            0 => 'ProcessMaker\\Package\\VariableFinder\\PackageServiceProvider',
        ],
    ],
    'processmaker/package-versions' => [
        'providers' => [
            0 => 'ProcessMaker\\Package\\Versions\\PluginServiceProvider',
        ],
    ],
    'processmaker/package-vocabularies' => [
        'providers' => [
            0 => 'ProcessMaker\\Package\\PackageVocabularies\\PackageServiceProvider',
        ],
    ],
    'processmaker/package-webentry' => [
        'providers' => [
            0 => 'ProcessMaker\\Package\\WebEntry\\WebEntryServiceProvider',
        ],
    ],
    'processmaker/packages' => [
        'providers' => [
            0 => 'ProcessMaker\\Package\\Packages\\PackageServiceProvider',
        ],
    ],
    'processmaker/pmql' => [
        'providers' => [
        ],
    ],
    'sentry/sentry-laravel' => [
        'aliases' => [
            'Sentry' => 'Sentry\\Laravel\\Facade',
        ],
        'providers' => [
            0 => 'Sentry\\Laravel\\ServiceProvider',
            1 => 'Sentry\\Laravel\\Tracing\\ServiceProvider',
        ],
    ],
    'simplesoftwareio/simple-qrcode' => [
        'aliases' => [
            'QrCode' => 'SimpleSoftwareIO\\QrCode\\Facades\\QrCode',
        ],
        'providers' => [
            0 => 'SimpleSoftwareIO\\QrCode\\QrCodeServiceProvider',
        ],
    ],
    'socialiteproviders/manager' => [
        'providers' => [
            0 => 'SocialiteProviders\\Manager\\ServiceProvider',
        ],
    ],
    'spatie/laravel-fractal' => [
        'aliases' => [
            'Fractal' => 'Spatie\\Fractal\\Facades\\Fractal',
        ],
        'providers' => [
            0 => 'Spatie\\Fractal\\FractalServiceProvider',
        ],
    ],
    'spatie/laravel-html' => [
        'aliases' => [
            'Html' => 'Spatie\\Html\\Facades\\Html',
        ],
        'providers' => [
            0 => 'Spatie\\Html\\HtmlServiceProvider',
        ],
    ],
    'spatie/laravel-ignition' => [
        'aliases' => [
            'Flare' => 'Spatie\\LaravelIgnition\\Facades\\Flare',
        ],
        'providers' => [
            0 => 'Spatie\\LaravelIgnition\\IgnitionServiceProvider',
        ],
    ],
    'spatie/laravel-medialibrary' => [
        'providers' => [
            0 => 'Spatie\\MediaLibrary\\MediaLibraryServiceProvider',
        ],
    ],
    'teamtnt/laravel-scout-tntsearch-driver' => [
        'providers' => [
            0 => 'TeamTNT\\Scout\\TNTSearchScoutServiceProvider',
        ],
    ],
];
