---
description: View the details about a Request in which you started or participated.
---

# Request Details

You can view details about a Request in the following ways:

* Click a Request from any of the tabs in the **Requests** page.
* Click the **Request Details** link from an [advanced search](search-for-a-request.md#advanced-search).

Refer to the topics about Request details:

* [Request Header](request-details.md#request-header): See the Request title and status at a glance.
* [Current Tasks](request-details.md#current-tasks): See current and pending tasks associated with the Request.
* [Statistics](request-details.md#statistics): See statistics about the Request at a glance.
* [Summary](request-details.md#summary): View data entered into the Request from participants who have completed their tasks.
* [Timeline](request-details.md#timeline): View a vertical timeline of all interactions that have occurred in the Request.
* [Workflow](request-details.md#workflow): View process workflow associated with the Request in a BPMN 2.0 diagram.
* [Documents](request-details.md#documents): View documents associated with the Request.
* [Comments](request-details.md#comments): View comments associated with the Request.

## Request Header

The Request header is a gray-colored bar near the top of the page. The Request header displays the following:

* **Request title:** The Request title is the title of the Request as entered by the person who started the Request.
* **Status:** The status of the request displays as one of the following:
  * **In Progress:** The Request is in progress. View the current and pending tasks for that Request in the [Current Tasks](request-details.md#current-tasks) pane.
  * **Completed:** The Request is completed.
  * **Canceled:** The Request has been canceled by the Request initiator.
* **Back:** Click the **Back** button to return to the previous page.

## Current Tasks

The **Current Tasks** pane displays current and pending tasks associated with the Request, as well as the status of each. Tasks that are open or pending display above tasks that are waiting.

{% hint style="info" %}
The **Current Tasks** pane does not display completed tasks associated with the Request. View the [Timeline](request-details.md#request-timeline) tab to view the history of the Request.
{% endhint %}

The **Current Tasks** pane displays the following:

* **Task recipient and task name:** The name of the current or pending task displays to the right of the task recipient's avatar.
* **Task status:** A representation of the task's status displays as a colored dot to the right of the task name. The dot's color represents the task's status as follows:
  * **&lt;screenshot&gt;:** The task is pending. A task is pending when the task recipient can work on the task now. Mouse hover over the dot to see the task recipient's name and for how long the task has been pending.
  * **&lt;screenshot&gt;:** The task is waiting. A task is waiting when it is not pending.
* **Open Task:** The **Open Task** button displays to the right of the task status if you are the task recipient and the task is currently pending. Click **Open Task** to open the task. The task displays.

## Statistics

Request statistics display the following:

* **Created by:** The **Created by** field displays the full name of the person who started the Request.
* **Date Created:** The **Date Created** field displays the date and time the Request was created in the following format: `MM/DD/YYYY HH:MM`.
* **Created:** The **Created** field displays how old the Request is.
* **Last Modification:** The **Last Modification** field displays how long ago the Request was last modified.
* **Duration:** The **Duration** field displays the duration of the Request. ~~How is this different than what the Created field displays other than its format as shown in the mock-up?~~

## Request Information

Refer to the following topics about information regarding a Request.

### Summary

The **Summary** tab displays data entered into the Request from participants who have completed their tasks. ~~How does the user select which completed task information to view?~~ The following information displays about the Request:

* **First Name:** The **First Name** field displays the first name of the user who completed the selected task.
* **Last Name:** The **Last Name** field displays the last name of the user who completed the selected task.
* **Username:** The **Username** field displays the username of the user who completed the selected task.
* **Email Address:** The **Email Address** field displays the email address for the user who completed the selected task.
* **Information:** Below the **Email Address** field displays information as entered into the selected completed task.

### Timeline

The **Timeline** tab displays a vertical timeline of all interactions that have occurred in the Request. The Timeline displays each point of interaction in the Request as a dot. Older interaction points display at the top of the Timeline. The Request's current state displays at the bottom.

The Timeline displays both user and automatic interactions with the Request in the following ways:

* **User interactions:** User interactions display to the left of the Timeline. Each user interaction displays that user's avatar, that user's first and last name, and the date of that user's interaction.
* **Automatic interactions:** Automatic interactions are those performed by a script. Automatic interactions display to the right of the Timeline as a robot avatar. Beside the robot avatar displays the the date the script executed.

### Workflow

The **Workflow** tab displays process workflow associated with the Request in a BPMN 2.0 diagram. The process diagram displays using the following color scheme:

* ~~&lt;Color&gt;: Document each color and what it represents in process workflow.~~

## Documents

The **Documents** pane displays documents associated with the Request. Documents become associated with a Request when a Recipient participant uploads a document in a task.

Click on a document to download it to your computer. If you do not have permission to download documents, you cannot click on any document.

{% hint style="info" %}
Contact your ProcessMaker Administrator to request permission to download documents from the Request details page.
{% endhint %}

## Comments

The Request details displays each comment that has been entered into a task Dynaform in the order that they were entered. The oldest comment displays at the top of the comment list.

The following information displays for each comment:

* the username of the person who entered the comment
* the date the person entered the comment
* the comment

To enter a comment, enter the content of your comment into the comment field, and then click **Comment**.

If you have not entered any comments into the Request, the following message displays: **You have not posted any comments yet.**

