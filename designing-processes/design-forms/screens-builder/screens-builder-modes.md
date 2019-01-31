---
description: Understand each of Screens Builder's modes.
---

# Screens Builder Modes

## Editor Mode

Use Editor mode to build your ProcessMaker Screen. While in Editor mode, use the **Inspector** panel to configure [controls](control-descriptions/) that you place into your ProcessMaker Screen.

{% hint style="info" %}
### Learn More About the Inspector Panel

See [View the Inspector Panel](view-the-inspector-pane.md).

### Learn How to Configure Controls

See [Control Descriptions and Inspector Settings](control-descriptions/).

### Learn How to Create a Multi-Page ProcessMaker Screen

See [Add a New Page to a Screen](add-a-new-page-to-a-screen.md).
{% endhint %}

## Preview Mode

Use Preview mode to view how your ProcessMaker Screen displays.

Furthermore, use Preview mode to view how the ProcessMaker Screen's controls you configured in Editor mode use data in a JSON data model. To allow ProcessMaker Screens to be used among any Process, they are represented in JSON data models. You can view the JSON data model in Preview mode as you enter information into your previewed ProcessMaker Screen. Viewing the JSON data model can be helpful to see how values are entered into the ProcessMaker Screen.

{% hint style="info" %}
 See [Preview a Screen and Its JSON Data Model](preview-a-screen.md).
{% endhint %}

## Computed Properties Mode

Use Computed Properties mode to add Properties to a ProcessMaker Screen's JSON data model. A Property represents any value, mathematical calculation, or formula that computes a value. A Property's computation can be determined either through a mathematical formula or valid JavaScript, and may include values from [ProcessMaker Screen control](control-descriptions/) values during a Request. Likewise, a computed Property's value can be displayed in a ProcessMaker Screen control. Computed Properties can only be used within and only affect the currently opened ProcessMaker Screen.

{% hint style="info" %}
See [Manage Computed Properties](manage-computed-properties.md).
{% endhint %}

## Related Topics

{% page-ref page="control-descriptions/" %}

{% page-ref page="preview-a-screen.md" %}

{% page-ref page="manage-computed-properties.md" %}

