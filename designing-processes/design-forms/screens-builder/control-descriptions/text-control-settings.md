---
description: Add a field that displays text for the form user.
---

# Text Control Settings

## Control Description

The Text control adds a text field that displays text to the form user.

## Add the Control to a ProcessMaker Screen

Follow these steps to add this control to the ProcessMaker Screen:

1. View the ProcessMaker Screen page to which to add the control.
2. Go to the **Controls** panel on the left side of the ProcessMaker Screen.
3. Drag the **Text** icon ![](../../../../.gitbook/assets/text-control-screens-builder-processes.png) from the **Controls** panel to the ProcessMaker Screen page.
4. Drop into the ProcessMaker Screen where you want the control to display on the page.   

   ![](../../../../.gitbook/assets/text-control-placed-screens-builder-processes.png)

Below is a Text control in Preview mode.

![Text control in Preview mode](../../../../.gitbook/assets/text-control-preview-screens-builder-processes.png)

## Inspector Settings

{% hint style="info" %}
For information how to view the **Inspector** panel, see [View the Inspector Panel](../view-the-inspector-pane.md).
{% endhint %}

Below are Inspector settings for the Text control:

* **Text Label:** Specify what text displays for the Text control. Set by default as **New Text**. You can change what text will display.
* **Font Weight:** Sets the weight of the **Text Label** text. Set by default as **Normal**. You can change to **Bold**.
* **Font Size:** Sets the size of the **Text Label** text in em units. Set by default as **1**. You can change the font size to the following options:
  * 0.5
  * 1
  * 1.5
  * 2

{% hint style="info" %}
Below are some ways to render Request data to display as text in a Text control:

* Use mustache template syntax to reference the Request data. Example: `Customer First Order Name: {{customer.orders.0.name}}`
* \`\`

  Include your own HTML into the text control along with template references. Example: `Customer First Name: <strong>{{customer.firstname}}</strong>`
{% endhint %}

## Related Topics

{% page-ref page="../view-the-inspector-pane.md" %}

{% page-ref page="./" %}

