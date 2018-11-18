---
description: Understand what a Script does in ProcessMaker 4.
---

# What is a Script?

In ProcessMaker 4, Scripts allow Process Owners and ProcessMaker Developers to write self-contained programmatic actions that can be called from any process at run-time. The same ProcessMaker Script can be deployed in any process created in your organization. In other words, "write once, use anywhere."

While writing a ProcessMaker Script, test it before you deploy it. ProcessMaker Scripts are tested within the authoring environment to ensure they function as intended. While testing, do the following:

* Use the JSON data model that you can [preview from your ProcessMaker Screens](../design-forms/screens-builder/preview-a-screen.md) to ensure that variables designed from a ProcessMaker Screen function as intended in your ProcessMaker Script.
* [Incorporate other JSON-formatted data](), such as API keys, to ensure your ProcessMaker Script uses them correctly during your testing.

During run-time, ProcessMaker Scripts run within isolated containers for greater security. After the ProcessMaker Script runs and returns output to the process, the container that isolated and ran the script automatically removes itself.

ProcessMaker 4 supports Lua and PHP Scripts out-of-the-box.

## Related Topics

{% page-ref page="what-is-a-script.md" %}

{% page-ref page="manage-scripts/search-for-a-script.md" %}

{% page-ref page="manage-scripts/edit-a-script.md" %}

{% page-ref page="manage-scripts/stop-a-script.md" %}

{% page-ref page="manage-scripts/preview-a-script.md" %}

{% page-ref page="manage-scripts/remove-a-script.md" %}

{% page-ref page="create-a-new-script.md" %}

{% page-ref page="scripts-editor.md" %}

{% page-ref page="scripts-editor.md" %}

