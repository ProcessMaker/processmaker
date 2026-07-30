/**
 * Exportable asset types that can be linked to an environment variable.
 * apiPath is the local API listing endpoint used by the asset picker.
 */
export default [
  {
    class: "ProcessMaker\\Models\\Process",
    label: "Process",
    apiPath: "processes",
    nameField: "name",
  },
  {
    class: "ProcessMaker\\Models\\Screen",
    label: "Screen",
    apiPath: "screens",
    nameField: "title",
  },
  {
    class: "ProcessMaker\\Models\\Script",
    label: "Script",
    apiPath: "scripts",
    nameField: "title",
  },
  {
    class: "ProcessMaker\\Plugins\\Collections\\Models\\Collection",
    label: "Collection",
    apiPath: "collections",
    nameField: "name",
  },
  {
    class: "ProcessMaker\\Packages\\Connectors\\DataSources\\Models\\DataSource",
    label: "Data Connector",
    apiPath: "data_sources",
    nameField: "name",
  },
  {
    class: "ProcessMaker\\Package\\PackageDecisionEngine\\Models\\DecisionTable",
    label: "Decision Table",
    apiPath: "decision_tables",
    nameField: "title",
  },
  {
    class: "ProcessMaker\\Package\\PackageAi\\Models\\FlowGenie",
    label: "FlowGenie",
    apiPath: "package-ai/flow_genies",
    nameField: "name",
  },
  {
    class: "ProcessMaker\\Package\\PackagePmBlocks\\Models\\PmBlock",
    label: "PM Block",
    apiPath: "pm-blocks",
    nameField: "name",
  },
];
