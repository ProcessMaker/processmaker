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
* View Task Assignments through the [ProcessMaker 4 REST API](https://develop-demo.bpm4.qa.processmaker.net/api/documentation)

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

### Requests

The **Requests** category contains the following permission:

* **View All Requests:** View the **All Requests** page and [Request information](../using-processmaker/requests/request-details.md) accessible from that page. See [View All Requests](../using-processmaker/requests/view-all-requests.md).

### Scripts

The **Scripts** category contains the following permissions:

* **View Scripts:** View the table of ProcessMaker Scripts on the **Scripts** page. See [View All Scripts](../designing-processes/scripts/manage-scripts/view-all-scripts.md).
* **Create Scripts:** Create a ProcessMaker Script from the **Scripts** page. Selecting this permission also selects the **Edit Scripts** permission. See [Create a New Script](../designing-processes/scripts/create-a-new-script.md).
* **Edit Scripts:** Edit a ProcessMaker Script and/or its configuration from the **Scripts** page. See [Edit a Script](../designing-processes/scripts/manage-scripts/edit-a-script.md) and [Edit Script Configuration](../designing-processes/scripts/manage-scripts/edit-script-configuration.md).
* **Delete Scripts:** Delete a ProcessMaker Script from the **Scripts** page. See [Delete a Script](../designing-processes/scripts/manage-scripts/remove-a-script.md).

{% hint style="info" %}
Select the **View Scripts** permission to use any of the other permissions in this category.
{% endhint %}

### Categories

The **Categories** category contains the following permissions:

* **View Categories:** View the table of Process Categories on the **Categories** page. See [View Process Categories](../designing-processes/viewing-processes/process-categories.md#view-process-categories).
* **Create Categories:** Create a Process Category from the **Categories** page. Selecting this permission also selects the **Edit Categories** permission. See [Add a New Process Category](../designing-processes/viewing-processes/process-categories.md#add-a-new-process-category).
* **Edit Categories:** Edit a Process Category from the **Categories** page. See [Edit a Process Category](../designing-processes/viewing-processes/process-categories.md#edit-a-process-category).
* **Delete Categories:** Delete a Process Category from the Categories page. See [Delete a Process Category](../designing-processes/viewing-processes/process-categories.md#delete-a-process-category).

{% hint style="info" %}
Select the **View Categories** permission to use any of the other permissions in this category.
{% endhint %}

### Screens

The **Screens** category contains the following permissions:

* **View Screens:** View the table of ProcessMaker Screens on the **Screens** page. See [View All Screens](../designing-processes/design-forms/manage-forms/view-all-forms.md).
* **Create Screens:** Create a ProcessMaker Screen from the **Screens** page. Selecting this permission also selects the **Edit Screens** permission. See [Create a New Screen](../designing-processes/design-forms/create-a-new-form.md).
* **Edit Screens:** Edit a ProcessMaker Screen and/or its configuration from the **Screens** page. See [Edit a Screen](../designing-processes/design-forms/screens-builder/control-descriptions/) and [Edit Screen Configuration](../designing-processes/design-forms/manage-forms/edit-a-screen.md).
* **Delete Screens:** Delete a ProcessMaker Screen from the **Screens** page. See [Delete a Screen](../designing-processes/design-forms/manage-forms/remove-a-screen.md).

{% hint style="info" %}
Select the **View Screens** permission to use any of the other permissions in this category.
{% endhint %}

### Environment Variables

The **Environment Variables** category contains the following permissions:

* **View Environment Variables:** View the table of Environment Variables on the **Environment Variables** page. See [View All Environment Variables](../designing-processes/environment-variable-management/manage-your-environment-variables/view-all-environment-variables.md).
* **Create Environment Variables:** Create an Environment Variable from the **Environment Variables** page. Selecting this permission also selects the **Edit Environment Variables** permission. See [Create a New Environment Variable](../designing-processes/environment-variable-management/create-a-new-environment-variable.md).
* **Edit Environment Variables:** Edit an Environment Variable from the **Environment Variables** page. See [Edit an Environmental Variable](../designing-processes/environment-variable-management/manage-your-environment-variables/edit-an-environmental-variable.md).
* **Delete Environment Variables:** Delete an Environment Variable from the **Environment Variables** page. See [Delete an Environment Variable](../designing-processes/environment-variable-management/manage-your-environment-variables/remove-an-environment-variable.md).

{% hint style="info" %}
Select the **View Environment Variables** permission to use any of the other permissions in this category.
{% endhint %}

### Users

The **Users** category contains the following permissions:

* **View Users:** View the table of ProcessMaker user accounts on the **Users** page. See [View All Users Accounts](add-users/manage-user-accounts/view-all-users.md).
* **Create Users:** Create a ProcessMaker user account from the **Users** page. Selecting this permission also selects the **Edit Users** permission. See [Create a New User Account](add-users/create-a-user-account.md).
* **Edit Users:** Edit a ProcessMaker user account from the **Users** page. See [Edit a User Account](add-users/manage-user-accounts/edit-a-user-account.md).
* **Delete Users:** Delete a ProcessMaker user account from the **Users** page. See [Delete a User Account](add-users/manage-user-accounts/remove-a-user-account.md).

{% hint style="info" %}
Select the **View Users** permission to use any of the other permissions in this category.
{% endhint %}

### Groups

The **Groups** category contains the following permissions:

* **View Groups:** View the table of ProcessMaker groups on the **Groups** page. See [View All Groups](assign-groups-to-users/manage-groups/view-all-groups.md).
* **Create Groups:** View a ProcessMaker group from the **Groups** page. Selecting this permission also selects the **Edit Groups** permission. See [Create a New Group](assign-groups-to-users/create-a-group.md).
* **Edit Groups:** Edit a ProcessMaker group from the **Groups** page. See [Edit a Group](assign-groups-to-users/manage-groups/edit-a-group.md).
* **Delete Groups:** Delete a ProcessMaker group from the **Groups** page. See [Delete a Group](assign-groups-to-users/manage-groups/remove-a-group.md).

{% hint style="info" %}
Select the **View Groups** permission to use any of the other permissions in this category.
{% endhint %}

### Processes

The **Processes** category contains the following permissions:

* **View Processes:** View the table of Processes on the **Processes** page. See [View All Processes](../designing-processes/viewing-processes/view-the-list-of-processes/view-your-processes.md).
* **Create Processes:** Create a Process from the **Processes** page. Selecting this permission also selects the **Edit Processes** permission. See [Create a New Process](../designing-processes/viewing-processes/create-a-process.md).
* **Edit Processes:** Edit a Process model and/or its configuration from the **Processes** page. See [Edit a Process Model](../designing-processes/viewing-processes/view-the-list-of-processes/view-your-processes.md#edit-the-process-model) and [Edit Process Configuration](../designing-processes/viewing-processes/view-the-list-of-processes/edit-the-name-description-category-or-status-of-a-process.md).
* **Archive Processes:** Archive a Process from the **Processes** page. See [Archive a Process](../designing-processes/viewing-processes/view-the-list-of-processes/remove-a-process.md).

{% hint style="info" %}
Select the **View Processes** permission to use any of the other permissions in this category.
{% endhint %}

### Comments

The **Comments** category contains the following permissions:

* **View Comments:** View comments on a Request information page. See ~~LINK~~.
* **Create Comments:** Create a comment from a Request information page. Selecting this permission also selects the **Edit Comments** permission. See ~~LINK~~.
* **Edit Comments:** Edit a comment from a Request information page. See ~~LINK~~.
* **Delete Comments:** Delete a comment from a Request information page. See ~~LINK~~.

{% hint style="info" %}
Select the **View Comments** permission to use any of the other permissions in this category.
{% endhint %}

### Files \(API\)

The **Files \(API\)** category contains the following permissions:

* **View Files:** Returns the list of files associated to an API request. See "Files &gt; Get" endpoint in the [ProcessMaker 4 REST API](https://develop-demo.bpm4.qa.processmaker.net/api/documentation).
* **Create Files:** Saves a new file specified in an API request. Selecting this permission also selects the **Edit Files** permission. See "Files &gt; Post" endpoint in the [ProcessMaker 4 REST API](https://develop-demo.bpm4.qa.processmaker.net/api/documentation).
* **Edit Files:** Update a file specified in an API request. See "Files &gt; Update" endpoint in the [ProcessMaker 4 REST API](https://develop-demo.bpm4.qa.processmaker.net/api/documentation).
* **Delete Files:** Deletes a specified file in an API request. See "Files &gt; Delete" endpoint in the [ProcessMaker 4 REST API](https://develop-demo.bpm4.qa.processmaker.net/api/documentation).

### Notifications \(API\)

The **Notifications \(API\)** category contains the following permissions:

* **View Notifications:**  Returns all notifications to which the user has access. See "Notifications &gt; Get" endpoint in the [ProcessMaker 4 REST API](https://develop-demo.bpm4.qa.processmaker.net/api/documentation).
* **Create Notifications:**  Save a new notification through an API request. Selecting this permission also selects the **Edit Notifications** permission. See "Notifications &gt; Post" endpoint in the [ProcessMaker 4 REST API](https://develop-demo.bpm4.qa.processmaker.net/api/documentation).
* **Edit Notifications:** Updates a notification through an API request. See "Notifications &gt; Update" endpoint in the [ProcessMaker 4 REST API](https://develop-demo.bpm4.qa.processmaker.net/api/documentation).
* **Delete Notifications:** Deletes a specified notification through an API request. See "Notifications &gt; Delete" endpoint in the [ProcessMaker 4 REST API](https://develop-demo.bpm4.qa.processmaker.net/api/documentation).

### Task Assignments \(API\)

The **Task Assignments \(API\)** category contains the following permissions:

* **View Task Assignments:** Returns all assignments assigned to the user. See ~~WHAT?~~ endpoint in the [ProcessMaker 4 REST API](https://develop-demo.bpm4.qa.processmaker.net/api/documentation).
* **Create Task Assignments:** Saves a new task assignment to a specified user in an API request. Selecting this permission also selects the **Edit Task Assignments** permission. See "Task Assignments &gt; Post" endpoint in the [ProcessMaker 4 REST API](https://develop-demo.bpm4.qa.processmaker.net/api/documentation).
* **Edit Task Assignments:** Updates a task assignment through an API request. See "Task Assignments &gt; Update" endpoint in the [ProcessMaker 4 REST API](https://develop-demo.bpm4.qa.processmaker.net/api/documentation).
* **Delete Task Assignments:** Deletes a specified task assignment through an API request. See ~~WHAT?~~ endpoint in the [ProcessMaker 4 REST API](https://develop-demo.bpm4.qa.processmaker.net/api/documentation).

## Related Topics

{% page-ref page="../using-processmaker/requests/what-is-a-request.md" %}

{% page-ref page="../using-processmaker/requests/view-all-requests.md" %}

{% page-ref page="../using-processmaker/requests/request-details.md" %}

{% page-ref page="../designing-processes/scripts/manage-scripts/view-all-scripts.md" %}

{% page-ref page="../designing-processes/scripts/create-a-new-script.md" %}

{% page-ref page="../designing-processes/scripts/manage-scripts/edit-script-configuration.md" %}

{% page-ref page="../designing-processes/scripts/manage-scripts/edit-a-script.md" %}

{% page-ref page="../designing-processes/scripts/manage-scripts/remove-a-script.md" %}

{% page-ref page="../designing-processes/viewing-processes/process-categories.md" %}

{% page-ref page="../designing-processes/design-forms/manage-forms/view-all-forms.md" %}

{% page-ref page="../designing-processes/design-forms/create-a-new-form.md" %}

{% page-ref page="../designing-processes/design-forms/manage-forms/edit-a-screen.md" %}

{% page-ref page="../designing-processes/design-forms/screens-builder/control-descriptions/" %}

{% page-ref page="../designing-processes/design-forms/manage-forms/remove-a-screen.md" %}

{% page-ref page="../designing-processes/environment-variable-management/manage-your-environment-variables/view-all-environment-variables.md" %}

{% page-ref page="../designing-processes/environment-variable-management/create-a-new-environment-variable.md" %}

{% page-ref page="../designing-processes/environment-variable-management/manage-your-environment-variables/edit-an-environmental-variable.md" %}

{% page-ref page="../designing-processes/environment-variable-management/manage-your-environment-variables/remove-an-environment-variable.md" %}

{% page-ref page="add-users/manage-user-accounts/view-all-users.md" %}

{% page-ref page="add-users/create-a-user-account.md" %}

{% page-ref page="add-users/manage-user-accounts/edit-a-user-account.md" %}

{% page-ref page="add-users/manage-user-accounts/remove-a-user-account.md" %}

{% page-ref page="assign-groups-to-users/manage-groups/view-all-groups.md" %}

{% page-ref page="assign-groups-to-users/create-a-group.md" %}

{% page-ref page="assign-groups-to-users/manage-groups/edit-a-group.md" %}

{% page-ref page="assign-groups-to-users/manage-groups/remove-a-group.md" %}

{% page-ref page="../designing-processes/viewing-processes/view-the-list-of-processes/view-your-processes.md" %}

{% page-ref page="../designing-processes/viewing-processes/create-a-process.md" %}

{% page-ref page="../designing-processes/viewing-processes/view-the-list-of-processes/edit-the-name-description-category-or-status-of-a-process.md" %}

