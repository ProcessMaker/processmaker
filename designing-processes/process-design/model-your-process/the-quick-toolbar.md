---
description: >-
  Indicate the order of workflow routing in your Process model by setting
  Sequence Flow between elements.
---

# Set Sequence Flow Elements to Indicate Workflow Routing

## Overview

Use a Sequence Flow element to indicate workflow routing between the connected elements. The direction in which the Sequence Flow points implies how [Request](../../../using-processmaker/requests/what-is-a-request.md) data is conveyed and utilized in the Process model. As a best practice indicate a consistent direction of Sequence Flow elements, either left to right or top to bottom, to make Process models easier to understand.

Sequence Flow elements have the following attributes in regards to specific Process model elements:

* From the context of a Process model element associated with a Sequence Flow, that Sequence Flow element can be "incoming" or "outgoing" for that element. Consider the following Process model to demonstrate their differences.  

  ![](../../../.gitbook/assets/sequence-flow-incoming-outgoing-process-modeler-processes.png)

  * **Incoming:** An incoming Sequence Flow element comes from its connecting element. In the Process model, the Sequence Flow element is incoming to the New Task element.  

    ![](../../../.gitbook/assets/sequence-flow-incoming-process-modeler-processes.png)

  * **Outgoing:** An outgoing Sequence Flow goes to the connecting element. In the Process model, the Sequence Flow element is outgoing from the New Task element.  

    ![](../../../.gitbook/assets/sequence-flow-outgoing-process-modeler-processes.png)

