---
description: Welcome to ProcessMaker 4 documentation. This is a good place to start.
---

# How to Use This Document

## Overview

ProcessMaker 4 is a next-generation business process application to easily design and implement BPMN 2.0 compliant business processes within a modern and extensible system.

Our goal for ProcessMaker 4 is to deliver simplicity, but to allow complexity. ProcessMaker 4 is easy to use, but allows you to design customized processes and end-user experiences.

## Use This Document Based on How You Use ProcessMaker

This document is organized based on how different roles use ProcessMaker 4. Refer to the following roles:

* [ProcessMaker Administrator](how-to-use-this-document.md#processmaker-administrator)
* [Process Owner](how-to-use-this-document.md#process-owner)
* [ProcessMaker Developer](how-to-use-this-document.md#processmaker-developer)
* [ProcessMaker User](how-to-use-this-document.md#processmaker-user)

### ProcessMaker Administrator

A ProcessMaker Administrator installs ProcessMaker on-premises. \(On-premises installations are not necessary for ProcessMaker Enterprise cloud deployments.\) A ProcessMaker Administrator also performs administrative tasks in ProcessMaker.

Refer to the **Install ProcessMaker** section that includes the following topics:

* [On-Premises Server Requirements](../install-processmaker/prerequisites.md)
* [On-Premises Install Guide](../install-processmaker/installation-guide.md)

Refer to the **ProcessMaker Administration** section that includes the following topics:

* [User Management](../processmaker-administration/add-users/)
* [Group Management](../processmaker-administration/assign-groups-to-users/)
* [Permission Descriptions for Users and Groups](../processmaker-administration/permission-descriptions-for-users-and-groups.md)
* [Client Authentication Management](../processmaker-administration/auth-client-management/)
* [Queue Management](../processmaker-administration/queue-management/)

### Process Owner

A Process Owner creates and maintains ProcessMaker [Processes](../designing-processes/viewing-processes/what-is-a-process.md) that both people use to make [Requests](../using-processmaker/requests/what-is-a-request.md).

Refer to the **Manage and Model Processes** section that includes the following topics:

* [Process Management](../designing-processes/viewing-processes/)
* [Script Management](../designing-processes/scripts/)
* [Screen Management](../designing-processes/design-forms/)
* [Environment Variable Management](../designing-processes/environment-variable-management/)
* [Process Modeling](../designing-processes/process-design/)

### ProcessMaker Developer

A ProcessMaker Developer extends out-of-the-box ProcessMaker functionality in the following ways:

* Develop [ProcessMaker Scripts](../designing-processes/scripts/what-is-a-script.md) that Process Owners use in Processes.
* Develop Connectors that perform custom functions, and then package those Connectors for distribution.

Refer to the [Script Management](../designing-processes/scripts/) section, especially the [Scripts Editor](../designing-processes/scripts/scripts-editor.md) topic.

Refer to the **Connector Development** section.

Refer to the **Package Development and Distribution** section.

### ProcessMaker User

A ProcessMaker user is a person whose only interaction with ProcessMaker is to start, cancel, and/or participate in Requests. 

Refer to the **Using ProcessMaker** section that includes the following topics:

* [Log On to ProcessMaker](../using-processmaker/log-in.md)
* [Profile Settings](../using-processmaker/profile-settings.md)
* [View ProcessMaker Version Information](../using-processmaker/application-version-details.md)
* [Log Out of ProcessMaker](../using-processmaker/log-out.md)
* [Requests](../using-processmaker/requests/)
* [Task Management](../using-processmaker/task-management/)
* [Notifications](../using-processmaker/notifications.md)
* [Control How Tabular Information Displays](../using-processmaker/control-how-requests-display-in-a-tab.md)

## Document Conventions

This document uses different font styles, types, and weights to represent types of information. The conventions described below are used in paragraphs and do not represent style variations in document titles or headers, nor standard document conventions such as for hyperlinks.

The table below describes these document conventions.

<table>
  <thead>
    <tr>
      <th style="text-align:left">Convention</th>
      <th style="text-align:left">Description</th>
      <th style="text-align:left">Examples</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td style="text-align:left"><b>Bold</b>
      </td>
      <td style="text-align:left">
        <p>Represents the following:</p>
        <ul>
          <li>Application labels such as for menus, fields, and panels</li>
          <li>Application messages displayed to the user</li>
        </ul>
      </td>
      <td style="text-align:left">
        <ul>
          <li>Click the <b>Submit</b> button.</li>
          <li>The following message displays: <b>The file was saved successfully.</b>
          </li>
        </ul>
      </td>
    </tr>
    <tr>
      <td style="text-align:left"><code>Code</code>
      </td>
      <td style="text-align:left">
        <p>Represents the following:</p>
        <ul>
          <li>File extension types</li>
          <li>Code samples and code blocks</li>
        </ul>
      </td>
      <td style="text-align:left">
        <ul>
          <li>A <code>.deb</code> file extension is downloaded.</li>
          <li><code>npm install</code>
          </li>
        </ul>
      </td>
    </tr>
  </tbody>
</table>