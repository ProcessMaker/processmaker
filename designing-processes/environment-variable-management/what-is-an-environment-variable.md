---
description: Understand how to use Environment Variables in ProcessMaker 4.
---

# What is an Environment Variable?

In ProcessMaker 4, an Environment Variable is a secure, abstract proxy for any sensitive information that you need to use in a process. Any ProcessMaker Environment Variable can be re-used in any process to abstract information securely throughout your organization.

The Process Owner that creates a ProcessMaker Environment Variable does not need to know the sensitive information that the Environment Value represents. The Process Owner creates the placeholder for that sensitive information, and then the value for the ProcessMaker Environment Value is entered by a person or ProcessMaker Script when the Request is in progress.

Below are a few examples how to use ProcessMaker Environment Variables:

* A person's username and password may be entered into a ProcessMaker Screen as part of a [task](../../using-processmaker/task-management/what-is-a-task.md) that passes each ProcessMaker Environment Variable's value securely to a third-party service.
* A person's credit card information securely interacts with Stripe online payment processing service through multiple ProcessMaker Environment Variables.
* A person's banking information securely interacts with a bank to make transactions securely while following compliance protocols.

## Related Topics

{% page-ref page="manage-your-environment-variables/view-all-environment-variables.md" %}

{% page-ref page="manage-your-environment-variables/search-for-an-environment-variable.md" %}

{% page-ref page="manage-your-environment-variables/edit-an-environmental-variable.md" %}

{% page-ref page="manage-your-environment-variables/remove-an-environment-variable.md" %}

{% page-ref page="create-a-new-environment-variable.md" %}

