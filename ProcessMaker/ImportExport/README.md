## Terminology
- Source Instance
- Target Instance
- Manifest
- 

## Exporters
Exporter classes handle both the importing and exporting of a model asset.

They use a top-down approach to finding dependencies and adding them to the manifest.

A model asset is any model that has a UUID

### Methods
- `export()`
  - Logic to run when exporting the model
- `import()`
  - Logic to run when importing the model
- `handleDuplicateAttributes()` (optional)
  - Logic to run if a column must be unique and already exists on the target instance

## Exporter Extensions

Extensions are used in packages to extend an Exporter class defined in core
or in another package.

Note that a package will have it's own Exporter class if the database model was added
by the same package.

### Methods
- `preExport()`
  - Run before the export method of the Exporter
- `postExport()`
  - Run after the export method of the Exporter
- `preImport()`
  - Run before the import method of the Exporter
- `postImport()`
  - Run after the import method of the Exporter

In most cases, you can use either the `pre` or the `post` methods, but in some cases
you might want to do the logic before or after the Exporter's methods are run.

## Adding Dependencies
### Exporting
- `addDependent('name', $modelInstance, ExporterClass::class, $metadata=null)`
  - When a model depends on another model that has a UUID, you use addDependent.
    For example, a Screen reference a Script (in watchers) and another Screen
  - Params:
    - name: Any string to reference the dependency. You will need to use the same string when importing. Unlike `addReference` below, you can use the same string and the dependencies will be pushed into the dependency stack.
    - $modelInstance: Any model with a UUID, for example a $screen or a $process
    - ExporterClass: The class name of the Exporter for the dependency
    - $metadata: Any data you would like to associate with the dependency to make importing easier.
- `addReference('name', $data)`
  - When you need to add a dependency that does not have a UUID, use add reference.
    For example, a User has permissions that need to be exported.
  - Params:
    - name: Any string. You will need to use the same string when importing. Unlike `addDependency` above, the name should be unique in the context of the exporter. If the same exporter reuses a name, it will overwrite the previous set name.
    - Any variable you want to export.

### Importing
- `$this->getDependents('name')`
  - Get the dependencies that were exported
    - Params:
      - name: The name given when exporting
    - Returns: An array of Dependency Objects (see below)
- `$this->getReference('name')`
  - Get a reference that was exported
    - Params:
      - name: The name given when exporting
    - Returns: The data variable exported

### The Dependency Object
The dependency object contains the reference model as it exists on the target instance.


## Options

The importer accepts an `Options` object to determine
how to handle each asset.

Pass an array to the constructor in the format of `uuid => ['mode' => mode]]` to
set the import mode for each asset.

### Modes
- `update` (default)
  - Update the existing asset if it exists on the target instance
- `discard`
  - Ignore this asset when importing. Do not modify the asset on the target instance.
- `copy`
  - Create a new copy of the asset with a new UUID. Link all other assets in the
    import to the copy. Do not modify the matching asset on the target instance.
- `new`
  - Not user selectable. Create the asset when it does not exist on the target instance.

The Exporter also accepts an `Options` object however the only mode supported is `discard`. When set for an asset, the asset will not be exported.

### Example