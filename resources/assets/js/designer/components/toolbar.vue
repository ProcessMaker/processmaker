<template>
    <div class="pmdesigner-toolbar">
        <ul class="nav flex-column flex-md-row mt--1 text-light">
            <li class="bpmn-item-menu">
                <img id="bpmn:task"
                     type="task"
                     src="../img/task.svg" height="18"
                     draggable="true" @dragend="createElement($event)"></li>
            <li class="bpmn-item-menu">
                <img id="bpmn:callActivity"
                     type="callActivity"
                     src="../img/sub-process.svg" height="18"
                     draggable="true" @dragend="createElement($event)"></li>
            <li class="bpmn-item-menu">
                <img id="bpmn:exclusiveGateway"
                     type="exclusiveGateway"
                     src="../img/exclusive-gateway.svg" height="25" draggable="true"
                     @dragend="createElement($event)"></li>
            <li class="bpmn-item-menu">
                <img id="bpmn:parallelGateway"
                     type="parallelGateway"
                     src="../img/parallel-gateway.svg" height="25" draggable="true"
                     @dragend="createElement($event)"></li>
            <li class="bpmn-item-menu">
                <img id="bpmn:inclusiveGateway"
                     type="inclusiveGateway"
                     src="../img/inclusive-gateway.svg" height="25" draggable="true"
                     @dragend="createElement($event)"></li>
            <li class="bpmn-item-menu">
                <img id="bpmn:startEvent"
                     type="startEvent"
                     src="../img/start-event.svg" height="25"
                     draggable="true" @dragend="createElement($event)"></li>
            <li class="bpmn-item-menu">
                <img id="bpmn:messageEventDefinition"
                     event="messageEventDefinition"
                     type="intermediateThrowEvent"
                     src="../img/intermediate-email-event.svg" height="25"
                     draggable="true" @dragend="createElement($event)"></li>
            <li class="bpmn-item-menu">
                <img id="bpmn:timerEventDefinition"
                     event="timerEventDefinition"
                     type="intermediateThrowEvent"
                     src="../img/intermediate-time-event.svg" height="25"
                     draggable="true" @dragend="createElement($event)"></li>
            <li class="bpmn-item-menu">
                <img id="bpmn:endEvent"
                     type="endEvent"
                     src="../img/end-event.svg" height="25" draggable="true"
                     @dragend="createElement($event)"></li>
            <li class="bpmn-item-menu">
                <img id="bpmn:endEmailEvent"
                     type="endEvent"
                     event="messageEventDefinition"
                     src="../img/end-email-event.svg" height="25" draggable="true"
                     @dragend="createElement($event)"></li>
            <li class="bpmn-item-menu">
                <img id="bpmn:DataObject"
                     type="DataObject"
                     src="../img/data-object.svg" height="23" draggable="true"
                     @dragend="createElement($event)"></li>
            <li class="bpmn-item-menu">
                <img id="bpmn:DataStore"
                     type="DataStore"
                     src="../img/data-store.svg" height="23" draggable="true"
                     @dragend="createElement($event)"></li>
            <li class="bpmn-item-menu">
                <img id="bpmn:BlackBoxPool"
                     type="BlackBoxPool"
                     src="../img/black-box-pool.svg" height="18" draggable="true"
                     @dragend="createElement($event)"></li>
            <li class="bpmn-item-menu">
                <img id="bpmn:participant"
                     type="participant"
                     src="../img/pool.svg" height="18" draggable="true"
                     @dragend="createElement($event)"></li>
            <li class="bpmn-item-menu">
                <img id="bpmn:Lane"
                     type="Lane"
                     src="../img/lane.svg" height="18"
                     @dragend="createElement($event)"></li>
            <li class="bpmn-item-menu">
                <img id="bpmn:Group"
                     type="Group"
                     src="../img/group.svg" height="18" draggable="true"
                     @dragend="createElement($event)"></li>
            <li class="bpmn-item-menu">
                <img id="bpmn:TextAnnotation"
                     type="TextAnnotation"
                     src="../img/text-annotation.svg" height="15" draggable="true"
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
