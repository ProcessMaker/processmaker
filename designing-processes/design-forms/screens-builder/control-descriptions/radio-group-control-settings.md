---
description: Add a Radio Group control from which the user has only a binary on/off option.
---

# Radio Group Control Settings

##  Control Description

A radio group control is used instead of a checkbox or check-group control, when the user must select only one option from a domain of choices.

##  Add the Control to a ProcessMaker Screen

Follow these steps to add this control to the ProcessMaker Screen:

1. View the ProcessMaker Screen page to which to add the control.
2. Go to the **Controls** panel on the left side of the ProcessMaker Screen.
3. Drag the **Radio Group** icon ![](../../../../.gitbook/assets/radio-group-control-screens-builder-processes.png) from the **Controls** panel to the ProcessMaker Screen page.
4. Drop into the ProcessMaker Screen where you want the control to display on the page.  

   ![](../../../../.gitbook/assets/radio-group-control-placed-screens-builder-processes.png)

## Inspector Settings <a id="inspector-settings"></a>

For information how to view the **Inspector** panel, see [View the Inspector Panel](https://processmaker.gitbook.io/processmaker-4-community/-LPblkrcFWowWJ6HZdhC/designing-processes/design-forms/screens-builder/view-the-inspector-pane).

Below are Inspector settings for the Line Input control:

* **Field Name:** Specify the internal data name of the control that only the Process Owner views at design time. Set by default as **New Radio Button Group.**
* **Help Text:** Specify text that provides additional guidance on the field's use.
* **Option List:** Specify the list of options available in the radio group. Each option has the following settings:

  * **Value:** **Value** is the internal data name for the option that only the Process Owner views at design time.
  * **Content:** **Content** is the option label displayed to the form user.
  * **Actions:** Click the Remove ![](https://firebasestorage.googleapis.com/v0/b/gitbook-28427.appspot.com/o/assets%2F-LJ0aNaVW1m7sNsxVJLV%2F-LRd_ECXdNI2bEak7VEt%2F-LRdb7nEk9dSH8ZO3LOs%2FRemove%20Page%20Screens%20Editor%20-%20Processes.png?alt=media&token=1045667a-c7bd-4431-8bca-556e389e6d11) icon to remove the option.

    A default option is called **new** with the content **New Option**.

  Follow these steps to add an option:

  1. Click **Add Option** from below the **Option List** setting. The **Add New Option** screen displays.

     ​![](https://firebasestorage.googleapis.com/v0/b/gitbook-28427.appspot.com/o/assets%2F-LJ0aNaVW1m7sNsxVJLV%2F-LRh9g3GQGcB5CtncSF-%2F-LRhGwUGe2CECm6rxBfP%2FAdd%20New%20Option%20Screen%20Screen%20Builder%20-%20Processes.png?alt=media&token=3f36252b-6f82-44b7-aef3-bab793d1e6e2)​

  2. Enter in the **Option Value** field the **Value** option value \(as described above\).
  3. Enter in the **Option Label** field the **Content** option value \(as described above\).
  4. Click **OK**. Otherwise, click **Cancel** to not add a new option.

## Related Topics <a id="related-topics"></a>

{% page-ref page="../view-the-inspector-pane.md" %}

{% page-ref page="./" %}

