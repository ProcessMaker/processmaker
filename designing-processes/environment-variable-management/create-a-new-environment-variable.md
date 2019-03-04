---
description: >-
  Create a new ProcessMaker Environment Variable that can be re-used in any
  process.
---

# Create a New Environment Variable

## Create a New ProcessMaker Environment Variable

{% hint style="info" %}
Your user account or group membership must have the following permissions to create a ProcessMaker Environment Variable:

* Environment Variables: View Environment Variables
* Environment Variables: Create Environment Variables

See the ProcessMaker [Environment Variable](../../processmaker-administration/permission-descriptions-for-users-and-groups.md#environment-variables) permissions or ask your ProcessMaker Administrator for assistance.
{% endhint %}

Follow these steps to create a new ProcessMaker Environment Variable:

1. [View your ProcessMaker Environment Variables.](manage-your-environment-variables/view-all-environment-variables.md) The **Environment Variables** page displays.
2. Click the **+Environment Variable** button. The **Create Environment Variable** page displays.  

   ![](../../.gitbook/assets/create-environment-variable-screen-processes.png)

3. In the **Variable Name** field, enter the name of the ProcessMaker Environment Variable. The ProcessMaker Environment Variable name can only contain letters, numbers, and dashes. ~~Character length limitation?~~ This is a required field.
4. In the **Description** field, enter a description for the ProcessMaker Environment Variable. This is a required field. ~~Character length limitation and/or unsupported characters?~~
5. In the **Value** field, enter the value for the ProcessMaker Environment Variable. Entering a value is optional since ProcessMaker Environment Variables are secure, abstract proxies for sensitive information you assign to contain a value that can be determined during an in-progress Request.
6. Click **Save**. The screen closes and the **Environment Variables** page displays with your new Environment Variable.

## Related Topics

{% page-ref page="what-is-an-environment-variable.md" %}

{% page-ref page="manage-your-environment-variables/view-all-environment-variables.md" %}

{% page-ref page="manage-your-environment-variables/search-for-an-environment-variable.md" %}

{% page-ref page="manage-your-environment-variables/edit-an-environmental-variable.md" %}

{% page-ref page="manage-your-environment-variables/remove-an-environment-variable.md" %}

