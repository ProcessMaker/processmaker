---
description: Export a ProcessMaker 4 Process that is BPMN 2.0 compliant.
---

# Export a BPMN-Compliant Process

## Overview

Export Processes from ProcessMaker 4 that are BPMN 2.0 compliant. The exported Process may then be imported to the same or another ProcessMaker 4 instance of the same product version and/or imported to a third-party BPMN 2.0 compliant tool. If the exported Process is imported to a third-party tool, all ProcessMaker features that are not part of the BPMN 2.0 specification are ignored.

The following ProcessMaker 4 components are exported if they are specified in the source Process:

* ProcessMaker Scripts configured for Script Task elements as well as their Script configurations
* ProcessMaker Screens configured for Task elements as well as routing rule expressions
* Sequence Flows and their routing rule expressions
* ProcessMaker Environment Variable containers, but not the sensitive data an Environment Variable contained in the original Process

ProcessMaker 4 does not export users from the original ProcessMaker 4 Process. Therefore, Task element assignments are not exported and must be configured if the Process is imported to another ProcessMaker 4 instance.

## Export a BPMN-Compliant Process

{% hint style="info" %}
Your user account or group membership must have the following permissions to export a Process:

* Processes: View Processes
* Processes: Export Processes

See the [Process](../../../processmaker-administration/permission-descriptions-for-users-and-groups.md#processes) permissions or ask your ProcessMaker Administrator for assistance.
{% endhint %}

## Related Topics

{% page-ref page="../what-is-a-process.md" %}

{% page-ref page="view-your-processes.md" %}

{% page-ref page="create-a-process.md" %}

{% page-ref page="import-a-bpmn-compliant-process.md" %}

{% page-ref page="search-for-a-process.md" %}

{% page-ref page="edit-the-name-description-category-or-status-of-a-process.md" %}

{% page-ref page="remove-a-process.md" %}

{% page-ref page="restore-a-process.md" %}

{% page-ref page="../process-categories.md" %}

{% page-ref page="../../process-design/" %}

