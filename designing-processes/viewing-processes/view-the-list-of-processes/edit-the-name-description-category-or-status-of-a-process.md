---
description: Edit the configuration for a Process.
---

# Edit Process Configuration

## Edit Configuration Information About a Process

{% hint style="info" %}
Your user account or group membership must have the following permissions to edit a Process's configuration:

* Processes: View Processes
* Processes: Edit Processes

See the [Process](../../../processmaker-administration/permission-descriptions-for-users-and-groups.md#processes) permissions or ask your ProcessMaker Administrator for assistance.
{% endhint %}

Follow these steps to edit the configuration information about a Process:

1. [View your active Processes.](./#view-your-processes) The **Processes** page displays.
2. Select the **Config** icon![](../../../.gitbook/assets/configure-process-icon-processes-page-processes.png)for your Process. The **Edit** page displays.  

   ![](../../../.gitbook/assets/edit-process-page-processes.png)

3. Edit the following information about the Process as necessary:
   * In the **Process title** field, edit the Process name. This is a required field.
   * In the **Description** field, edit the description of the Process. This is a required field.
   * From the **Category** drop-down, select to which category to assign the Process. This is a required field. See [Process Categories](../process-categories.md) for more information how this affects the Process.
   * From the **Start Request** drop-down, specify from which users or groups have permission to start Requests from this Process. If no users or groups are selected, no one can start a Request from this Process. Use the following guidelines:
     * **Users:** Select which user\(s\) from the **Users** section of the drop-down have permission to start Requests of this Process.
     * **Groups:** Select which group\(s\) from the Groups section of the drop-down have permission to start Requests of the Process.
   * From the **Cancel Request** drop-down, specify from which users or groups have permission to cancel Requests from this Process. If no users or groups are selected, no one can cancel a Request from this Process. Use the following guidelines:
     * **Users:** Select which user\(s\) from the **Users** section of the drop-down have permission to cancel Requests of this Process. Multiple users can be selected. Use **Shift** to select multiple consecutive users or use **Ctrl** to select multiple non-consecutive users.
     * **Groups:** Select which group\(s\) from the **Groups** section of the drop-down have permission to cancel Requests of the Process. Multiple groups can be selected. Use **Shift** to select multiple consecutive groups or use **Ctrl** to select multiple non-consecutive groups.
   * From the **Status** drop-down, select the status of the Process. This is a required field. Below is a description of each Process status:
     * **Active:** An active Process is one in which Requests can be started using that Process.
     * **Inactive:** An inactive Process is one in which Requests cannot be started using that Process. ~~However, active Requests using that inactive Process can be completed.~~
4. Click **Update**.

## Related Topics

{% page-ref page="../what-is-a-process.md" %}

{% page-ref page="view-your-processes.md" %}

{% page-ref page="../process-categories.md" %}

{% page-ref page="search-for-a-process.md" %}

{% page-ref page="remove-a-process.md" %}

{% page-ref page="restore-a-process.md" %}

{% page-ref page="create-a-process.md" %}

