---
description: Add and configure Exclusive Gateway elements in your Process model.
---

# Add and Configure Exclusive Gateway Elements

## Add an Exclusive Gateway Element

{% hint style="info" %}
### Don't Know What an Exclusive Gateway Element Is?

See [Process Modeling Element Descriptions](process-modeling-element-descriptions.md) for a description of the [Exclusive Gateway](process-modeling-element-descriptions.md#exclusive-gateway) element.

### Permissions Required to Do This Task

Your user account or group membership must have the following permissions to add an Exclusive Gateway element to the Process model:

* Processes: View Processes
* Processes: Edit Processes

Ask your ProcessMaker Administrator for assistance if necessary.
{% endhint %}

Follow these steps to add an Exclusive Gateway element to the Process model:

1. [View your Processes](https://processmaker.gitbook.io/processmaker-4-community/-LPblkrcFWowWJ6HZdhC/~/drafts/-LRhVZm0ddxDcGGdN5ZN/primary/designing-processes/viewing-processes/view-the-list-of-processes/view-your-processes#view-all-processes). The **Processes** page displays.
2. [Create a new Process](../../viewing-processes/create-a-process.md) or click the **Open Modeler** icon![](../../../.gitbook/assets/open-modeler-edit-icon-processes-page-processes.png)to edit the selected Process model. Process Modeler displays.
3. Locate the **Exclusive Gateway** element ![](../../../.gitbook/assets/exclusive-gateway-bpmn-side-bar-process-modeler-processes.png) in the **BPMN** panel. Drag and drop the element to where in the Process model you want to place it. If a Pool element is in your Process model, the Exclusive Gateway element cannot be placed outside of the Pool element. The element has been added to the Process model.

![Exclusive Gateway element](../../../.gitbook/assets/exclusive-gateway-process-modeler-processes.png)

After the element is placed into the Process model, you may move it by selecting it, hold the cursor, and then dragging it to the new location.

{% hint style="warning" %}
If the element is placed inside of a Pool element, the Exclusive Gateway element cannot be moved outside of the Pool element. If you attempt to do so, Process Modeler places the Exclusive Gateway element inside the Pool element closest to where you attempted to move it.
{% endhint %}

## Configure an Exclusive Gateway Element

{% hint style="info" %}
### Looking for Information How to Configure Exclusive Gateway Conditions?

See [Configure the Sequence Flow for Exclusive Gateway Elements](the-quick-toolbar.md#configure-the-sequence-flow-for-exclusive-gateway-elements).

### Permissions Required to Do This Task

Your user account or group membership must have the following permissions to configure an Exclusive Gateway element:

* Processes: View Processes
* Processes: Edit Processes

Ask your ProcessMaker Administrator for assistance if necessary.
{% endhint %}

### Edit the Identifier Value

Process Modeler automatically assigns a unique value to each Process element added to a Process model. However, an element's identifier value can be changed as long as it is unique.

{% hint style="warning" %}
All identifier values for all elements in the Process model must be unique.
{% endhint %}

Follow these steps to edit the identifier value for an Exclusive Gateway element:

1. Select the Exclusive Gateway element in which to edit its identifier value. The current Exclusive Gateway identifier value displays in the **Identifier** field in the right side bar.
2. In the **Identifier** field, edit the selected Exclusive Gateway element's identifier value and then press **Enter**. The identifier value is changed.

### Edit the Element Name

An element name is a human-readable reference for a Process element. Process Modeler automatically assigns the name of a Process element with its element type. However, an element's name can be changed.

Follow these steps to edit the name for an Exclusive Gateway element:

1. Select the Exclusive Gateway element in which to edit its name. The current name displays in the **Name** field in the right side bar.
2. In the **Name** field, edit the selected Exclusive Gateway element's name and then press **Enter**. The element's name is changed.

## Related Topics

{% page-ref page="process-modeling-element-descriptions.md" %}

{% page-ref page="../../viewing-processes/view-the-list-of-processes/view-your-processes.md" %}

{% page-ref page="../../viewing-processes/create-a-process.md" %}

