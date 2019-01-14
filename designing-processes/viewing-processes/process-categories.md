---
description: Assign Categories to Processes for better organization.
---

# Process Categories

Use Categories to organize your organizational Processes. Categories allow different departments throughout your organization to assign their Processes to their own Categories.

Categories can be activated or deactivated. ~~What value does that provide?~~

A Process can be assigned to only one Category.

## View Process Categories

Follow these steps to view Process Categories:

1. [Log in](../../using-processmaker/log-in.md#log-in) to ProcessMaker.
2. Click the **Processes** option from the top menu. The **Processes** page displays.
3. Click the **Process Categories** icon![](../../.gitbook/assets/process-categories-icon-processes.png) in the left sidebar. The **Process Categories** page displays.

![&quot;Process Categories&quot; page](../../.gitbook/assets/process-categories-page-processes.png)

The **Process Categories** page displays the following information about Process Categories:

* **Category:** The **Category** column displays the name of the Process Category.
* **Status:** The **Status** column displays the status of the Process Category. See [Edit the Name or Status of a Process Category](process-categories.md#edit-the-name-or-status-of-a-process-category).
* **\#Processes:** The **\#Processes** column displays how many Processes in your organization have been assigned to that Process Category.

{% hint style="info" %}
### No Process Categories? <a id="no-processes"></a>

If no Process Categories exist, the following message displays: **No Data Available**.

### Display Information the Way You Want It <a id="display-information-the-way-you-want-it"></a>

​[Control how tabular information displays](https://processmaker.gitbook.io/processmaker-4-community/-LPblkrcFWowWJ6HZdhC/~/drafts/-LWD5skTaOptuIWIWk76/primary/using-processmaker/control-how-requests-display-in-a-tab), including how to sort columns or how many items display per page.
{% endhint %}

## Add a New Process Category

Follow these steps to add a new process category:

1. [View process categories.](process-categories.md#view-process-categories)
2. Click the **+Category** button. The **Create New Process Category** screen displays.  

   ![](../../.gitbook/assets/create-new-process-category-screen-processes.png)

3. Enter the name of the new process category in the **Category Name** field. The category name must be unique from all other process category names in your organization. This is a required field.
4. Click **Save**. The **Edit Process Category** page displays. Use this page to edit the process category's name or set its status. For more information, see [Edit the Name or Status of a Process Category](process-categories.md#edit-the-name-or-status-of-a-process-category).

## Edit the Name or Status of a Process Category

Follow these steps to edit the name or status of a process category:

1. [View process categories.](process-categories.md#view-process-categories)
2. Hover your cursor over the process category and then select the **Edit** icon![](../../.gitbook/assets/edit-icon.png). The **Edit Process Category** page displays.  

   ![](../../.gitbook/assets/edit-process-category-page-processes.png)

3. Edit the name of the process category in the **Category Name** field if necessary. The process category name must be unique from all other process category names in your organization.
4. Change the status of the process category from the **Status** drop-down if necessary. Below is a description of each process category status:
   * **Active:** A process category that is in Active status can have processes assigned to it.
   * **Inactive:** A process category that is Inactive deactivates all processes assigned to that process category. ~~A process category that is in Inactive status cannot have processes assigned to it. If a process category becomes inactive, processes assigned to that category are no longer assigned to that process category and are no longer assigned to any process category.~~
5. Click **Update**. Otherwise, click **Cancel** to cancel any changes.

## Remove a Process Category

{% hint style="warning" %}
Removing a process category cannot be undone. Furthermore, no processes can be assigned to the process category for it to be removed.
{% endhint %}

Follow these steps to remove a process category:

1. [View process categories.](process-categories.md#view-process-categories)
2. Hover your cursor over the process category and then select the **Remove** icon![](../../.gitbook/assets/remove-icon.png). A message displays to confirm removal of the process category.  

   ![](../../.gitbook/assets/remove-process-category-screen-processes.png)

3. Click **Confirm** to remove the process category. Otherwise, click **Cancel** to not remove the process category.

{% hint style="info" %}
If any processes are assigned to the process category when you try to remove it, the following message displays: **The item should not have associated processes**.

Remove association all processes from the process category, and then remove the process category.
{% endhint %}

## Related Topics

{% page-ref page="what-is-a-process.md" %}

{% page-ref page="../../using-processmaker/control-how-requests-display-in-a-tab.md" %}

