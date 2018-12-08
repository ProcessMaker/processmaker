---
description: Add and configure Task elements in your process model.
---

# Add and Configure Task Elements

## Add a Task Element

{% hint style="info" %}
For a description of the [Task](../process-modeling-element-descriptions.md#user-task) element, see [Process Modeling Element Descriptions](../process-modeling-element-descriptions.md).
{% endhint %}

Follow these steps to add a Task element to the process model:

1. [View your processes](https://processmaker.gitbook.io/processmaker-4-community/-LPblkrcFWowWJ6HZdhC/~/drafts/-LRhVZm0ddxDcGGdN5ZN/primary/designing-processes/viewing-processes/view-the-list-of-processes/view-your-processes#view-all-processes). The **Processes** page displays.
2. [Create a new process](../../viewing-processes/create-a-process.md) or [select the process name of an existing process to open it](../../viewing-processes/view-the-list-of-processes/view-your-processes.md#view-all-processes). Process Modeler displays.
3. Locate the **Task** element ![](../../../.gitbook/assets/task-bpmn-side-bar-process-modeler-processes.png) in the **BPMN** left side bar. Drag and drop the element to where in the process model you want to place it. The event has been added to the process model.

![Task element](../../../.gitbook/assets/task-process-modeler-processes.png)

## Configure a Task Element

### Edit the Identifier Value

Process Modeler assigns a value to a process model or process element to identify that element. Process Modeler automatically assigns a unique value to each process element added to a process model. However, an element's identifier value can be changed as long as it is unique.

{% hint style="info" %}
All identifier values for all elements in the process model must be unique.
{% endhint %}

Follow these steps to edit the identifier value for a Task element:

1. Place a [Task](add-and-configure-task-elements.md#add-a-task-element) element into your process model.
2. Select the Task element in which to edit its identifier value. The current Task identifier value displays in the **Identifier** field in the right side bar.
3. In the **Identifier** field, edit the selected Task element's identifier value and then press **Enter**. The identifier value is changed.

### Edit the Element Name

An element name is a human-readable reference for a process element. Process Modeler automatically assigns the name of a process element with its element type. However, an element's name can be changed.

Follow these steps to edit the name for a Task element:

1. Place a [Task](add-and-configure-task-elements.md#add-a-task-element) element into your process model.
2. Select the Task element in which to edit its name. The current name displays in the **Name** field in the right side bar.
3. In the **Name** field, edit the selected Task element's name and then press **Enter**. The element's name is changed.

### Select the ProcessMaker Screen for a Task Element

{% hint style="info" %}
For information about ProcessMaker Screens, see [What is a Screen?](../../design-forms/what-is-a-form.md).
{% endhint %}

Since Task elements are designed to collect or display [Request](../../../using-processmaker/requests/what-is-a-request.md) information, specify which ProcessMaker Screen a selected Task element uses. A ProcessMaker Screen must already exist before it can be selected for use in a Task element.

{% hint style="warning" %}
Ensure to select a ProcessMaker Screen for each Task element in your process model. If a ProcessMaker Screen is not specified and Requests are started for that process, users who are assigned Tasks with no ProcessMaker Screens have no way of interacting with the Request.
{% endhint %}

Follow these steps to select a ProcessMaker Screen for a Task element:

1. Place a [Task](add-and-configure-task-elements.md#add-a-task-element) element into your process model.
2. Select the Task element in which to specify its ProcessMaker Screen. Options for the Script Task element display in the right side bar.
3. In the **Screen For Input** field, select which ProcessMaker Screen that Task element uses. The ProcessMaker Screen is selected.

{% hint style="info" %}
Click the **Refresh** link below the **Screen For Input** field to refresh the options in the drop-down.
{% endhint %}

{% hint style="warning" %}
If no ProcessMaker Screens exist, the drop-down contains no options. Ensure to select a ProcessMaker Screen for every Task element in the process model before making the process active.
{% endhint %}

### Specify When the Task is Due

Specify how much time a task in a Task element is due from when that task is assigned to a Request participant. The default period of time for a task to be due is 72 hours \(three days\).

The task due date displays for each [pending assigned task](../../../using-processmaker/requests/view-completed-requests.md#view-completed-requests-in-which-you-are-a-participant). After the specified time has expired for a task, an overdue indicator displays for that task to the assigned task recipient.

{% hint style="info" %}
Specify due time for a Task element in total number of hours. This includes hours not normally associated with business hours, including overnight hours, weekends, and holidays.
{% endhint %}

Follow these steps to specify when a Task element is due:

1. Place a [Task](add-and-configure-task-elements.md#add-a-task-element) element into your process model.
2. Select the Task element in which to specify how many hours the task is due.
3. Specify the total number of hours the task is due in one of the following ways:
   * Enter the number in the **Due In** field and then press **Enter**. The number of hours is entered.
   * Hover your cursor over the **Due In** field, and then use the spin arrows to increase or decrease the total number of hours by one.

### Select to Whom to Assign the Task

Select to whom to assign the Task element in a process model:

* **Requestor:** Assign the Task element to the Request initiator.
* **User:** Assign the Task element to a selected person.

Follow these steps to select to whom to assign the Task element:

1. Place a [Task](add-and-configure-task-elements.md#add-a-task-element) element into your process model.
2. Select the Task element in which to select to whom to assign the Task element. Options for the Script Task element display in the right side bar.
3. From the **Task Assignment** field, select one of the following options:
   * **To requestor:** Select **To requestor** to assign the Task element to the Request initiator.
   * **To user:** Select **To user** to assign the Task element to a specified person. When this option is selected, the **Assigned User** field displays below the **Task Assignment** field. From the **Assigned User** field, select the person's full name as the Task element's assignee.

## Related Topics

{% page-ref page="../process-modeling-element-descriptions.md" %}

{% page-ref page="../../viewing-processes/view-the-list-of-processes/view-your-processes.md" %}

{% page-ref page="../../viewing-processes/create-a-process.md" %}

{% page-ref page="../../design-forms/what-is-a-form.md" %}

{% page-ref page="../../../using-processmaker/requests/what-is-a-request.md" %}

{% page-ref page="../../../using-processmaker/task-management/view-tasks-you-need-to-do.md" %}

