<template>
    <div class="pmdesigner-toolbar">
        <ul class="side-menu">
            <li class="bpmn-item-menu">
                <img id="bpmn:StartEvent" name="StartEvent" src="images/start-event.svg" height="25"
                     draggable="true" @dragend="createElement($event)"></li>
            <li class="bpmn-item-menu">
                <img id="bpmn:Task" src="images/task.svg" height="20"
                     draggable="true" @dragend="createElement($event)"></li>
            <li class="bpmn-item-menu">
                <img id="bpmn:EndEvent" src="images/end-event.svg" height="25" draggable="true"
                     @dragend="createElement($event)"></li>
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
                <img id="bpmn:DataStoreReference" src="images/lane.svg" height="20" draggable="true"
                     @dragend="createElement($event)"></li>

            <li class="bpmn-item-menu">
                <img id="bpmn:DataStoreReference" src="images/lasso.svg" height="20" draggable="true"
                     @dragend="createElement($event)"></li>

            <li class="bpmn-item-menu">
                <img id="bpmn:DataStoreReference" src="images/text-annotation.svg" height="15" draggable="true"
                     @dragend="createElement($event)"></li>

            <li class="bpmn-item-menu">
                <img id="bpmn:DataStoreReference" src="images/validator.svg" height="20" draggable="true"
                     @dragend="createElement($event)"></li>

        </ul>
    </div>
</template>

<script>
    import actions from "../actions/"
    export default {
        methods: {
            createElement (value) {
                let action = actions.designer.drag.end(value)
                Dispatcher.$emit(action.type, action.payload)
            }
        }
    };
</script>
<style>
    .pmdesigner-toolbar {
        display: inline-block;
        position: fixed;
        background-color: white;
        border-right: 2px solid #e1dbd9;
        z-index: 2;
    }

    .side-menu {
        list-style-type: none;
        padding: 0;
        text-align: center;
    }

    .bpmn-item-menu {
        padding: 7px 13px
    }
</style>