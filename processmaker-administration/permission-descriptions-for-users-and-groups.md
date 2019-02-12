---
description: >-
  Understand how each permission affects ProcessMaker access for ProcessMaker
  users and groups.
---

# Permission Descriptions for Users and Groups

## Overview

In ProcessMaker 4, a permission allows a ProcessMaker user member to view a type of information or perform an action in ProcessMaker. Below are some examples of ProcessMaker permissions:

* Start Requests
* View the list of Processes
* Edit Processes
* Edit ProcessMaker Screens
* Create Environment Variables
* View Task Assignments through the [ProcessMaker 4 API](https://develop.bpm4.qa.processmaker.net/api/documentation)

### Assign Permissions to Users and Groups

While permissions apply to ProcessMaker users, those permissions can be assigned from a user account or a ProcessMaker group:

* **User-level permissions:** Permissions can be assigned to a ProcessMaker user account. These permission assignments only apply to that user account.
* **Group-level permissions:** Permissions can be assigned to a ProcessMaker group. A group assigns permissions to all ProcessMaker user account members. ProcessMaker groups make it easy to manage permissions for multiple ProcessMaker user accounts with identical permission assignments.

### User and Group Permissions are Cumulative

User-level and group-level permission assignments are cumulative. This means that a ProcessMaker user account has all the group-level permission assignments from all groups memberships, but also has the flexibility of permission assignments that apply only to that ProcessMaker user account. For example, a ProcessMaker user account might be a member of a group whereby its members can view the list of all Processes. However, a ProcessMaker Administrator can assign the permission to edit Processes to only the one ProcessMaker user account.

### Best Practice to Assign Permissions

ProcessMaker recommends [creating ProcessMaker groups](assign-groups-to-users/create-a-group.md#create-a-processmaker-group) based on how you define ProcessMaker usage roles in your organization. Based on usage roles you define, assign permissions to ProcessMaker groups so that all group members have the same permission set. Below is an example how you might create groups to assign permissions:

* **ProcessMaker user:** Most ProcessMaker users start or participate in Requests and perform Tasks. Their permission assignments may be limited to [Requests](permission-descriptions-for-users-and-groups.md#requests). Note that if you want specific ProcessMaker users and/or groups to start and/or cancel Requests, that must be [configured for each Process](../designing-processes/viewing-processes/view-the-list-of-processes/edit-the-name-description-category-or-status-of-a-process.md#edit-configuration-information-about-a-process).
* **Process Owner:** Process Owners create Process models. Their permission assignments may be limited to [Requests](permission-descriptions-for-users-and-groups.md#requests), [Processes](permission-descriptions-for-users-and-groups.md#processes), Process [Categories](permission-descriptions-for-users-and-groups.md#categories), [Screens](permission-descriptions-for-users-and-groups.md#screens), and [Environment Variables](permission-descriptions-for-users-and-groups.md#environment-variables) categories.
* **ProcessMaker Developer:** ProcessMaker Developers create ProcessMaker Scripts. Their permission assignments may be limited to [Requests](permission-descriptions-for-users-and-groups.md#requests), [Scripts](permission-descriptions-for-users-and-groups.md#scripts), [Files \(API\)](permission-descriptions-for-users-and-groups.md#files-api), [Notifications \(API\)](permission-descriptions-for-users-and-groups.md#notifications-api), and [Task Assignments \(API\)](permission-descriptions-for-users-and-groups.md#task-assignments-api) categories.
* **ProcessMaker Administrator:** ProcessMaker Administrators administer the ProcessMaker environment and its users. Their permission assignments may be limited to [Requests](permission-descriptions-for-users-and-groups.md#requests), [Users](permission-descriptions-for-users-and-groups.md#users), [Groups](permission-descriptions-for-users-and-groups.md#groups), and [Comments](permission-descriptions-for-users-and-groups.md#comments) categories.

## Permission Descriptions

Permissions are organized into categories. Permission are described below by category and how each permission affects ProcessMaker functionality. These permissions function identically in ProcessMaker user accounts and groups.

Most permission categories have four permission types:

* View permission type: View the resource, but no change functionality.
* Create permission type: Create a resource. When 
* Edit: 
* Delete: 

### Requests

The **Requests** category contains the following permission:

* **View All Requests:** View the **All Requests** page and [Request information](../using-processmaker/requests/request-details.md) accessible from that page. See [View All Requests](../using-processmaker/requests/view-all-requests.md).

### Scripts

The **Scripts** category contains the following permissions:

* **View Scripts:** View the list of ProcessMaker Scripts that displays on the **Scripts** page. See [View All Scripts](../designing-processes/scripts/manage-scripts/view-all-scripts.md).
* **Create Scripts:** Create a ProcessMaker Script from the **Scripts** page. Selecting this permission also selects the **Edit Scripts** permission. See [Create a New Script](../designing-processes/scripts/create-a-new-script.md).
* **Edit Scripts:** Edit a ProcessMaker Script and/or its configuration from the **Scripts** page. See [Edit Script Configuration](../designing-processes/scripts/manage-scripts/edit-script-configuration.md) and [Edit a Script](../designing-processes/scripts/manage-scripts/edit-a-script.md).
* **Delete Scripts:** Delete a ProcessMaker Script from the **Scripts** page. See [Delete a Script](../designing-processes/scripts/manage-scripts/remove-a-script.md).

{% hint style="info" %}
Select the **View Scripts** permission to use any of the other permissions in this category.
{% endhint %}

### Categories

The **Categories** category contains the following permissions:

* **View Categories:** View the list of Process Categories that displays on the **Categories** page. See [View Process Categories](../designing-processes/viewing-processes/process-categories.md#view-process-categories).
* **Create Categories:** Create a Process Category from the **Categories** page. Selecting this permission also selects the **Edit Categories** permission. See [Add a New Process Category](../designing-processes/viewing-processes/process-categories.md#add-a-new-process-category).
* **Edit Categories:** Edit a Process Category from the **Categories** page. See [Edit a Process Category](../designing-processes/viewing-processes/process-categories.md#edit-a-process-category).
* **Delete Categories:** Delete a Process Category from the Categories page. See [Delete a Process Category](../designing-processes/viewing-processes/process-categories.md#delete-a-process-category).

{% hint style="info" %}
Select the **View Categories** permission to use any of the other permissions in this category.
{% endhint %}

### Screens

The **Screens** category contains the following permissions:



### Environment Variables

The **Environment Variables** category contains the following permissions:



### Users

The **Users** category contains the following permissions:



### Groups

The **Groups** category contains the following permissions:



### Processes

The **Processes** category contains the following permissions:



### Comments

The **Comments** category contains the following permissions:



### Files \(API\)

The **Files \(API\)** category contains the following permissions:



### Notifications \(API\)

The **Notifications \(API\)** category contains the following permissions:



### Task Assignments \(API\)

The **Task Assignments \(AI\)** category contains the following permissions:

## Related Topics

{% page-ref page="assign-groups-to-users/create-a-group.md" %}

