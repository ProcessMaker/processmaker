---
description: Understand what a script does in ProcessMaker 4.
---

# What is a Script?

In ProcessMaker 4, scripts allow Process Owners and ProcessMaker Developers to write self-contained programmatic actions that can be called from any process at run-time. The same ProcessMaker script can be deployed in any process created for your organization. In other words, "write once, use anywhere."

While writing a ProcessMaker script, test it before you deploy it. ProcessMaker scripts are tested within the authoring environment to ensure they function as intended.

During run-time, ProcessMaker scripts run within isolated containers for greater security. After the ProcessMaker script runs and returns output to the process, the container that isolated and ran the script automatically removes itself.

ProcessMaker 4 supports Lua and PHP scripts out-of-the-box.

