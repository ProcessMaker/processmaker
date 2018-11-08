// Our initial node types to register with our modeler
import {
    association,
    endEvent,
    exclusiveGateway,
    inclusiveGateway,
    parallelGateway,
    sequenceFlow,
    startEvent,
    task,
    textAnnotation
} from '@processmaker/modeler'

let nodeTypes = [
    startEvent,
    endEvent,
    task,
    exclusiveGateway,
    //inclusiveGateway,
    //parallelGateway,
    sequenceFlow,
    textAnnotation,
    association,
]

ProcessMaker.EventBus.$on('modeler-init', function(modeler) {
    for(var node of nodeTypes) {
        modeler.registerNode(node);
    }
});
