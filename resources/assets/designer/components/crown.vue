<template>
    <div class="designer-container-crown">
        <div class="d-flex flex-row">
            <div class="item-crown">
                <img id="bpmn:Task" src="images/task.svg" height="18"
                     @click="createAction($event)">
            </div>
            <div class="item-crown">
                <img id="bpmn:ExclusiveGateway" src="images/exclusive-gateway.svg" height="25"
                     @click="createAction($event)">
            </div>
        </div>
        <div class="d-flex flex-row">
            <div class="item-crown">
                <img id="bpmn:IntermediateEmailEvent" name="IntermediateEmailEvent"
                     src="images/intermediate-email-event.svg" height="25" @click="createAction($event)">
            </div>
            <div class="item-crown">
                <img id="bpmn:EndEvent" src="images/corona-flow.png" height="25"
                     @click="createAction($event)">
            </div>
        </div>
        <div class="d-flex flex-row">
            <div class="item-crown">
                <img id="bpmn:Task" class="delete-crown" src="images/corona-delete.png" height="20"
                     draggable="true" @click="remove($event)">
            </div>
        </div>
    </div>
</template>

<script>
    import actions from "../actions"
    export default {
        data() {
            return {
                top: null,
                left: null
            }
        },
        created() {

        },
        methods: {
            remove (ev){
            }
        },
        mounted() {
            $(this.$el).css("top", this.top)
            $(this.$el).css("left", this.left)
        }
    }
</script>

<style>
    .designer-container-crown {
        position: fixed;
        z-index: 10;
    }

    .item-crown {
        display: table-cell;
        padding: 3px;
        text-align: center;
    }

    .delete-crown {
        padding-right: 4px;
        padding-left: 4px;
    }

    .item-crown:hover {
        background-color: #e7e0e0;
    }
</style>