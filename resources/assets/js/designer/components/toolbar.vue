<template>
    <div class="pmdesigner-toolbar">
        <ul class="nav flex-column flex-md-row mt--1 text-light">
            <li class="bpmn-item-menu">
                <img id="bpmn:Task" src="../img/task.svg" height="18"
                     draggable="true" @dragend="createElement($event)"></li>
            <li class="bpmn-item-menu">
                <img id="bpmn:CallActivity" src="../img/sub-process.svg" height="18"
                     draggable="true" @dragend="createElement($event)"></li>
            <li class="bpmn-item-menu">
                <img id="bpmn:ExclusiveGateway" src="../img/exclusive-gateway.svg" height="25" draggable="true"
                     @dragend="createElement($event)"></li>
            <li class="bpmn-item-menu">
                <img id="bpmn:ParallelGateway" src="../img/parallel-gateway.svg" height="25" draggable="true"
                     @dragend="createElement($event)"></li>
            <li class="bpmn-item-menu">
                <img id="bpmn:InclusiveGateway" src="../img/inclusive-gateway.svg" height="25" draggable="true"
                     @dragend="createElement($event)"></li>
            <li class="bpmn-item-menu">
                <img id="bpmn:StartEvent" name="StartEvent" src="../img/start-event.svg" height="25"
                     draggable="true" @dragend="createElement($event)"></li>
            <li class="bpmn-item-menu">
                <img id="bpmn:IntermediateThrowEvent" name="IntermediateEmailEvent"
                     src="../img/intermediate-email-event.svg" height="25"
                     draggable="true" @dragend="createElement($event)"></li>
            <li class="bpmn-item-menu">
                <img id="bpmn:IntermediateTimerEvent" name="IntermediateTimerEvent"
                     src="../img/intermediate-time-event.svg" height="25"
                     draggable="true" @dragend="createElement($event)"></li>
            <li class="bpmn-item-menu">
                <img id="bpmn:EndEvent" src="../img/end-event.svg" height="25" draggable="true"
                     @dragend="createElement($event)"></li>
            <li class="bpmn-item-menu">
                <img id="bpmn:EndEmailEvent" src="../img/end-email-event.svg" height="25" draggable="true"
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
