---
description: Add and configure Text Annotation elements in your Process model.
---

# Add and Configure Text Annotation Elements

## Add a Text Annotation Element

{% hint style="info" %}
### Don't Know What a Text Annotation Element Is?

See [Process Modeling Element Descriptions](process-modeling-element-descriptions.md) for a description of the [Text Annotation](process-modeling-element-descriptions.md#text-annotation) element.

### Permissions Required to Do This Task

Your user account or group membership must have the following permissions to add a Text Annotation element to the Process model:

* Processes: View Processes
* Processes: Edit Processes

See the [Process](../../../processmaker-administration/permission-descriptions-for-users-and-groups.md#processes) permissions or ask your ProcessMaker Administrator for assistance.
{% endhint %}

Follow these steps to add a Text Annotation element to the Process model:

1. [View your Processes](https://processmaker.gitbook.io/processmaker-4-community/-LPblkrcFWowWJ6HZdhC/~/drafts/-LRhVZm0ddxDcGGdN5ZN/primary/designing-processes/viewing-processes/view-the-list-of-processes/view-your-processes#view-all-processes). The **Processes** page displays.
2. [Create a new Process](../../viewing-processes/view-the-list-of-processes/create-a-process.md) or click the **Open Modeler** icon![](../../../.gitbook/assets/open-modeler-edit-icon-processes-page-processes.png)to edit the selected Process model. Process Modeler displays.
3. Locate the **Text Annotation** element ![](../../../.gitbook/assets/text-annotation-bpmn-side-bar-process-modeler-processes.png) in the **BPMN** panel. Drag and drop the element to where in the Process model you want to place it. If a Pool element is in your Process model, the Text Annotation element cannot be placed outside of the Pool element. The element has been added to the Process model.

![Text Annotation element](../../../.gitbook/assets/text-annotation-process-modeler-processes.png)

After the element is placed into the Process model, you may move it by selecting it, hold the cursor, and then dragging it to the new location.

{% hint style="warning" %}
If the element is placed inside of a Pool element, the Text Annotation element cannot be moved outside of the Pool element. If you attempt to do so, Process Modeler places the Text Annotation element inside the Pool element closest to where you attempted to move it.
{% endhint %}

## Configure a Text Annotation Element

{% hint style="info" %}
Your user account or group membership must have the following permissions to configure a Text Annotation element:

* Processes: View Processes
* Processes: Edit Processes

See the [Process](../../../processmaker-administration/permission-descriptions-for-users-and-groups.md#processes) permissions or ask your ProcessMaker Administrator for assistance.
{% endhint %}

### Edit the Identifier Value

Process Modeler automatically assigns a unique value to each Process element added to a Process model. However, an element's identifier value can be changed as long as it is unique.

{% hint style="warning" %}
All identifier values for all elements in the Process model must be unique.
{% endhint %}

Follow these steps to edit the identifier value for a Text Annotation element:

1. Select the Text Annotation element in which to edit its identifier value. The current Text Annotation identifier value displays in the **Identifier** field in the right side bar.
2. In the **Identifier** field, edit the selected Text Annotation element's identifier value and then press **Enter**. The identifier value is changed.

### Edit the Annotation Description

Process Modeler automatically assigns a default value to a new Text Annotation element. However, change the annotation description to provide context to your Process model.

Follow these steps to edit the annotation description for a Text Annotation element:

1. Select the Text Annotation element in which to edit its annotation description. The current description displays in the **Annotation Description** field in the right side bar.
2. In the **Annotation Description** field, edit the selected Text Annotation element's description and then press **Enter**. The annotation description is changed.

## Related Topics

{% page-ref page="process-modeling-element-descriptions.md" %}

{% page-ref page="../../viewing-processes/view-the-list-of-processes/view-your-processes.md" %}

{% page-ref page="../../viewing-processes/view-the-list-of-processes/create-a-process.md" %}

