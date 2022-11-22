
# Entity
A type of object to export

Schema:
- props
    - Array params
    - Array references
    - Array warnings (optional)
- methods:
  - save
  - load

## example: ModelEntity
  - Inherits: Entity
  - params: ["class" => "\ProcessMaker\Models\Screen"]
  - references:
    - Reference(entity: Screen, strategy: ScreenConfig)
    - Reference(entity: Script, strategy: WatcherConfig)
    

## example: GlobalSignalEntity
  - Inherits: Entity
  - params: ["id" => "my_signal"]
  - references:
    - Reference(entity:Process, stragegy: GlobalSignalsInProcess)

## example: BpmnNodeEntity
  - Inherits: Entity
  - params: ["tag" => "CallActivity"]
  - references:
    - Reference(entity: Process strategy: CallActivity)

# Strategy
Responsible for:
- Finding the reference in the entity for export
- Setting the reference on the entity for import

Schema:
- props
  - Source
  - Reference
  - params (optional)
- methods
  - import
  - export

## Exmaple: ScreeenConfig
- Inherits: JsonConfig (which inherits Strategy)
- props
  - source: Entity $screen # parent screen
  - destination: Entity $screen # nested screen
  - params: ['component' => 'FormNestedScreen', 'field' => 'screen']

## Exmaple: WatcherConfig
- Inherits: JsonConfig (which inherits Strategy)
- props
  - source: Entity:Screen
  - destination: Entity:DataSource

## Exmaple: Categories
- Inherits: Strategy
- props
  - source: Entity:Screen
  - destination: Entity:ScreenCategory

## Exmaple: ForeignKey
- Inherits: Strategy
- props
  - source: Entity:Script
  - destination: Entity:User
- params: ['source' => 'run_as_user_id', 'reference' => 'id']


# Reference
Generated in memory only (no php file). An Entity and and Strategy.

- props
  - Entity
  - Strategy
  - Props (optional)