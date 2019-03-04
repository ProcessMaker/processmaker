---
description: Edit a client authentication key.
---

# Edit a Client Authentication Key

## Edit a Client Authentication Key

{% hint style="info" %}
~~Your user account or group membership must have the "Auth Client: View Auth Client" permission to edit authentication keys.~~

~~See the Auth Client permissions or ask your ProcessMaker Administrator for assistance.~~
{% endhint %}

Follow these steps to edit a client authentication key that grants access to the [ProcessMaker 4 REST API](https://develop-demo.bpm4.qa.processmaker.net/api/documentation):

1. [View all client authentication keys.](view-all-client-authentication-keys.md#view-all-scripts) The **Auth Clients** page displays.
2. Select the **Edit** icon![](../../../.gitbook/assets/edit-icon.png) for the client authentication key to edit. The **Edit Auth Client** screen displays.  

   ![](../../../.gitbook/assets/edit-auth-client-screen-admin.png)

3. Edit the following information about the client authentication key as necessary:
   * In the **Name** field, edit the name of the client authentication key. ~~This is a required field.~~
   * In the **Redirect URL** field, edit the URL that redirects the authenticated client to your ProcessMaker 4 application server. ~~This is a required field.~~
4. Click **Save**.

{% hint style="info" %}
The **Client Secret** value cannot be changed.
{% endhint %}

## Related Topics

{% page-ref page="../what-is-client-authentication.md" %}

{% page-ref page="view-all-client-authentication-keys.md" %}

{% page-ref page="delete-a-client-authentication-key.md" %}

{% page-ref page="../create-a-new-client-authentication-key.md" %}

