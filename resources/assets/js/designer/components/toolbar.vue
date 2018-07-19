<template>
    <div class="pmdesigner-toolbar">
        <ul class="nav flex-column flex-md-row mt--1 text-light">
            <li class="bpmn-item-menu">
                <img id="bpmn:task" src="../img/task.svg" height="18"
                     draggable="true" @dragend="createElement($event)"></li>
            <li class="bpmn-item-menu">
                <img id="bpmn:callActivity" src="../img/sub-process.svg" height="18"
                     draggable="true" @dragend="createElement($event)"></li>
            <li class="bpmn-item-menu">
                <img id="bpmn:exclusiveGateway" src="../img/exclusive-gateway.svg" height="25" draggable="true"
                     @dragend="createElement($event)"></li>
            <li class="bpmn-item-menu">
                <img id="bpmn:parallelGateway" src="../img/parallel-gateway.svg" height="25" draggable="true"
                     @dragend="createElement($event)"></li>
            <li class="bpmn-item-menu">
                <img id="bpmn:inclusiveGateway" src="../img/inclusive-gateway.svg" height="25" draggable="true"
                     @dragend="createElement($event)"></li>
            <li class="bpmn-item-menu">
                <img id="bpmn:startEvent" src="../img/start-event.svg" height="25"
                     draggable="true" @dragend="createElement($event)"></li>
            <li class="bpmn-item-menu">
                <img id="bpmn:messageEventDefinition"
                     src="../img/intermediate-email-event.svg" height="25"
                     draggable="true" @dragend="createElement($event)"></li>
            <li class="bpmn-item-menu">
                <img id="bpmn:timerEventDefinition" name="IntermediateTimerEvent"
                     src="../img/intermediate-time-event.svg" height="25"
                     draggable="true" @dragend="createElement($event)"></li>
            <li class="bpmn-item-menu">
                <img id="bpmn:endEvent" src="../img/end-event.svg" height="25" draggable="true"
                     @dragend="createElement($event)"></li>
            <li class="bpmn-item-menu">
                <img id="bpmn:endEmailEvent" src="../img/end-email-event.svg" height="25" draggable="true"
                     @dragend="createElement($event)"></li>
            <li class="bpmn-item-menu">
                <img id="bpmn:DataObject" src="../img/data-object.svg" height="23" draggable="true"
                     @dragend="createElement($event)"></li>
            <li class="bpmn-item-menu">
                <img id="bpmn:DataStore" src="../img/data-store.svg" height="23" draggable="true"
                     @dragend="createElement($event)"></li>
            <li class="bpmn-item-menu">
                <img id="bpmn:BlackBoxPool" src="../img/black-box-pool.svg" height="18" draggable="true"
                     @dragend="createElement($event)"></li>
            <li class="bpmn-item-menu">
                <img id="bpmn:Participant" src="../img/pool.svg" height="18" draggable="true"
                     @dragend="createElement($event)"></li>
            <li class="bpmn-item-menu">
                <img id="bpmn:Lane" src="../img/lane.svg" height="18"
                     @dragend="createElement($event)"></li>
            <li class="bpmn-item-menu">
                <img id="bpmn:Group" src="../img/group.svg" height="18" draggable="true"
                     @dragend="createElement($event)"></li>
            <li class="bpmn-item-menu">
                <img id="bpmn:TextAnnotation" src="../img/text-annotation.svg" height="15" draggable="true"
                     @dragend="createElement($event)"></li>
            <li class="bpmn-item-menu">
                <img id="bpmn:Lasso" src="../img/lasso.svg" height="20" draggable="true"
                     @dragend="createElement($event)"></li>
            <li class="bpmn-item-menu">
                <img id="bpmn:Validator" src="../img/validator.svg" height="20" draggable="true"
                     @dragend="createElement($event)"></li>
        </ul>
    </div>
</template>

<script>
    import EventBus from "../lib/event-bus"
    import actions from "../actions/"
    export default {
        methods: {
            createElement (value) {
                let action = actions.designer.drag.toolbar.end(value)
                EventBus.$emit(action.type, action.payload)
            }
        }
    };
</script>
<style lang="scss" scoped>
    .pmdesigner-toolbar {
        background-color: white;
        border-right: 2px solid #e1dbd9;

        .bpmn-item-menu {
            padding: 7px 7px
        }

        .bpmn-item-menu:hover {
            background-color: #eff2ed;
        }
    }
</style>
