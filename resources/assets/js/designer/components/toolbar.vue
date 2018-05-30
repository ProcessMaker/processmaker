<template>
    <div class="pmdesigner-toolbar">
        <ul class="side-menu">
            <li class="bpmn-item-menu">
                <img id="bpmn:Task" src="images/task.svg" height="20"
                     draggable="true" @dragend="createElement($event)"></li>
            <li class="bpmn-item-menu">
                <img id="bpmn:Subprocess" src="images/sub-process.svg" height="20"
                     draggable="true" @dragend="createElement($event)"></li>
            <li class="bpmn-item-menu">
                <img id="bpmn:ExclusiveGateway" src="images/exclusive-gateway.svg" height="25" draggable="true"
                     @dragend="createElement($event)"></li>
            <li class="bpmn-item-menu">
                <img id="bpmn:ParallelGateway" src="images/parallel-gateway.svg" height="25" draggable="true"
                     @dragend="createElement($event)"></li>
            <li class="bpmn-item-menu">
                <img id="bpmn:InclusiveGateway" src="images/inclusive-gateway.svg" height="25" draggable="true"
                     @dragend="createElement($event)"></li>
            <li class="bpmn-item-menu">
                <img id="bpmn:StartEvent" name="StartEvent" src="images/start-event.svg" height="25"
                     draggable="true" @dragend="createElement($event)"></li>
            <li class="bpmn-item-menu">
                <img id="bpmn:IntermediateEmailEvent" name="IntermediateEmailEvent"
                     src="images/intermediate-email-event.svg" height="25"
                     draggable="true" @dragend="createElement($event)"></li>
            <li class="bpmn-item-menu">
                <img id="bpmn:IntermediateTimeEvent" name="IntermediateTimeEvent"
                     src="images/intermediate-time-event.svg" height="25"
                     draggable="true" @dragend="createElement($event)"></li>
            <li class="bpmn-item-menu">
                <img id="bpmn:EndEvent" src="images/end-event.svg" height="25" draggable="true"
                     @dragend="createElement($event)"></li>
            <li class="bpmn-item-menu">
                <img id="bpmn:EndEmailEvent" src="images/end-email-event.svg" height="25" draggable="true"
                     @dragend="createElement($event)"></li>
            <li class="bpmn-item-menu">
                <img id="bpmn:DataObjectReference" src="images/data-object.svg" height="20" draggable="true"
                     @dragend="createElement($event)"></li>
            <li class="bpmn-item-menu">
                <img id="bpmn:DataStoreReference" src="images/data-store.svg" height="20" draggable="true"
                     @dragend="createElement($event)"></li>
            <li class="bpmn-item-menu">
                <img id="bpmn:DataStoreReference" src="images/black-box-pool.svg" height="20" draggable="true"
                     @dragend="createElement($event)"></li>
            <li class="bpmn-item-menu">
                <img id="bpmn:DataStoreReference" src="images/pool.svg" height="20" draggable="true"
                     @dragend="createElement($event)"></li>
            <li class="bpmn-item-menu">
                <img id="bpmn:Lane" src="images/lane.svg" height="20" draggable="true"
                     @dragend="createElement($event)"></li>
            <li class="bpmn-item-menu">
                <img id="bpmn:Group" src="images/group.svg" height="20" draggable="true"
                     @dragend="createElement($event)"></li>
            <li class="bpmn-item-menu">
                <img id="bpmn:TextAnnotation" src="images/text-annotation.svg" height="15" draggable="true"
                     @dragend="createElement($event)"></li>
            <li class="bpmn-item-menu">
                <img id="bpmn:Lasso" src="images/lasso.svg" height="20" draggable="true"
                     @dragend="createElement($event)"></li>
            <li class="bpmn-item-menu">
                <img id="bpmn:Validator" src="images/validator.svg" height="20" draggable="true"
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
                console.log("Emitting");
            }
        }
    };
</script>
<style lang="scss" scoped>
.pmdesigner-toolbar {
    background-color: white;
    border-right: 2px solid #e1dbd9;
    min-height: 100%;
    max-height: 100%;
    overflow: auto;

    .side-menu {
        list-style-type: none;
        padding: 0;
        text-align: center;
        width: 56px;


        .bpmn-item-menu {
            padding: 7px 13px
        }
    }
}
</style>