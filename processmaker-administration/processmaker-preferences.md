---
description: Set ProcessMaker preferences.
---

# ProcessMaker Preferences

{% hint style="info" %}
To set ProcessMaker preferences, you must be a member of the ProcessMaker Administrator group. Otherwise, the **Admin** option is not available from the top menu that allows you to set ProcessMaker preferences.
{% endhint %}

## View ProcessMaker Preferences

Follow these steps to view ProcessMaker preferences:

1. [Log in](../using-processmaker/log-in.md#log-in) to ProcessMaker.
2. Click the **Admin** option from the top menu. The **Users** page displays.
3. Click the **Preferences** icon![](../.gitbook/assets/preferences-icon-admin.png). The **Preferences** page displays.  

   ![](../.gitbook/assets/preferences-page-admin.png)

## Set Language and Format Settings

Follow these steps to set language and format settings in ProcessMaker:

1. [View ProcessMaker preferences.](processmaker-preferences.md#view-processmaker-preferences) The **Preferences** page displays.  

   ![](../.gitbook/assets/preferences-page-admin.png)

2. Select from the **Full Name Format** drop-down how full names display in ProcessMaker from the following options:
   * **First Name:** Full names display with the first name preceding the last name.
   * **Last Name:** Full names display with the last name preceding the first name.
3. Select from the **Default Language** drop-down the language ProcessMaker displays throughout the application.

{% hint style="info" %}
Click **Reset** to reset the Localization section settings to default settings if necessary. 
{% endhint %}

## Set Email Notification Settings

Follow these steps to set email notification settings in ProcessMaker:

1. [View ProcessMaker preferences.](processmaker-preferences.md#view-processmaker-preferences) The **Preferences** page displays.  

   ![](../.gitbook/assets/preferences-page-admin.png)

2. Enter in the **Host Name** field the host name or IP address to the SMTP server used for email notifications.
3. Enter in the **Server Port** field the SMTP server port the ProcessMaker application uses to send email notifications. The default port is 25. The default port is used if you do not enter a value.
4. Select the **SSL/TLS** checkbox if SSL/TLS is required by this server.
5. Enter in the **Username** field the username for the account to be authenticated against the SMTP server.
6. Enter in the **Password** field the password for the account to be authenticated against the SMTP server.
7. Select from the **Authentication Method** drop-down the authentication protocol to log in to the SMTP server:
   * SSL
   * GSSAPI
   * NTLM
   * MD5
   * Password
8. Click **Test** to test your email settings that they properly access the email server.
9. Click **Save**. Otherwise, click **Cancel** to not change any ProcessMaker preferences.