* Text annotations and Pool elements do not participate in Sequence Flow.
* Sequence Flow elements cannot connect workflow between Process model elements within different Pool elements. However, use [Message Flow](set-and-delete-message-flow-between-elements.md) elements to infer communication between elements in different Pool elements.
* A Start Event begins the flow of a Request for that Process. Therefore, a Start Event cannot have an incoming Sequence Flow.
* An End Event terminates the flow of a Request for that Process. Therefore, an End Event cannot have an outgoing Sequence Flow.
* Sequence Flows from Exclusive Gateway elements can be configured to specify under which condition a Request routes through that Sequence Flow. See [Configure a Sequence Flow from an Exclusive Gateway Element](the-quick-toolbar.md#configure-a-sequence-flow-from-an-exclusive-gateway-element).

## Set the Sequence Flow Element from One Connecting Element to Another

{% hint style="info" %}
### Looking for Information about Message Flow Elements?

See [Set and Delete Message Flow Between Elements](set-and-delete-message-flow-between-elements.md).

### Permissions Required to Do This Task

Your user account or group membership must have the following permissions to set Sequence Flow elements in the Process model:

* Processes: View Processes
* Processes: Edit Processes

See the [Process](../../../processmaker-administration/permission-descriptions-for-users-and-groups.md#processes) permissions or ask your ProcessMaker Administrator for assistance.
{% endhint %}

Follow these steps to set the Sequence Flow element from one connecting element to another:

1. ​[View your Processes](../../viewing-processes/view-the-list-of-processes/view-your-processes.md#view-all-processes). The **Processes** page displays.
2. Click the **Open Modeler** icon![](../../../.gitbook/assets/open-modeler-edit-icon-processes-page-processes.png)to edit the selected Process model. Process Modeler displays.
3. Select the Process model element from which you want to set the workflow routing. Available options display to the right of the selected element.  

   ![](../../../.gitbook/assets/sequence-flow-indicator-process-modeler-processes.png)

4. Click the **Sequence Flow** icon![](../../../.gitbook/assets/sequence-flow-icon-process-modeler-processes.png).
5. Click the Process model element in which to set the workflow routing. The Sequence Flow element connects between the two elements to indicate workflow routing.  

   ![](../../../.gitbook/assets/sequence-flow-connecting-elements-process-modeler-processes.png)

## Configure Sequence Flow Elements

{% hint style="info" %}
Your user account or group membership must have the following permissions to configure Sequence Flows in the Process model:

* Processes: View Processes
* Processes: Edit Processes

See the [Process](../../../processmaker-administration/permission-descriptions-for-users-and-groups.md#processes) permissions or ask your ProcessMaker Administrator for assistance.
{% endhint %}

### Configure a Sequence Flow Element

{% hint style="info" %}
This section discusses how to configure any Sequence Flow element except from an Exclusive Gateway element. See [Configure a Sequence Flow from an Exclusive Gateway Element](the-quick-toolbar.md#configure-a-sequence-flow-from-an-exclusive-gateway-element) if looking for information to configure those Sequence Flows.
{% endhint %}

#### Edit the Identifier Value

Process Modeler automatically assigns a unique value to each Process element added to a Process model. However, an element's identifier value can be changed if it is unique.

{% hint style="warning" %}
All identifier values for all elements in the Process model must be unique.
{% endhint %}

Follow these steps to edit the identifier value for a Sequence Flow element:

1. Select the Sequence Flow element from the Process model in which to edit its identifier value.
2. Expand the **Configuration** setting section if it is not presently expanded. The **Identifier** field displays. This is a required field.  

   ![](../../../.gitbook/assets/sequence-flow-configuration-identifier-name-process-modeler-processes.png)

3. In the **Identifier** field, edit the Sequence Flow element's identifier to a unique value from all elements in the Process model and then press **Enter**. The element's identifier value is changed.

#### Edit the Element Name

An element name is a human-readable reference for a Process element. Process Modeler automatically assigns the name of a Process element with its element type. However, an element's name can be changed.

Follow these steps to edit the name for a Sequence Flow element:

1. Select the Sequence Flow element from the Process model in which to edit its name.
2. Expand the **Configuration** setting section if it is not presently expanded. The **Name** field displays.  

   ![](../../../.gitbook/assets/sequence-flow-configuration-identifier-name-process-modeler-processes.png)

3. In the **Name** field, edit the selected element's name and then press **Enter**. The element's name is changed.

### Configure an Outgoing Sequence Flow from an Exclusive Gateway Element

Outgoing Sequence Flows from Exclusive Gateway elements have the following settings as do other Sequence Flow elements:

* [Identifier value](the-quick-toolbar.md#edit-the-identifier-value)
* [Element name](the-quick-toolbar.md#edit-the-element-name)

Outgoing Sequence Flows from Exclusive Gateway elements have an addition setting to indicate the condition under which a Request should follow that Sequence Flow to its connected element. Specify this condition using an expression syntax described in [Expression Syntax Components](the-quick-toolbar.md#expression-syntax-components). Each Sequence Flow can only have one expression, but by using logical operators multiple conditions can be specified in that expression.

Each outgoing Sequence Flow from an Exclusive Gateway element is evaluated using the following protocol:

* **The Sequence Flow does not have an expression:** If an outgoing Sequence Flow does not have an expression, there are no conditions to evaluate if that outgoing Sequence Flow should be followed.
* **The Sequence Flow has an expression:** The condition\(s\) in the Request is evaluated to determine if the condition\(s\) is met. If so, workflow can follow that outgoing Sequence Flow to its connected element. If not, then workflow cannot follow that Sequence Flow.

{% hint style="warning" %}
When specifying the condition\(s\) for outgoing Sequence Flows from an Exclusive Gateway element, ensure that the condition\(s\) for at least one outgoing Sequence Flow can evaluate validly to meet possible Request conditions. Otherwise, no outgoing Sequence Flows can be followed and the Request will stall at the Exclusive Gateway element.
{% endhint %}

Follow these steps to set the condition under which a Request follows an outgoing Sequence Flow element from an Exclusive Gateway element:

1. Select the outgoing Sequence Flow from the Exclusive Gateway element in which to set its workflow condition.
2. Expand the **Configuration** setting section if it is not presently expanded. The **Expression** field displays.
3. In the **Expression** field, enter or edit the expression for the selected Sequence Flow element using the syntax components described in [Expression Syntax Components](the-quick-toolbar.md#expression-syntax-components), and then press **Enter**.

#### Expression Syntax Components

Use the following expression syntax components to compose the expression that describes under which condition\(s\) a Request follows that outgoing Sequence Flow to its connected element.

**Literals**

| Component | Syntax | Example |
| :--- | :--- | :--- |
| string | `"hello world"` or `'hello world'` |  |
| number | `100` |  |
| array | `[`value1`,` value2`]` | `[1, 2]` |
| hash | `{foo: "`value`"}` | `{foo: "bar"}` |
| Boolean | `true` and `false` |  |

**Arithmetic Operations**

| Component | Syntax |
| :--- | :--- |
| addition | `+` |
| subtraction | `-` |
| multiplication | `*` |
| division | `/` |

**Logical Operators**

| Component | Syntax |
| :--- | :--- |
| not | `not` |
| and | `and` |
| or | `or` |

**Comparison Operators**

| Component | Syntax |
| :--- | :--- |
| equal to | `==` |
| not equal to | `!=` |
| less than | `<` |
| greater than | `>` |
| less than or equal to | `<=` |
| greater than or equal to | `>=` |

**String Operator**

| Component | Syntax |
| :--- | :--- |
| concatenate matches | `~` |

**Array Operators**

| Component | Syntax |
| :--- | :--- |
| contains | `in` |
| does not contain | `not in` |

**Ternary**

| Component | Syntax | Example |
| :--- | :--- | :--- |
| ternary | tested value `?` if true then value `:` else then value | `foo ? bar : baz` |

**Range**

| Component | Syntax | Example |
| :--- | :--- | :--- |
| range | `..` | `foo in 1..10` |

## Related Topics

{% page-ref page="process-modeling-element-descriptions.md" %}

{% page-ref page="../../viewing-processes/view-the-list-of-processes/view-your-processes.md" %}

{% page-ref page="../../viewing-processes/view-the-list-of-processes/create-a-process.md" %}

{% page-ref page="set-and-delete-message-flow-between-elements.md" %}

