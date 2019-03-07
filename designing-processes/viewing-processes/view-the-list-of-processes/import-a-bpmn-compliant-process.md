---
description: Import a process into ProcessMaker 4 if it is BPMN 2.0 compliant.
---

# Import a BPMN-Compliant Process

## Overview

Import Processes into ProcessMaker 4 if the Process to be imported is BPMN 2.0 compliant.

### Import Valid ProcessMaker 4 Processes

Import valid ProcessMaker 4 Processes that have been [exported](export-a-bpmn-compliant-process.md) from ProcessMaker 4 if the Process was exported from the same ProcessMaker 4 version. A valid ProcessMaker 4 Process is [BPMN 2.0 compliant](https://www.omg.org/spec/BPMN/2.0/About-BPMN/). The Process can be imported from the same or different ProcessMaker 4 instance.

The following ProcessMaker 4 components are imported from another valid ProcessMaker 4 Process if they are specified in that Process:

* ProcessMaker Scripts configured for Script Task elements as well as their Script configurations
* ProcessMaker Screens configured for Task elements as well as routing rule expressions
* Sequence Flows and their routing rule expressions
* ProcessMaker Environment Variable containers, but not the sensitive data an Environment Variable contained in the original Process

ProcessMaker 4 does not import users from the original ProcessMaker 4 Process. Therefore, Task element assignments are not imported and must be configured after importing the Process.

### Import BPMN 2.0 Compliant Processes from Third-Party Tools

ProcessMaker 4 supports importing third-party processes if those processes are compliant to the [BPMN 2.0 specification](https://www.omg.org/spec/BPMN/2.0/About-BPMN/). When importing BPMN 2.0 compliant processes, ProcessMaker imports the process model that can be opened in [Process Modeler](../../process-design/what-is-process-modeling.md). ProcessMaker 4 ignores any functionality that the third-party tool may support that is not part of the BPMN 2.0 specification.

Despite that the imported Process is BPMN 2.0 compliant, you may need to edit the Process in Process Modeler for ProcessMaker 4 specific functionality.

## Import a BPMN 2.0 Compliant Process

{% hint style="info" %}
Your user account or group membership must have the following permissions to import a Process:

* Processes: View Processes
* Processes: Import Processes

See the [Process](../../../processmaker-administration/permission-descriptions-for-users-and-groups.md#processes) permissions or ask your ProcessMaker Administrator for assistance.
{% endhint %}

## Related Topics

{% page-ref page="../what-is-a-process.md" %}

{% page-ref page="view-your-processes.md" %}

{% page-ref page="create-a-process.md" %}

{% page-ref page="search-for-a-process.md" %}

{% page-ref page="edit-the-name-description-category-or-status-of-a-process.md" %}

{% page-ref page="export-a-bpmn-compliant-process.md" %}

{% page-ref page="remove-a-process.md" %}

{% page-ref page="restore-a-process.md" %}

{% page-ref page="../process-categories.md" %}

{% page-ref page="../../process-design/what-is-process-modeling.md" %}

