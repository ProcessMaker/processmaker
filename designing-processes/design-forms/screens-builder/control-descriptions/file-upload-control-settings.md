---
description: >-
  Add a File Upload control to which the form user can upload files from a local
  computer.
---

# File Upload Control Settings

## Control Description

The File Upload control adds an area in the ProcessMaker Screen to which the form user can upload one or more files from a local computer. The form user can drag-and-drop the file\(s\) or select a button from which to locate the file\(s\) for upload. The uploaded file\(s\) can be referenced in a later step in the Request.

{% hint style="info" %}
This control is not available for [Display](../types-for-screens.md#display)-type ProcessMaker Screens. See [Screen Types](../types-for-screens.md).
{% endhint %}

## Add the Control to a ProcessMaker Screen <a id="add-the-control-to-a-processmaker-screen"></a>

{% hint style="info" %}
Your user account or group membership must have the following permissions to add a control to a ProcessMaker Screen:

* Screens: View Screens
* Screens: Edit Screens

See the ProcessMaker [Screens](../../../../processmaker-administration/permission-descriptions-for-users-and-groups.md#screens) permissions or ask your ProcessMaker Administrator for assistance.
{% endhint %}

Follow these steps to add this control to the ProcessMaker Screen:

1. [Create](../../manage-forms/create-a-new-form.md) or [open](../../manage-forms/view-all-forms.md) the ProcessMaker Screen. The ProcessMaker Screen is in [Editor mode](../screens-builder-modes.md#editor-mode).
2. View the ProcessMaker Screen page to which to add the control.
3. Go to the **Controls** panel on the left side of the ProcessMaker Screen.
4. Drag the **File Upload** icon![](../../../../.gitbook/assets/file-upload-control-screens-builder-processes.png)from the **Controls** panel anywhere within the ProcessMaker Screen canvas represented by the dotted-lined box. Existing controls on the ProcessMaker Screen canvas adjust positioning based on where you drag the control.
5. Drop into the ProcessMaker Screen where you want the control to display on the page.  

   ![](../../../../.gitbook/assets/file-upload-control-placed-screens-builder-processes.png)

6. Configure the File Upload control. See [Inspector Settings](file-upload-control-settings.md#inspector-settings).

Below is a File Upload control in Preview mode.

![File Upload Control in Preview mode](../../../../.gitbook/assets/file-upload-control-preview-screens-builder-processes.png)

## Delete the Control from a ProcessMaker Screen

{% hint style="warning" %}
Deleting a control also deletes configuration for that control. If you add another control, it will have default settings.
{% endhint %}

Click the **Delete** icon![](../../../../.gitbook/assets/delete-screen-control-screens-builder-processes.png)for the control to delete it.

## Inspector Settings <a id="inspector-settings"></a>

{% hint style="info" %}
### Don't Know What the Inspector Panel Is?

See [View the Inspector Panel](../view-the-inspector-pane.md).

### Permissions Required to View Control Settings

Your user account or group membership must have the following permissions to edit a ProcessMaker Screen control:

* Screens: View Screens
* Screens: Edit Screens

See the ProcessMaker [Screens](../../../../processmaker-administration/permission-descriptions-for-users-and-groups.md#screens) permissions or ask your ProcessMaker Administrator for assistance.
{% endhint %}

Below are Inspector settings for the File Upload control:

* ~~**Field Name:** Specify the unique internal data name of the control that only the Process Owner views at design time. This is a required setting. Use the **Field Name** value for this control to reference it in~~ [~~**Show If** setting expressions~~](expression-syntax-components-for-show-if-control-settings.md)~~.~~
* **Text Label:** Specify the field label text that displays. **New File Upload** is the default value.
* **Upload Name:** Specify the unique name associated with the uploaded file\(s\). This name can be referenced in a later step in the Request. This setting has no default value.
* **Show If:** Specify an expression that dictates the condition\(s\) under which the File Upload control displays. See [Expression Syntax Components for "Show If" Control Settings](expression-syntax-components-for-show-if-control-settings.md#expression-syntax-components-for-show-if-control-settings). If this setting does not have an expression, then this control displays by default.

## Related Topics <a id="related-topics"></a>

{% page-ref page="../types-for-screens.md" %}

{% page-ref page="../view-the-inspector-pane.md" %}

{% page-ref page="./" %}

{% page-ref page="text-control-settings.md" %}

{% page-ref page="line-input-control-settings.md" %}

{% page-ref page="select-control-settings.md" %}

{% page-ref page="radio-group-control-settings.md" %}

{% page-ref page="checkbox-control-settings.md" %}

{% page-ref page="textarea-control-settings.md" %}

{% page-ref page="date-picker-control-settings.md" %}

{% page-ref page="submit-button-control-settings.md" %}

{% page-ref page="page-navigation-button-control-settings.md" %}

{% page-ref page="multi-column-button-control-settings.md" %}

{% page-ref page="record-list-control-settings.md" %}

{% page-ref page="image-control-settings.md" %}

{% page-ref page="file-download-control-settings.md" %}

{% page-ref page="expression-syntax-components-for-show-if-control-settings.md" %}

