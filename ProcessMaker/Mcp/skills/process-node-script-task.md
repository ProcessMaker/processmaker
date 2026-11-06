# Skill: Script Task (`bpmn:ScriptTask`)

Use when adding or configuring a Script Task that runs a ProcessMaker Script asset.

## Identity

| | |
|--|--|
| Modeler id | `processmaker-modeler-script-task` |
| BPMN type | `bpmn:ScriptTask` |
| Default | `scriptRef: null` |

## Inspectors → attributes

| UI | Attr | Required | Notes |
|----|------|----------|-------|
| Name | `name` | yes | Human label only — **not** the script lookup key |
| Script | `pm:scriptRef` | **yes** | Script id or `${script:exact-title}` |
| Script Configuration | `config` / `pm:config` JSON | no | Passed as `$config` into the executor |
| Error Handling | `pm:errorHandling` JSON | no | timeout, retryAttempts, retryWaitTime, notify manager |
| Loop | `loopCharacteristics` | no | Multi-instance |

## Example

```xml
<bpmn:scriptTask id="node_30" name="Calculate Credits"
  pm:scriptRef="${script:calculate-credit}"
  pm:config="{&quot;mode&quot;:&quot;strict&quot;}"
  pm:errorHandling="{&quot;timeout&quot;:60,&quot;retryAttempts&quot;:0,&quot;retryWaitTime&quot;:5}" />
```

## Data in / out

Runtime (`RunScriptTask`):

- **In:** `DataManager::getData($token)` — full request data (+ system vars)
- **Config:** decoded JSON → second argument (`$config`)
- **Out:** script **return value must be an associative array**; merged into request data

PHP script contract (typical):

```php
// expects $data, $config
return ['result' => $value];
```

## Pitfalls

1. Missing `scriptRef` — task will not run
2. Invalid JSON in config
3. Returning a non-array breaks merge
4. Script language/executor must match the Script asset
5. Task `name` ≠ Script `title`; portable key is `${script:title}`
6. Long-running scripts need `errorHandling.timeout`
7. Publish the Script with upsert-script **before** deploying the process
