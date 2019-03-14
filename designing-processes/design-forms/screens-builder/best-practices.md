---
description: Follow best practices when designing ProcessMaker Screens.
---

# Screens Builder Best Practices

## Screens Builder Best Practices

Follow these best practices when designing ProcessMaker Screens in your organization:

* **Field naming best practice for controls:** Controls that have the **Field Name** setting use this value as a variable name in the ProcessMaker Screen in which that control is used. Use unique **Field Name** settings from any other control of the same type in all ProcessMaker Screens in your organization. Why? If a Process uses two different ProcessMaker Screens in which two controls of the same type have the same **Field Name** setting, the value of the first ProcessMaker Screen's control overwrites the value of the second during Requests.

  For example, suppose you have a Process that uses two different ProcessMaker Screens that have a Line Input control with the setting `FirstName`. During a Request, the first Line Input control receives a value. As the Request continues, that Line Input control's value automatically overwrites any value in the second ProcessMaker Screen's Line Input control because they have the same **Field Name** setting. This may be unintended. This is why it is helpful to experiment with JSON data models in [Preview mode](preview-a-screen.md).

  To avoid this issue, establish a naming convention with all Process Owners in your organization for **Field Name** settings. For example, use a naming convention such as `<ScreenName>_<FieldName>`. This naming convention minimizes two controls of the same type in different ProcessMaker Screens to have identical names.

* 
## Related Topics

{% page-ref page="../what-is-a-form.md" %}

{% page-ref page="what-is-screens-builder.md" %}

{% page-ref page="types-for-screens.md" %}

{% page-ref page="screens-builder-modes.md" %}

{% page-ref page="view-the-inspector-pane.md" %}

{% page-ref page="control-descriptions/" %}

{% page-ref page="add-a-new-page-to-a-screen.md" %}

{% page-ref page="preview-a-screen.md" %}

{% page-ref page="manage-computed-properties.md" %}

{% page-ref page="add-custom-css-to-a-screen.md" %}

{% page-ref page="save-a-screen.md" %}

{% page-ref page="close-screens-builder.md" %}

