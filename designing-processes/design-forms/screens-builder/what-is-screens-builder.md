---
description: Understand what Screens Builder is in ProcessMaker 4.
---

# What is Screens Builder?

{% hint style="info" %}
To develop a ProcessMaker Screen, you must be a member of the Process Owner group. Otherwise, the **Processes** option is not available from the top menu that allows you to perform Screen management activities.
{% endhint %}

## Overview

Screens Builder allows Process Owners to design screens for their business processes. ProcessMaker Screens are independent of business processes. Any ProcessMaker Screen can be used in any process in your organization. As a Process Owner, you can use in your process a ProcessMaker Screen another Process Owner designed.

Process Owners can design different types of ProcessMaker Screens. See [Screen Types](types-for-screens.md) to learn more.

ProcessMaker Screens are composed of controls. Use controls to provide your ProcessMaker Screen with specific functionality. Below are a few examples of these controls:

* Display text.
* Provide a group of radio buttons that allows Request participants to select an option.
* Provide a checkbox that allows Request participants to approve or reject a request.
* Provide a text area where Request participants can enter text.
* Provide a date control where Request participants can select a date.

See [Control Descriptions and Inspector Settings](control-descriptions/) for more information about ProcessMaker Screen controls.

## Editor and Preview Modes

Screens Builder provides an Editor and Preview mode.

### Editor Mode

Use Editor mode to build your ProcessMaker Screen. While in Editor mode, use the Inspector panel to configure [controls](control-descriptions/) that you place into your ProcessMaker Screen.

{% hint style="info" %}
See [View the Inspector Panel](view-the-inspector-pane.md) for more information.

See [Control Descriptions and Inspector Settings](control-descriptions/) for information how to configure controls you place into your ProcessMaker Screen.

Need a multi-page ProcessMaker Screen? See [Add a New Page to a Screen](add-a-new-page-to-a-screen.md) for more information.
{% endhint %}

### Preview Mode

Use Preview mode to view how your ProcessMaker Screen displays. Furthermore, Preview mode allows you to view how the ProcessMaker Screen's controls you configured in Editor mode contain data in the form of a JSON data model.

{% hint style="info" %}
See [Preview a Screen and Its JSON Data Model](preview-a-screen.md) for more information.
{% endhint %}

The JSON data model uses key-value pairs: the key names are those you set when you configured your ProcessMaker Screen controls, while the values are those that you entered while previewing the Screen. During an actual Request in a ProcessMaker process, these values would be those entered by a Request participant.

## Related Topics



