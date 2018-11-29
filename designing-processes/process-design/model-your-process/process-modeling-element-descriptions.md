---
description: These are brief descriptions about each process modeling element.
---

# Process Modeling Element Descriptions

The following are brief descriptions about each process modeling element. For more information, see the [BPMN specification](https://www.omg.org/spec/BPMN/2.0/About-BPMN/).

## Events

An Event represents a "milestone" in the process model.

### Start Event

A Start Event indicates where a modeled process starts. A Start Event begins the flow of a Request for that process. Therefore, a Sequence Flow cannot connect into a Start Event. A process can have multiple Start Events.

Below is a Start Event element in Process Modeler.

![Start Event](../../../.gitbook/assets/start-event-element-process-modeler-processes.png)

### End Event

An End Event indicates where a modeled process normally ends when abnormal events do not terminate a Request for that process \(such as a canceled Request\). An End Event terminates the flow of of a Request for that process. Therefore, a Sequence Flow cannot exit from an End Event. A process can have multiple End Events.

Below is an End Event element in Process Modeler.

![End Event](../../../.gitbook/assets/end-event-element-process-modeler-processes.png)

## Tasks

A task represents an activity to be performed either by a person or a script.

### User Task

A User Task is an activity to be performed by a person. The person assigned the perform that task might be assigned or determined by the Request routing. In Process Modeler, a User Task is labeled as "Task."

Below is a User Task element in Process Modeler.

![User Task](../../../.gitbook/assets/task-element-process-modeler-processes.png)

### Script Task





## Exclusive Gateway



## Sequence Flow



