---
description: Add and configure Task elements in your Process model.
---

# Add and Configure Task Elements

## Add a Task Element

{% hint style="info" %}
### Don't Know What a Task Element Is?

See [Process Modeling Element Descriptions](process-modeling-element-descriptions.md) for a description of the [Task](process-modeling-element-descriptions.md#user-task) element.

### Permissions Required to Do This Task

Your user account or group membership must have the following permissions to add a Task element to the Process model:

* Processes: View Processes
* Processes: Edit Processes

See the [Process](../../../processmaker-administration/permission-descriptions-for-users-and-groups.md#processes) permissions or ask your ProcessMaker Administrator for assistance.
{% endhint %}

Follow these steps to add a Task element to the Process model:

1. [View your Processes](https://processmaker.gitbook.io/processmaker-4-community/-LPblkrcFWowWJ6HZdhC/~/drafts/-LRhVZm0ddxDcGGdN5ZN/primary/designing-processes/viewing-processes/view-the-list-of-processes/view-your-processes#view-all-processes). The **Processes** page displays.
2. [Create a new Process](../../viewing-processes/view-the-list-of-processes/create-a-process.md) or click the **Open Modeler** icon![](../../../.gitbook/assets/open-modeler-edit-icon-processes-page-processes.png)to edit the selected Process model. Process Modeler displays.
3. Locate the **Task** element ![](../../../.gitbook/assets/task-bpmn-side-bar-process-modeler-processes.png) in the **BPMN** panel. Drag and drop the element to where in the Process model you want to place it. If a Pool element is in your Process model, the Task element cannot be placed outside of the Pool element. The element has been added to the Process model.

![Task element](../../../.gitbook/assets/task-element-process-modeler-processes.png)

After the element is placed into the Process model, you may move it by selecting it, hold the cursor, and then dragging it to the new location.

{% hint style="warning" %}
If the element is placed inside of a Pool element, the Task element cannot be moved outside of the Pool element. If you attempt to do so, Process Modeler places the Task element inside the Pool element closest to where you attempted to move it.
{% endhint %}

## Configure a Task Element

{% hint style="info" %}
Your user account or group membership must have the following permissions to configure a Task element:

* Processes: View Processes
* Processes: Edit Processes

See the [Process](../../../processmaker-administration/permission-descriptions-for-users-and-groups.md#processes) permissions or ask your ProcessMaker Administrator for assistance.
{% endhint %}

### Edit the Identifier Value

Process Modeler automatically assigns a unique value to each Process element added to a Process model. However, an element's identifier value can be changed as long as it is unique.

{% hint style="warning" %}
All identifier values for all elements in the Process model must be unique.
{% endhint %}

Follow these steps to edit the identifier value for a Task element:

1. Select the Task element in which to edit its identifier value. The current Task identifier value displays in the **Identifier** field in the right side bar.
2. In the **Identifier** field, edit the selected Task element's identifier value and then press **Enter**. The identifier value is changed.

### Edit the Element Name

An element name is a human-readable reference for a Process element. Process Modeler automatically assigns the name of a Process element with its element type. However, an element's name can be changed.

Follow these steps to edit the name for a Task element:

1. Select the Task element in which to edit its name. The current name displays in the **Name** field in the right side bar.
2. In the **Name** field, edit the selected Task element's name and then press **Enter**. The element's name is changed.

### Select the ProcessMaker Screen for a Task Element

{% hint style="info" %}
See [What is a Screen?](../../design-forms/what-is-a-form.md) for more information.
{% endhint %}

Since Task elements are designed to collect or display [Request](../../../using-processmaker/requests/what-is-a-request.md) information, specify which ProcessMaker Screen a selected Task element uses. A ProcessMaker Screen must already exist before it can be selected for use in a Task element.

{% hint style="warning" %}
Ensure to select a ProcessMaker Screen for each Task element in your Process model. If a ProcessMaker Screen is not specified and Requests are started for that Process, users who are assigned Tasks with no ProcessMaker Screens have no way of interacting with the Request.
{% endhint %}

Follow these steps to select a ProcessMaker Screen for a Task element:

1. Select the Task element in which to specify its ProcessMaker Screen. Options for the Task element display in the right side bar.
2. In the **Screen For Input** field, select which ProcessMaker Screen that Task element uses. The ProcessMaker Screen is selected.

{% hint style="info" %}
Click the **Refresh** link below the **Screen For Input** field to refresh the options in the drop-down.
{% endhint %}

{% hint style="warning" %}
If no ProcessMaker Screens exist, the drop-down contains no options. Ensure to select a ProcessMaker Screen for every Task element in the Process model before making the Process active.
{% endhint %}

### Specify When the Task is Due

Specify how much time a task in a Task element is due from when that task is assigned to a Request participant. The default period of time for a task to be due is 72 hours \(three days\).

The task due date displays for each [pending assigned task](../../../using-processmaker/requests/view-completed-requests.md#view-completed-requests-in-which-you-are-a-participant). After the specified time has expired for a task, an overdue indicator displays for that task to the assigned task recipient.

{% hint style="info" %}
Specify due time for a Task element in total number of hours. This includes hours not normally associated with business hours, including overnight hours, weekends, and holidays.
{% endhint %}

Follow these steps to specify when a Task element is due:

1. Select the Task element in which to specify how many hours the task is due.
2. Specify the total number of hours the task is due in one of the following ways:
   * Enter the number in the **Due In** field and then press **Enter**. The number of hours is entered.
   * Hover your cursor over the **Due In** field, and then use the spin arrows to increase or decrease the total number of hours by one.

### Select to Whom to Assign the Task

Select to whom to assign the Task element in a Process model:

* **Requestor:** Assign the Task element to the Request initiator.
* **User:** Assign the Task element to a selected person.
* **Group:** Assign the Task element to a selected group. When a Task element is assigned to a group, round robin assignment rule determines the assignee without manually assigning the Task.

Follow these steps to select to whom to assign the Task element:

1. Select the Task element in which to select the task assignee. Options for the Task element display in the right side bar.
2. From the **Task Assignment** field, select one of the following options:
   * **To requestor:** Select **To requestor** to assign the Task element to the Request initiator.
   * **To user:** Select **To user** to assign the Task element to a specified person. When this option is selected, the **Assigned User** field displays below the **Task Assignment** field. From the **Assigned User** field, select the person's full name as the Task element's assignee.
   * **To group:** Select **To group** to assign the Task element to a specified group. When this option is selected, the **Assigned Group** field displays below the **Task Assignment** field. From the **Assigned Group** field, select the group as the Task element's assignee.

## Related Topics

{% page-ref page="process-modeling-element-descriptions.md" %}

{% page-ref page="../../viewing-processes/view-the-list-of-processes/view-your-processes.md" %}

{% page-ref page="../../viewing-processes/view-the-list-of-processes/create-a-process.md" %}

{% page-ref page="../../design-forms/what-is-a-form.md" %}

{% page-ref page="../../../using-processmaker/requests/what-is-a-request.md" %}

{% page-ref page="../../../using-processmaker/task-management/view-tasks-you-need-to-do.md" %}

