---
description: These are brief descriptions about commonly used Process modeling elements.
---

# Process Modeling Element Descriptions

The following are brief descriptions about each Process modeling element. See the [BPMN specification](https://www.omg.org/spec/BPMN/2.0/About-BPMN/) for more information.

## Events

An Event represents a milestone in the Process model.

### Start Event

A Start Event indicates where a modeled Process starts. A Start Event begins the flow of a Request for that Process. Therefore, a Start Event cannot have an incoming [Sequence Flow](process-modeling-element-descriptions.md#sequence-flow). A Process model can have multiple Start Events.

In Process Modeler, the Start Event element is labeled as "Start Event" in the **BPMN** panel as highlighted below.

![Start Event element in the BPMN panel of Process Modeler](../../../.gitbook/assets/bpmn-panel-start-event-process-modeler-processes.png)

Below is a Start Event element when it has been placed into a Process model.

![Start Event element](../../../.gitbook/assets/start-event-element-process-modeler-processes.png)

{% hint style="info" %}
See [Add and Configure Event Elements](add-and-configure-an-event-element.md#add-a-start-event-element).
{% endhint %}

### End Event

An End Event indicates where a modeled Process normally ends when abnormal events do not terminate a [Request](../../../using-processmaker/requests/) for that Process \(such as a canceled Request\). An End Event terminates the flow of a Request for that Process. Therefore, an End Event cannot have an outgoing [Sequence Flow](process-modeling-element-descriptions.md#sequence-flow). A Process model can have multiple End Events.

In Process Modeler, the End Event element is labeled as "End Event" in the **BPMN** panel as highlighted below.

![End Event element in the BPMN panel of Process Modeler](../../../.gitbook/assets/bpmn-panel-end-event-process-modeler-processes.png)

Below is an End Event element when it has been placed into a Process model.

![End Event element](../../../.gitbook/assets/end-event-element-process-modeler-processes.png)

{% hint style="info" %}
See [Add and Configure Event Elements](add-and-configure-an-event-element.md#add-an-end-event-element).
{% endhint %}

## Tasks

A task represents an activity to be performed either by a [Request](../../../using-processmaker/requests/what-is-a-request.md) participant or a [ProcessMaker Script](../../scripts/).

### User Task

A Task element represents an activity to be performed by a person participating in a [Request](../../../using-processmaker/requests/what-is-a-request.md). The Request participant assigned that task might be determined by the conditions in a Request's workflow.

People perform Task activities through ProcessMaker Screens as digital [forms](../../design-forms/screens-builder/types-for-screens.md#forms) and [displays](../../design-forms/screens-builder/types-for-screens.md#display). ProcessMaker Screens are designed in [Screens Builder](../../design-forms/screens-builder/).

In Process Modeler, the Task element is labeled as "Task" in the **BPMN** panel as highlighted below.

![Task element in the BPMN panel of Process Modeler](../../../.gitbook/assets/bpmn-panel-task-process-modeler-processes.png)

Below is a Task element when it has been placed into a Process model.

![Task element](../../../.gitbook/assets/task-element-process-modeler-processes.png)

{% hint style="info" %}
See [Add and Configure Task Elements](add-and-configure-task-elements.md).
{% endhint %}

### Script Task

A Script Task is an activity to be performed by a ProcessMaker Script.

ProcessMaker Scripts are designed in [Scripts Editor](../../scripts/scripts-editor.md). ProcessMaker Scripts are independent of modeled processes: any ProcessMaker Script can be reused in any modeled process in your organization. This architecture allows Process Owners to focus on process modeling in a no-code environment while ProcessMaker Developers develop reusable ProcessMaker Scripts. ProcessMaker Scripts can leverage Request-level variable data as well as variable data designed in ProcessMaker Screens from [Screens Builder](../../design-forms/screens-builder/).

In Process Modeler, the Script Task element is labeled as "Script Task" in the **BPMN** panel as highlighted below.

![Script Task element in the BPMN panel of Process Modeler](../../../.gitbook/assets/bpmn-panel-script-task-process-modeler-processes.png)

Below is a Script Task element when it has been placed into a Process model.

![Script Task element](../../../.gitbook/assets/script-task-element-process-modeler-processes.png)

{% hint style="info" %}
See [Add and Configure Script Task Elements](add-and-configure-script-task-elements.md).
{% endhint %}

## Exclusive Gateway

An Exclusive Gateway represents a decision that creates alternative paths within a [Request's](../../../using-processmaker/requests/) workflow. During a Request's workflow for that Process, only one outgoing path from the Exclusive Gateway can be taken. An Exclusive Gateway can have two or more outgoing Sequence Flows.

In Process Modeler, the Exclusive Gateway element is labeled as "Exclusive Gateway" in the **BPMN** panel as highlighted below.

![Exclusive Gateway element in the BPMN panel of Process Modeler](../../../.gitbook/assets/bpmn-panel-exclusive-gateway-process-modeler-processes.png)

Below is an Exclusive Gateway element when it has been placed into a Process model.

![Exclusive Gateway element](../../../.gitbook/assets/exclusive-gateway-element-process-modeler-processes.png)

{% hint style="info" %}
See [Add and Configure Exclusive Gateway Elements](add-and-configure-exclusive-gateway-elements.md).
{% endhint %}

## Text Annotation

Text annotation is human-readable text in a modeled process provides description regarding the process. Text annotation performs no functional role in process Requests or routing.

In Process Modeler, the Text Annotation element is labeled as "Text Annotation" in the **BPMN** panel as highlighted below.

![Text Annotation element in the BPMN panel of Process Modeler](../../../.gitbook/assets/bpmn-panel-text-annotation-process-modeler-processes.png)

Below is a Text Annotation element when it has been placed into a Process model.

![Text Annotation element](../../../.gitbook/assets/text-annotation-process-modeler-processes.png)

{% hint style="info" %}
See [Add and Configure Text Annotation Elements](add-and-configure-text-annotation-elements.md).
{% endhint %}

## Sequence Flow

Sequence Flow represents intended workflow in a modeled Process. As a best practice indicate a consistent direction of Sequence Flows, either left to right or top to bottom, to make modeled Processes easier to understand.

In Process Modeler, a Sequence Flow indicator displays when you click an element in the Process model. Below the arrow icon represents a Sequence Flow indicator in Process Modeler.

![Sequence Flow indicator on a selected process element](../../../.gitbook/assets/sequence-flow-indicator-process-modeler-processes.png)

{% hint style="info" %}
Text annotations and Pool elements do not participate in Sequence Flow.

An End Event terminates the flow of a Request for that Process. Therefore, an End Event cannot have an outgoing Sequence Flow.
{% endhint %}

The Sequence Flow indicates how two Process elements are connected. Below are two Process elements connected in Process Modeler.

![Two Process elements connected by the Sequence Flow](../../../.gitbook/assets/sequence-flow-connecting-elements-process-modeler-processes.png)

{% hint style="info" %}
See [Connect and Remove Elements](the-quick-toolbar.md).
{% endhint %}

## Organize Process Participants

BPMN 2.0 provides graphical representations to organize participants in a modeled Process.

### Pool

A Pool represents an organization or entity involved in a Process modeled. The pool might apply to a  specific role \("Human Resources"\), entity \(such as a company\) or a general relationship \(such as a buyer, seller, or manufacturer\). A Pool can even reference another modeled Process.

In Process Modeler, the Pool element is labeled as "Pool" in the **BPMN** panel as highlighted below.

![Pool element in the BPMN panel of Process Modeler](../../../.gitbook/assets/bpmn-panel-pool-process-modeler-processes.png)

Below is a Pool element when it has been placed into a Process model.

![Pool element containing a modeled Process](../../../.gitbook/assets/pool-element-process-modeler-processes.png)

### Lane

A Lane represents a partition within a [Pool](process-modeling-element-descriptions.md#pool) element. Each Lane indicates individual roles and/or participants that perform tasks within the Pool. Text within the Lane indicates the participant in the Process model. Any elements within the Lane indicate that the participant is the actor or is responsible for performing tasks in the Process. Furthermore, [Sequence Flows](process-modeling-element-descriptions.md#sequence-flow) between elements in other Pools or  Lanes indicate with which other Process participants that Lane interacts.

Below is a Pool element with three Lane elements when it has been placed into a Process model. Each lane indicates roles within the overall organization.

![Pool element with three Lane elements that indicate roles within the organization](../../../.gitbook/assets/pool-element-with-lanes-process-modeler-processes.png)

## Related Topics

{% page-ref page="../../../using-processmaker/requests/" %}

{% page-ref page="../../design-forms/screens-builder/types-for-screens.md" %}

{% page-ref page="../../design-forms/screens-builder/" %}

{% page-ref page="../../scripts/scripts-editor.md" %}

