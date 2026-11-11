# Skill: Decision Task connector

Use when adding a Decision Task Service Task (`package-decision-engine`).

## Identity

| | |
|--|--|
| Modeler id | `processmaker-decision-engine-tables` |
| BPMN type | `bpmn:ServiceTask` |
| `implementation` | `package-decision-engine/decision-table` |
| Config | `pm:config` (JSON string) |
| Package | `package-decision-engine` |

## Discover ids

- Decision table ids from Decision Tables UI / package APIs.
- Do not invent table ids; `name` is a display snapshot.

## `pm:config` shape

```json
{
  "decisionTables": [
    {
      "id": 3,
      "name": "Eligibility",
      "hitPolicy": "FIRST",
      "executeConditionally": false,
      "condition": ""
    }
  ],
  "decTableOutboundMappings": [
    { "requestVar": "age", "tableVar": "Age" }
  ],
  "decTableResponseMappings": [
    { "tableOutVar": "result", "requestVar": "decision" }
  ]
}
```

| Key | Required | Notes |
|-----|----------|--------|
| `decisionTables` | yes | Ordered tables to run |
| `decTableOutboundMappings` | usually | Request → table inputs |
| `decTableResponseMappings` | usually | Table outputs → request |

### `decisionTables[]` item

| Field | Notes |
|-------|--------|
| `id` | Decision table DB id |
| `name` | Title snapshot |
| `hitPolicy` | `FIRST` \| `UNIQUE` \| `ANY` \| `SUM` \| `MIN` \| `MAX` \| `COUNT` \| `RULE ORDER` |
| `executeConditionally` | boolean |
| `condition` | expression when conditional |

## Example BPMN

```xml
<bpmn:serviceTask id="node_dt" name="Decision Task"
  implementation="package-decision-engine/decision-table"
  pm:config="{&quot;decisionTables&quot;:[{&quot;id&quot;:3,&quot;name&quot;:&quot;Eligibility&quot;,&quot;hitPolicy&quot;:&quot;FIRST&quot;,&quot;executeConditionally&quot;:false,&quot;condition&quot;:&quot;&quot;}],&quot;decTableOutboundMappings&quot;:[{&quot;requestVar&quot;:&quot;age&quot;,&quot;tableVar&quot;:&quot;Age&quot;}],&quot;decTableResponseMappings&quot;:[{&quot;tableOutVar&quot;:&quot;result&quot;,&quot;requestVar&quot;:&quot;decision&quot;}]}" />
```

## Pitfalls

1. Empty `decisionTables` → no-op at runtime.
2. Map both inbound and outbound vars the tables expect.
3. Package must be installed.
