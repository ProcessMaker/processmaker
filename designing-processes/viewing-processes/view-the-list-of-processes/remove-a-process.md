---
description: Archive a Process that is no longer needed in your organization.
---

# Archive a Process

## Overview

An archived Process has the following attributes:

* Archived Processes are disabled and inactive until they are [restored](restore-a-process.md#restore-a-process).
* All archived Processes in the organization are accessible from the **Process Archive** page. See [View Archived Processes](remove-a-process.md#view-archived-processes).
* A Process with in-progress Requests can be archived. In-progress Requests will complete.

## View Archived Processes

{% hint style="info" %}
Your user account or group membership must have the "Processes: View Processes" permission to view the list of Processes.

Ask your ProcessMaker Administrator for assistance if necessary.
{% endhint %}

Follow these steps to view all archived Processes in your organization:

1. [Log in](../../../using-processmaker/log-in.md#log-in) to ProcessMaker.
2. Click the **Processes** option from the top menu. The **Processes** page displays.
3. Click the **Archived Processes** icon![](../../../.gitbook/assets/archived-processes-icon-processes.png)in the left sidebar. The **Process Archive** page displays all archived Processes in your organization.

{% hint style="info" %}
Click the **Archived Processes** icon![](../../../.gitbook/assets/archived-processes-icon-processes.png)in the left sidebar when you are in other Process-related pages to view the **Process Archive** page.
{% endhint %}

![&quot;Process Archive&quot; page contains all archived Processes in your organization](../../../.gitbook/assets/process-archive-page-processes.png)

The **Process Archive** page displays the following information in tabular format about archived Processes:

* **Name:** The **Name** column displays the Process name.
* **Category:** The **Category** column displays in which Process Category the Process is assigned. See [Process Categories](../process-categories.md).
* **Status:** The **Status** column displays that the Process is inactive and is represented by the![](../../../.gitbook/assets/inactive-status-icon-processes.png)icon.
* **Owner:** The **Owner** column displays the Process Owner who maintains the Process. Hover your cursor over a user's avatar to view that person's full name.
* **Modified:** The **Modified** column displays the date and time the Process was last modified. The time zone setting to display the time is according to the ProcessMaker 4 server unless your [user profile's](../../../using-processmaker/profile-settings.md#change-your-profile-settings) **Time zone** setting is specified.
* **Created:** The **Created** column displays the date and time the Process was created. The time zone setting to display the time is according to the ProcessMaker 4 server unless your [user profile's](../../../using-processmaker/profile-settings.md#change-your-profile-settings) **Time zone** setting is specified.

{% hint style="info" %}
### Need to Restore an Archived Process?

Click the **Restore** icon![](../../../.gitbook/assets/restore-process-icon-processes-page-processes.png). See [Restore a Process](restore-a-process.md#restore-an-archived-process).

### No Archived Processes?

If no archived Processes exist, the following message displays: **No Data Available**.
{% endhint %}

## Archive a Process

{% hint style="info" %}
Your user account or group membership must have the following permissions to archive a Process:

* Processes: View Processes
* Processes: Edit Processes

Ask your ProcessMaker Administrator for assistance.
{% endhint %}

{% hint style="warning" %}
When a Process is archived ~~describe what happens and in which conditions a process can be removed.~~
{% endhint %}

Follow these steps to archive a Process:

1. [View your Processes.](./#view-your-processes) The **Processes** page displays.
2. Select the **Archive** icon![](../../../.gitbook/assets/archive-process-icon-processes-page-processes.png)for your Process. The **Caution** screen displays to confirm archiving the Process.  

   ![](../../../.gitbook/assets/archive-process-confirmation-processes.png)

3. Click **Confirm** to archive the Process. The following message displays: **Process Archived**. The Process moves from the **Processes** page to the **Process Archive** page. See [View Archived Processes](remove-a-process.md#view-archived-processes). Also see [Restore an Archived Process](restore-a-process.md#restore-a-process).

## Related Topics

{% page-ref page="../what-is-a-process.md" %}

{% page-ref page="view-your-processes.md" %}

{% page-ref page="search-for-a-process.md" %}

{% page-ref page="edit-the-name-description-category-or-status-of-a-process.md" %}

{% page-ref page="restore-a-process.md" %}

{% page-ref page="../process-categories.md" %}

{% page-ref page="../create-a-process.md" %}

