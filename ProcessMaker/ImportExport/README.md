## Terminology
- Source Instance: The instance of PM4 where you run the export.
- Target Instance: The instance of PM4 where you run the import.
- Manifest: An object that contains all the exported assets and information on how to link them when importing. The json encoded manifest is the export file the users downloads.
- Asset: Any model instance that has a UUID and can be exported. The model class has the trait `Exportable`
- Dependent: A child asset required by a parent asset to work correctly. For example, a Process asset will have a dependent User asset for the process manager if the process manager is set.
  
  
## Exporters
Exporter classes handle both the importing and exporting of an asset.

See the files in ProcessMaker/ImportExport/Exporters for examples.

In core they should be saved in the ProcessMaker/ImportExport/Exporters folder. In packages, they should be saved in the src/ImportExport folder.

They use a top-down approach to finding dependencies and adding them to the manifest.

A model asset is any model that has a UUID

### Methods
- `export()`
  - Logic to run when exporting the model. This is where you find all dependencies for a model that need to be exported and add them to the manifest using `addDependent()` and `addReference()`
- `import()`
  - Logic to run when importing the model. This is where you re-associate the dependencies to the model. Since IDs change across instances, this is where you will handle that.
- `handleDuplicateAttributes()` (optional)
  - Logic to run if a column must be unique and already exists on the target instance

See Exporting Assets and Importing Assets below for more information

## Exporter Extensions
Extensions are used in packages to extend an Exporter class defined in core
or in another package.

See the files in https://github.com/ProcessMaker/package-data-sources/tree/feature/FOUR-6769/src/ImportExport for examples.

These should be saved in the same folder as the Exporters and should have the same name as the Exporter it's extending but with the `Extension` suffix.

For example, if you are extending the ProcessExporter in core, you should
name the file and class `ProcessExporterExtension`

Note that a package can have it's own Exporter classes if the models are in the package.

### Methods
- `preExport()`
  - Returns a function to run before the export method of the Exporter
- `postExport()`
  - Returns a function to run after the export method of the Exporter
- `preImport()`
  - Returns a function to run before the import method of the Exporter
- `postImport()`
  - Returns a function to run after the import method of the Exporter

For example, the data sources package has:
```
use ProcessMaker\Packages\Connectors\DataSources\ImportExport\DataSourceExporter;
...
class ScreenExportExtension() {

    public function preExport() {
        $this->addDependent('data-source-watcher', $dataSource, DataSourceExporter::class);
    }

}
```
And PM4 Core has:
```
class ScreenExporter extends ExporterBase
{
    public function export()
    {
        $this->addDependent(DependentType::SCREENS, $nestedScreen, self::class);
    }
}
```
When the screen is exported, the screen exporter will run as if it was:
```
public function export()
{
    $this->addDependent('data-source-watcher', $dataSource, DataSourceExporter::class);

    $this->addDependent(DependentType::SCREENS, $nestedScreen, self::class);
}
```

In most cases, you can use the `pre` methods, but in some cases
you might want to do the logic after the Exporter's methods are run. In that cause, you can use `post` methods.

### Registering Extensions
In the `boot` method of the package service provider, register the extension like this:
```
use ProcessMaker\Packages\Connectors\DataSources\ImportExport\ScreenExporterExtension;
...
ScreenExporter::registerExtension(ScreenExporterExtension::class);
```

Note: You don't need to register Exporters. Exporters are imported by the ExporterExtension classes.

## Adding Dependencies
### Exporting Assets

Exporting is handled in a top-down manner by getting all an assets dependencies and running their respective exporters.

For example:
- A Process with a task will need to export the task Screen. The screen is a dependent of the process.
- If the screen has a script watcher, the script will need to be exported as a dependent of the screen.
- The script runs as a user, so that user will need to be exported as a dependent of the script.

- `addDependent('name', $modelInstance, ExporterClass::class, $metadata=null)`
  - When a model depends on another model that has a UUID, you use addDependent.
    For example, a Screen reference a Script (in watchers) and another Screen
  - Params:
    - name: Any string to reference the dependency. You will need to use the same string when importing. Unlike `addReference` below, you can use the same string and the dependencies will be pushed into the dependency stack.
    - $modelInstance: Any model with a UUID, for example a $screen or a $process
    - ExporterClass: The class name of the Exporter for the dependency
    - $metadata: Any data you would like to associate with the dependency to make importing easier. For example, the XPath to the element in a Process's bpmn.
  - Example in ScreenExporter:
    ```
    $this->addDependent('watcher-script', $watcherScript, ScriptExporter::class);
    $this->addDependent('nested-screen', $nestedScreen, self::class);
    ```
- `addReference('name', $data)`
  - When you need to add a dependency that does not have a UUID, use add reference.
    For example, a User has permissions that need to be exported.
  - Params:
    - name: Any string. You will need to use the same string when importing. Unlike `addDependency` above, the name should be unique in the context of the exporter. If the same exporter reuses a name, it will overwrite the previous set name.
    - Any variable you want to export.
  - Example in UserExporter
    ```
    $permissions = $this->model->permissions()->pluck('name')->toArray();
    $this->addReference('permissions', $permissions);
    ```

### Importing

Importing is done in the following order for each asset:
- Find an existing model (based on UUID) or create a new one.
- Set the attributes on the model from the imported manifest.
- Save the model.
- Run any preImport() methods from extensions
- Run the import() method in the Exporter
- Run any postImport() methods from extensions

The import() methods is where you link assets to their dependencies. Because IDs are often different 

- `$this->getDependents('name')`
  - Get the dependencies that were exported
    - Params:
      - name: The name given when exporting
    - Returns: An array of `Dependent` Objects (see below)
  - Example in UserExporter
    ```
    ```
- `$this->getReference('name')`
  - Get a reference that was exported
    - Params:
      - name: The name given when exporting
    - Returns: The data variable exported

Note: You will `$this->model->save()` some places in the code. This is from a previous version and is not necessary any more. These will be cleaned up later. The model is automatically saved before getting to he `import()` method.

You only need to save the model again if you made changes to it.

### The `Dependent` Object
The dependent object contains the reference model as it exists on the target instance.

Properties:
- model: The dependent model on the target instance. The model has already been saved when you access it here.
- meta: The metadata that was exported with the dependent object.
- originalId: The original ID as it was on the source instance. To get the ID on the target instance, use `$dependent->model->id`


## Options

When importing, you can pass an `Options` object to determine
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

Exporting also accepts an `Options` object however the only mode supported is `discard`. When set for an asset, the asset will not be exported.

### Example