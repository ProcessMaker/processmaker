---
description: >-
  Preview your ProcessMaker Screen, and view how users would enter control data
  into the Screen's JSON data model.
---

# Preview a Screen and Its JSON Data Model

## Overview

To allow ProcessMaker Screens to be used among any [Process](../../viewing-processes/what-is-a-process.md), they are represented in JSON format. Processes are also represented as JSON data models that pass [Request](../../../using-processmaker/requests/what-is-a-request.md) data to [Tasks](../../process-design/model-your-process/process-modeling-element-descriptions.md#user-task) defined in the [Process model](../../process-design/what-is-process-modeling.md). Preview how data in your ProcessMaker Screen is passed to JSON data models.

Use [Preview mode](screens-builder-modes.md#preview-mode) in the following ways:

* In the **Data Input** panel, experiment with how JSON data models for different Processes interact with the JSON data model for your ProcessMaker Screen. Enter a JSON data model as data input into the **Data Input** panel that is to the left of the previewed ProcessMaker Screen.
* In the **Data Preview** panel, view how the ProcessMaker Screen's controls you configured in [Editor mode](screens-builder-modes.md#editor-mode) use data in a JSON data model. To the right of the previewed ProcessMaker Screen, you can view the JSON data model as you enter information into your previewed ProcessMaker Screen. Viewing the JSON data model can be helpful to see how values are entered into the ProcessMaker Screen and how that data may affect other JSON data models.
* Understand how different JSON data models may affect [ProcessMaker Scripts](../../scripts/what-is-a-script.md). ProcessMaker Developers can use a ProcessMaker Screen's JSON data model as variable input to a ProcessMaker Script. The JSON data model from either a Process or a ProcessMaker Screen becomes the variables that ProcessMaker Developers can use to capture what Request participants enter into a Process or a Screen.

![Screens Builder displaying JSON input and output data models in Preview mode](../../../.gitbook/assets/preview-mode-screens-builder-processes.png)

{% hint style="info" %}
Are you a ProcessMaker Developer developing ProcessMaker Scripts? See [Scripts Editor](../../scripts/scripts-editor.md).
{% endhint %}

## Preview JSON Data Models in a ProcessMaker Screen

{% hint style="info" %}
Your user account or group membership must have the following permissions to preview a ProcessMaker Screen:

* Screens: View Screens
* Screens: Edit Screens

See the ProcessMaker [Screens](../../../processmaker-administration/permission-descriptions-for-users-and-groups.md#screens) permissions or ask your ProcessMaker Administrator for assistance.
{% endhint %}

Follow these guidelines to preview a ProcessMaker Screen and how JSON data models interact with it:

1. [Open](../manage-forms/view-all-forms.md) the ProcessMaker Screen. The ProcessMaker Screen is in [Editor mode](screens-builder-modes.md#editor-mode).
2. Click the **Preview** option from Screen Builder's top menu.
3. Optionally, in the **Data Input** panel, enter a JSON data model. This JSON data model may come from a Process or another ProcessMaker Screen. If you enter a JSON data model into the **Data Input** panel, one of the following occurs:
   * **Valid JSON:** The following message displays in the **Data Input** panel: **Valid JSON Data Object**. That JSON data model also displays in the **Data Preview** panel to indicate how that JSON data model interacts with the JSON data model from your previewed ProcessMaker Screen.
   * **Invalid JSON:** The following message displays in the **Data Input** panel: **Invalid JSON Data Object**. Edit the JSON data model until the **Data Input** panel indicates the model is valid.
4. Enter values into the control fields as if you were using the ProcessMaker Screen in a Request. In the **Data Preview** panel to the right of the preview, the JSON data model displays the key-value pairs. The key's values are those you enter in the ProcessMaker Screen preview.

![Data Preview panel displaying the combined JSON data model in Preview mode](../../../.gitbook/assets/data-preview-panel-screen-builder-processes.png)

{% hint style="info" %}
Computed properties also display in the **Data Preview** panel as part of the JSON data model. See [Manage Computed Properties](manage-computed-properties.md).
{% endhint %}

## Related Topics

{% page-ref page="what-is-screens-builder.md" %}

{% page-ref page="view-the-inspector-pane.md" %}

{% page-ref page="screens-builder-modes.md" %}

{% page-ref page="control-descriptions/" %}

{% page-ref page="../../scripts/scripts-editor.md" %}

{% page-ref page="../manage-forms/view-all-forms.md" %}

{% page-ref page="types-for-screens.md" %}

{% page-ref page="add-a-new-page-to-a-screen.md" %}

{% page-ref page="manage-computed-properties.md" %}

{% page-ref page="add-custom-css-to-a-screen.md" %}

{% page-ref page="save-a-screen.md" %}

{% page-ref page="close-screens-builder.md" %}

{% page-ref page="best-practices.md" %}

{% page-ref page="../../viewing-processes/what-is-a-process.md" %}

{% page-ref page="../../../using-processmaker/requests/what-is-a-request.md" %}

{% page-ref page="../../scripts/what-is-a-script.md" %}

