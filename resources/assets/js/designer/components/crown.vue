<template>
    <div v-show="visible" class="designer-container-crown" v-bind:style="{ top: y+'px', left:x+'px' }">
        <div class="d-flex flex-row">
            <div class="item-crown">
                <img id="bpmn:Task" src="../img/task.svg" height="18"
                     @click="createAction($event)">
            </div>
            <div class="item-crown">
                <img id="bpmn:ExclusiveGateway" src="../img/exclusive-gateway.svg" height="25"
                     @click="createAction($event)">
            </div>
        </div>
        <div class="d-flex flex-row">
            <div class="item-crown">
                <img id="bpmn:IntermediateEmailEvent" name="IntermediateEmailEvent"
                     src="../img/intermediate-email-event.svg" height="25" @click="createAction($event)">
            </div>
            <div class="item-crown">
                <img id="bpmn:EndEvent" src="../img/corona-flow.png" height="25"
                     @click="createAction($event)">
            </div>
        </div>
        <div class="d-flex flex-row">
            <div class="item-crown">
                <img id="bpmn:Task" class="delete-crown" src="../img/corona-delete.png" height="20"
                     draggable="true" @click="remove($event)">
            </div>
        </div>
    </div>
</template>

<script>
    import actions from "../actions"
    import EventBus from "../lib/event-bus"
    export default {
        data() {
            return {
                x: null,
                y: null,
                visible: false
            }
        },
        created() {
            EventBus.$on(actions.designer.crown.show().type, (value) => this.show(value))
            EventBus.$on(actions.designer.crown.hide().type, (value) => this.hide(value))
        },
        methods: {
            /**
             * Method for remove Selected Shape
             */
            remove (ev){
                let action = actions.designer.shape.remove()
                EventBus.$emit(action.type, action.payload)
            },
            /**
             * Method for show crown
             */
            show(conf){
                this.x = conf.x
                this.y = conf.y
                this.visible = true
            },
            /**
             * Method for hide crown
             */
            hide(){
                this.visible = false
            }
        },
        mounted() {

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
