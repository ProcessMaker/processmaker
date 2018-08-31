---
description: Understand hardware requirements and the supported monitoring tool.
---

# Installation Requirements

## Server Hardware Requirements

For personal use \(non-production\), ProcessMaker supports any computer with a modern CPU, an Internet connection, and 2GB of RAM.

For production use \(using live data\), the hardware requirements may vary based on the number of concurrent users, repository size and system configuration. ~~Larger implementations may require some configuration tuning to perform optimally. For more information, see~~ [~~ProcessMaker Server Sizing~~](https://wiki.processmaker.com/3.2/ProcessMaker_Server_Sizing)~~.~~

For production use, install ProcessMaker on a dedicated server or virtual machine with a dedicated Internet connection.

## New Relic: Supported Monitoring Tool

[New Relic](https://newrelic.com/) is the recommended monitoring tool to use with ProcessMaker.

### Disable the "Browser Application Monitoring" Instrumentation Feature

To work with ProcessMaker, the "Browser Application Monitoring" instrumentation feature must be disabled in the New Relic configurations.

{% hint style="warning" %}
 If the "Browser Application Monitoring" instrumentation feature is enabled, ProcessMaker behavior will be affected. Disable this feature.
{% endhint %}

To disable the "Browser Application Monitoring" instrumentation feature, follow these steps:

**1.** Log in to New Relic.

**2.** Go to the **Browser** panel.

![Accessing the Browser panel](https://wiki.processmaker.com/sites/default/files/3.1NewRelicBrowser.png)

**3.** Select the ProcessMaker workspaces that are being monitored.

![Monitored ProcesMaker workspaces](https://wiki.processmaker.com/sites/default/files/3.1NewRelicWorkspace.png)

**4.** Go to **Settings &gt; Application settings** in the menu on the left side of the window.

![Select Application settings](https://wiki.processmaker.com/sites/default/files/3.1NewRelicApplicationSettings.png)

**5.** Select the **Off - Disables Browser Application Monitoring Instrumentation** option to disable the Browser Application Monitoring instrumentation.

![Disabling the Browser Application Monitoring instrumentation](https://wiki.processmaker.com/sites/default/files/3.1NewRelicOption.png)

**6.** Click on the **Save application settings** button at the end of the page to save the changes.

## Considerations for Production Servers

For production servers, it is NOT recommended to have different ProcessMaker instances installed on different ports in the same server using:

* The same server domain \(IP\)
* The same workspace name

