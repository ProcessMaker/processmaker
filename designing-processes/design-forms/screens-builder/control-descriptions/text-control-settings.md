---
description: Add a field that displays text.
---

# Text Box Settings

## Control Description

The Text Box control adds a text field that displays text.

## Add the Control to a ProcessMaker Screen

Follow these steps to add this control to the ProcessMaker Screen:

1. View the ProcessMaker Screen page to which to add the control.
2. Go to the **Controls** panel on the left side of the ProcessMaker Screen.
3. Drag the **Text Box** icon ![](../../../../.gitbook/assets/text-control-screens-builder-processes.png) from the **Controls** panel anywhere within the ProcessMaker Screen canvas represented by the dotted-lined box. Existing controls on the ProcessMaker Screen canvas adjust positioning based on where you drag the control.
4. Drop into the ProcessMaker Screen where you want the control to display on the page.   

   ![](../../../../.gitbook/assets/text-control-placed-screens-builder-processes.png)

Below is a Text Box control in Preview mode.

![Text Box control in Preview mode](../../../../.gitbook/assets/text-control-preview-screens-builder-processes.png)

## Inspector Settings

{% hint style="info" %}
See [View the Inspector Panel](../view-the-inspector-pane.md) for information how to view the **Inspector** panel.
{% endhint %}

Below are Inspector settings for the Text Box control:

* **Text Content:** Specify what text displays for the Text Box control. **New Text** is the default value. You can change what text will display.
* **Font Weight:** Sets the weight of the **Text Content** text. **Normal** is the default option. You can change to **Bold**.
* **Text Color:** Sets the color of the displayed text. Use any HTML or Hex code. This setting has no default value.
* **Text Alignment:** Sets the text alignment. **Left** is the default option. Select one of the following options:
  * Center
  * Left
  * Right
  * Justify
* **Font Size:** Sets the size of the **Text Label** text in em units. **1** is the default option. Select one of the following options:
  * 0.5
  * 1
  * 1.5
  * 2

{% hint style="info" %}
Below are some ways to render Request data to display as text in a Text Box control:

* Use mustache template syntax to reference the Request data. Example: `Customer First Order Name: {{customer.orders.0.name}}`
* Include your own HTML syntax in the Text Box control along with template references. Example: `Customer First Name: <strong>{{customer.firstname}}</strong>`
{% endhint %}

## Related Topics

{% page-ref page="../view-the-inspector-pane.md" %}

{% page-ref page="./" %}

