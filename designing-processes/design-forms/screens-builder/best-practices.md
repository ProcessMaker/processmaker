---
description: Follow best practices when designing ProcessMaker Screens.
---

# Screens Builder Best Practices

## Screens Builder Best Practices

Follow these best practices when designing ProcessMaker Screens:

* **Field naming best practice for controls:** Controls that have the **Field Name** setting use this value as a variable name in the ProcessMaker Screen in which that control is used. Use unique **Field Name** settings from any other control of the same type in all ProcessMaker Screens in your organization. Why? If a process uses two different ProcessMaker Screens in which two controls of the same type have the same **Field Name** setting, the value of the first ProcessMaker Screen's control overwrites the value of the second during Requests.

  For example, suppose you are using a process that uses two different ProcessMaker Screens that have a Line Input control with the setting `FirstName`. During a Request, the first Line Input control receives a value. As the Request continues, that Line Input control's value automatically overwrites any value in the second ProcessMaker Screen's Line Input control because they have the same **Field Name** setting. This may be unintended.

  To avoid this issue, establish a naming convention with all Process Owners in your organization for **Field Name** settings. For example, use a naming convention such as `<ScreenName>_<FieldName>`. This naming convention minimizes two controls of the same type in different ProcessMaker Screens to have identical names.

* 


