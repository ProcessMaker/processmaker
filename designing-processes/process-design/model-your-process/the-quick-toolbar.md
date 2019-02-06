---
description: Connect Process model elements by setting the Sequence Flow between them.
---

# Set and Delete Sequence Flow Between Elements

## Set the Sequence Flow from One Element to Another

{% hint style="info" %}
### Don't Know What Sequence Flow Is?

See [Process Modeling Element Descriptions](process-modeling-element-descriptions.md) for a description of [Sequence Flow](process-modeling-element-descriptions.md#sequence-flow).

### Permissions Required to Do This Task

Your user account or group membership must have the following permissions to set Sequence Flow in the Process model:

* Processes: View Processes
* Processes: Edit Processes

Ask your ProcessMaker Administrator for assistance if necessary.
{% endhint %}

Connecting a Process model element to another sets the Sequence Flow between the connected elements. The direction in which the Sequence Flow points implies how [Request](../../../using-processmaker/requests/what-is-a-request.md) data is conveyed and utilized in the Process model. As a best practice indicate a consistent direction of Sequence Flows, either left to right or top to bottom, to make modeled Processes easier to understand.

Follow these steps to set the Sequence Flow from one element to another:

1. ​[View your Processes](../../viewing-processes/view-the-list-of-processes/view-your-processes.md#view-all-processes). The **Processes** page displays.
2. Click the **Open Modeler** icon![](../../../.gitbook/assets/open-modeler-edit-icon-processes-page-processes.png)to edit the selected Process model. Process Modeler displays.
3. Select the Process model element from which you want to set the Sequence Flow. Available options display to the right of the selected element.  

   ![](../../../.gitbook/assets/sequence-flow-indicator-process-modeler-processes.png)

4. Click the **Sequence Flow** icon![](../../../.gitbook/assets/sequence-flow-icon-process-modeler-processes.png).
5. Click the Process model element in which to set the Sequence Flow. The Sequence Flow between the elements is established.  

   ![](../../../.gitbook/assets/sequence-flow-connecting-elements-process-modeler-processes.png)

{% hint style="info" %}
Text annotations and Pool elements do not participate in Sequence Flow.

An End Event terminates the flow of a Request for that Process. Therefore, an End Event cannot have an outgoing Sequence Flow.
{% endhint %}

## Delete the Sequence Flow Between Two Elements

{% hint style="info" %}
Your user account or group membership must have the following permissions to delete the Sequence Flow between two elements in the Process model:

* Processes: View Processes
* Processes: Edit Processes

Ask your ProcessMaker Administrator for assistance if necessary.
{% endhint %}

Follow these steps to delete the Sequence Flow between two elements:

1. Select the Sequence Flow to be deleted between two elements.  

   ![](../../../.gitbook/assets/delete-sequence-flow-process-modeler-processes.png)

2. Click the **Delete** icon![](../../../.gitbook/assets/remove-icon.png). The Sequence Flow between the two elements is deleted.

## Related Topics

{% page-ref page="process-modeling-element-descriptions.md" %}

{% page-ref page="../../viewing-processes/view-the-list-of-processes/view-your-processes.md" %}

{% page-ref page="../../viewing-processes/create-a-process.md" %}

