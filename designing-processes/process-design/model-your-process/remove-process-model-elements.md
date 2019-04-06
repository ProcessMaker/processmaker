---
description: Delete elements from your Process model.
---

# Delete Process Model Elements

## Delete Process Model Elements

Deleting a Process model element also deletes any Sequence Flow and/or Message Flow elements incoming to or outgoing from that element. For example, if a Process model element is deleted that has both incoming and outgoing Sequence Flows, the disconnected Sequence Flows must be reconnected for the remaining elements.

{% hint style="info" %}
### Don't Know What Process Model Elements Are?

See [Process Modeling Element Descriptions](process-modeling-element-descriptions.md).

### Permissions Required

Your user account or group membership must have the following permissions to delete elements from the Process model:

* Processes: View Processes
* Processes: Edit Processes

See the [Process](../../../processmaker-administration/permission-descriptions-for-users-and-groups.md#processes) permissions or ask your ProcessMaker Administrator for assistance.
{% endhint %}

### Delete a Non-Pool Element

Follow these steps to delete any non-Pool element from a Process model:

1. Select the non-Pool element to delete. Available options display to the right of the selected element.  

   ![](../../../.gitbook/assets/delete-element-process-modeler-processes.png)

2. Click the **Delete** icon![](../../../.gitbook/assets/remove-icon.png). The Process model element is deleted.

### Delete a Pool Element

{% hint style="warning" %}
When a Pool element is deleted, all elements within it are also deleted. If you want to keep the elements within a Pool element, you must add those elements outside of the Pool element prior to deleting the Pool element.

If you accidentally delete a Pool element with other elements you want to keep, then select the **Undo** button.
{% endhint %}

Follow these steps to delete a Pool element from a Process model:

1. ​Select the Pool name in the Pool element to delete, thereby selecting the Pool element. Available options display to the right of the selected element.
2. Click the **Delete** icon![](../../../.gitbook/assets/remove-icon.png). The Pool element is deleted. All non-Pool elements within the Pool are also deleted.

### Delete a Lane Element from a Pool Element

Follow these steps to delete a Lane element from a Pool element:

1. ​Select the Lane element in the Pool element to delete. Available options display to the right of the selected element.
2. Click the **Delete** icon![](../../../.gitbook/assets/remove-icon.png). The Pool element is deleted. All non-Pool elements that were within the deleted Lane element stay in their current positions within the Pool element.

## Related Topics

{% page-ref page="process-modeling-element-descriptions.md" %}

{% page-ref page="../../viewing-processes/view-the-list-of-processes/view-your-processes.md" %}

{% page-ref page="../../viewing-processes/view-the-list-of-processes/create-a-process.md" %}

