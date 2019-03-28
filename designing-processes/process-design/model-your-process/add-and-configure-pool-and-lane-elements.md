---
description: Add and configure Pool and Lane elements in your Process model.
---

# Add and Configure Pool and Lane Elements

## Add a Pool Element

{% hint style="info" %}
### Don't Know What a Pool Element Is?

See [Process Modeling Element Descriptions](process-modeling-element-descriptions.md) for a description of the [Pool](process-modeling-element-descriptions.md#pool) element.

### Permissions Required to Do This Task

Your user account or group membership must have the following permissions to add a Pool element to the Process model:

* Processes: View Processes
* Processes: Edit Processes

See the [Process](../../../processmaker-administration/permission-descriptions-for-users-and-groups.md#processes) permissions or ask your ProcessMaker Administrator for assistance.
{% endhint %}

Follow these steps to add a Pool element to the Process model:

1. [View your Processes](https://processmaker.gitbook.io/processmaker-4-community/-LPblkrcFWowWJ6HZdhC/~/drafts/-LRhVZm0ddxDcGGdN5ZN/primary/designing-processes/viewing-processes/view-the-list-of-processes/view-your-processes#view-all-processes). The **Processes** page displays.
2. [Create a new Process](../../viewing-processes/view-the-list-of-processes/create-a-process.md) or click the **Open Modeler** icon![](../../../.gitbook/assets/open-modeler-edit-icon-processes-page-processes.png)to edit the selected Process model. Process Modeler displays.
3. Locate the **Pool** element in the **BPMN** panel.

   ![](../../../.gitbook/assets/pool-bpmn-side-bar-process-modeler-processes.png)

4. Drag the element to where in the Process model canvas that you want to place it. If non-Pool/[Lane](process-modeling-element-descriptions.md#lane) elements are in your Process model when you place the Pool element, those elements are automatically placed into the Pool element. A Pool element cannot be placed into another Pool element, though the most recently placed Pool element can overlap another such that only the recent Pool element and its contents is visible.

![Pool element](../../../.gitbook/assets/pool-process-modeler-processes.png)

After the element is placed into the Process model, you may [resize it](add-and-configure-pool-and-lane-elements.md#resize-a-pool-element) or move it by dragging it to the new location. Any elements within the Pool element move as well.

{% hint style="warning" %}
### Elements Placed Into a Pool Element Cannot Be Moved Out of It

If a non-Pool element is placed into a Pool element, that element cannot be moved outside of the Pool element. If you attempt to do so, Process Modeler places that element inside the Pool element closest to where you attempted to move it.

### Deleting a Pool Element Also Deletes All Elements Within It

When a Pool element is [deleted](remove-process-model-elements.md#delete-a-pool-element), all elements within it are also deleted. If you want to keep the elements within a Pool element, you must add those elements outside of the Pool element prior to deleting the Pool element.

If you accidentally delete a Pool element with other elements you want to keep, then click the **Undo** button.
{% endhint %}

## Resize a Pool Element

{% hint style="info" %}
### Don't Know What a Pool Element Is?

See [Process Modeling Element Descriptions](process-modeling-element-descriptions.md) for a description of the [Pool](process-modeling-element-descriptions.md#pool) element.

### Permissions Required to Do This Task

Your user account or group membership must have the following permissions to resize a Pool element to the Process model:

* Processes: View Processes
* Processes: Edit Processes

See the [Process](../../../processmaker-administration/permission-descriptions-for-users-and-groups.md#processes) permissions or ask your ProcessMaker Administrator for assistance.
{% endhint %}

Follow these steps to resize a Pool element:

1. [Add a Pool element to your Process model](add-and-configure-pool-and-lane-elements.md#add-a-pool-element).
2. Select the Pool element from the Process model to resize. Anchors display on each corner of the Pool element.  

   ![](../../../.gitbook/assets/pool-element-lane-message-anchor-icons-process-modeler-processes.png)

3. Click one of the anchors on the Pool element![](../../../.gitbook/assets/pool-element-anchor-process-modeler-processes.png), hold your cursor, and then adjust the size of the Pool element on the Process model canvas. If the Pool element contains elements within it prior to resizing, the Pool element only resizes to contain all elements within it. Lane elements automatically adjust to the width of the Pool element, though [Lane elements can be resized](add-and-configure-pool-and-lane-elements.md#resize-a-lane-element) separately.

## Configure a Pool Element

{% hint style="info" %}
Your user account or group membership must have the following permissions to configure a Pool element:

* Processes: View Processes
* Processes: Edit Processes

See the [Process](../../../processmaker-administration/permission-descriptions-for-users-and-groups.md#processes) permissions or ask your ProcessMaker Administrator for assistance.
{% endhint %}

### Edit the Identifier Value

Process Modeler automatically assigns a unique value to each Process element added to a Process model. However, an element's identifier value can be changed if it is unique.

{% hint style="warning" %}
All identifier values for all elements in the Process model must be unique.
{% endhint %}

Follow these steps to edit the identifier value for a Pool element:

1. Select the Pool element from the Process model in which to edit its identifier value.
2. Expand the **Configuration** setting section if it is not presently expanded. The **Identifier** field displays. This is a required field.  

   ![](../../../.gitbook/assets/pool-configuration-identifier-name-process-modeler-processes.png)

3. In the **Identifier** field, edit the Pool element's identifier to a unique value from all elements in the Process model and then press **Enter**. The element's identifier value is changed.

### Edit the Element Name

An element name is a human-readable reference for a Process element. Process Modeler automatically assigns the name of a Process element with its element type. However, an element's name can be changed.

Follow these steps to edit the name for a Pool element:

1. Select the Pool element from the Process model in which to edit its name.
2. Expand the **Configuration** setting section if it is not presently expanded. The **Name** field displays.  

   ![](../../../.gitbook/assets/pool-configuration-identifier-name-process-modeler-processes.png)

3. In the **Name** field, edit the selected element's name and then press **Enter**. The element's name is changed.

## Add a Lane Element to a Pool Element

{% hint style="info" %}
### Don't Know What a Lane Element Is?

See [Process Modeling Element Descriptions](process-modeling-element-descriptions.md) for a description of the [Lane](process-modeling-element-descriptions.md#lane) element.

### Permissions Required to Do This Task

Your user account or group membership must have the following permissions to add a Lane element to a Pool element:

* Processes: View Processes
* Processes: Edit Processes

See the [Process](../../../processmaker-administration/permission-descriptions-for-users-and-groups.md#processes) permissions or ask your ProcessMaker Administrator for assistance.
{% endhint %}

Follow these steps to add a Lane element to a Pool element:

1. [View your Processes](https://processmaker.gitbook.io/processmaker-4-community/-LPblkrcFWowWJ6HZdhC/~/drafts/-LRhVZm0ddxDcGGdN5ZN/primary/designing-processes/viewing-processes/view-the-list-of-processes/view-your-processes#view-all-processes). The **Processes** page displays.
2. Click the **Open Modeler** icon![](../../../.gitbook/assets/open-modeler-edit-icon-processes-page-processes.png)for edit the selected Process model. Process Modeler displays.
3. Click the Pool element from the Process model into which to add a Lane element. Icons to add a Lane to a Pool element display.  

   ![](../../../.gitbook/assets/pool-element-lane-message-anchor-icons-process-modeler-processes.png)

4. Do one of the following:
   * **Add a Lane element above existing Lane elements:** Click the![](../../../.gitbook/assets/pool-add-lane-above-process-modeler-processes.png)icon to add a Lane element above all existing Lane elements. If only the Pool element exists, two Lane elements display.
   * **Add a Lane element below existing Lane elements:** Click the![](../../../.gitbook/assets/pool-add-lane-below-process-modeler-processes.png)icon to add a Lane element below all existing Lane elements. If only the Pool element exists, two Lane elements display.

## Resize a Lane Element

{% hint style="info" %}
### Don't Know What a Lane Element Is?

See [Process Modeling Element Descriptions](process-modeling-element-descriptions.md) for a description of the [Lane](process-modeling-element-descriptions.md#lane) element.

### Permissions Required to Do This Task

Your user account or group membership must have the following permissions to resize a Lane element to the Process model:

* Processes: View Processes
* Processes: Edit Processes

See the [Process](../../../processmaker-administration/permission-descriptions-for-users-and-groups.md#processes) permissions or ask your ProcessMaker Administrator for assistance.
{% endhint %}

Follow these steps to resize a Lane element:

1. [Add a Lane element to a Pool element](add-and-configure-pool-and-lane-elements.md#add-a-lane-element-to-a-pool-element).
2. Select the Lane element from the Pool element to resize. Anchors display on each corner of the Lane element.  

   ![](../../../.gitbook/assets/lane-element-resize-anchor-process-modeler-processes.png)

3. Click one of the anchors on the Lane element![](../../../.gitbook/assets/pool-element-anchor-process-modeler-processes.png), hold your cursor, and then adjust the size of the Lane element in the Pool element. If the Lane element contains elements within it prior to resizing, the Lane element only resizes to contain all elements within it. The Pool element automatically adjusts to the width of the resized Lane element so far as all elements contained within the Pool element remain contained in it. [Pool elements can be resized](add-and-configure-pool-and-lane-elements.md#resize-a-pool-element) separately.

## Configure a Lane Element

{% hint style="info" %}
Your user account or group membership must have the following permissions to configure a Lane element:

* Processes: View Processes
* Processes: Edit Processes

See the [Process](../../../processmaker-administration/permission-descriptions-for-users-and-groups.md#processes) permissions or ask your ProcessMaker Administrator for assistance.
{% endhint %}

### Edit the Identifier Value

Process Modeler automatically assigns a unique value to each Process element added to a Process model. However, an element's identifier value can be changed if it is unique.

{% hint style="warning" %}
All identifier values for all elements in the Process model must be unique.
{% endhint %}

Follow these steps to edit the identifier value for a Lane element:

1. Select the Lane element from the Process model in which to edit its identifier value.
2. Expand the **Configuration** setting section if it is not presently expanded. The **Identifier** field displays. This is a required field.  

   ![](../../../.gitbook/assets/lane-configuration-identifier-name-process-modeler-processes.png)

3. In the **Identifier** field, edit the Lane element's identifier to a unique value from all elements in the Process model and then press **Enter**. The element's identifier value is changed.

### Edit the Element Name

An element name is a human-readable reference for a Process element. Process Modeler automatically assigns the name of a Process element with its element type. However, an element's name can be changed.

Follow these steps to edit the name for a Lane element:

1. Select the Lane element from the Process model in which to edit its name.
2. Expand the **Configuration** setting section if it is not presently expanded. The **Name** field displays.  

   ![](../../../.gitbook/assets/lane-configuration-identifier-name-process-modeler-processes.png)

3. In the **Name** field, edit the selected element's name and then press **Enter**. The element's name is changed.

## Related Topics

{% page-ref page="process-modeling-element-descriptions.md" %}

{% page-ref page="../../viewing-processes/view-the-list-of-processes/view-your-processes.md" %}

{% page-ref page="../../viewing-processes/view-the-list-of-processes/create-a-process.md" %}

{% page-ref page="remove-process-model-elements.md" %}

