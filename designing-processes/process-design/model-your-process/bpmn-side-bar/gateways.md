---
description: >-
  Use exclusive gateways to select one path between two or more tasks within a
  process workflow.
---

# Use an Exclusive Gateway Event

See the following sections for information how to use this event in a process model:

* [Adding an exclusive gateway element](gateways.md#adding-an-exclusive-gateway-element)
* [Set an exclusive gateway identifier](gateways.md#set-an-exclusive-gateway-identifier)
* [Set an exclusive gateway name](gateways.md#set-an-exclusive-gateway-name)
* [Defining an exclusive gateway direction](gateways.md#defining-an-exclusive-gateway-direction)

## Adding an Exclusive Gateway Event

Follow these steps to add an exclusive gateway to the process modeler:

1. [View your processes](https://processmaker.gitbook.io/processmaker-4-community/-LPblkrcFWowWJ6HZdhC/~/drafts/-LRhVZm0ddxDcGGdN5ZN/primary/designing-processes/viewing-processes/view-the-list-of-processes/view-your-processes#view-all-processes). The **Processes** page displays.
2. Click a process to open it. The process modeler displays.
3. Locate the exclusive gateway element in the BPMN left side bar. Drag and drop the element to the process modeler. The exclusive gateway has been added to the process modeler.

\[Image\]

## Set an Exclusive Gateway Identifier

Follow these steps to set a gateway identifier:

1. [Add an exclusive gateway](gateways.md#adding-an-exclusive-gateway-element) to the process modeler.
2. Click the new exclusive gateway.
3. The default exclusive gateway identifier displays in the context right side bar.
4. In the **Identifier** field, enter an exclusive gateway ID identifier. Process Modeler automatically saves the new value.

{% hint style="info" %}
The ID identifier should be unique across all elements in the diagram.
{% endhint %}

\[Image\]

## Set an Exclusive Gateway Name

Follow these steps to set an exclusive gateway name:

1. [Add an exclusive gateway](gateways.md#adding-an-exclusive-gateway-element) to the process modeler.
2. Click the new exclusive gateway.
3. The default exclusive gateway name displays in the context right side bar.
4. In the **Name** field, enter an exclusive gateway name. Process Modeler automatically saves the new value.

\[Image\]

## Defining an Exclusive Gateway Direction

Follow these steps to define an exclusive gateway direction:

1. [Add an exclusive gateway](gateways.md#adding-an-exclusive-gateway-element) to the process modeler.
2. Click the new exclusive gateway.
3. Two exclusive gateway directions displays in the context right side bar.
4. In the **Direction** dropdown, select the direction between: 
   * **Diverging:** A Diverging Gateway should have one incoming connection and two or more outgoing paths.
   * **Converging:** A Converging Gateway allows the synchronization of multiple branches. It should have two or more incoming connections and one outgoing connection path.

Process Modeler automatically saves the exclusive gateway direction.

\[Image\]

{% hint style="info" %}
Take into consideration that an exclusive gateway cannot ****be diverging and converging at the same time in the same process design.
{% endhint %}

