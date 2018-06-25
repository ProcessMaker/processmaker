<template>
    <div v-show="visible" class="designer-container-crown" v-bind:style="{ top: y+'px', left:x+'px' }">
        <div class="d-flex flex-row">
            <div class="item-crown">
                <img id="bpmn:Task" src="../img/task.svg" height="20"
                     @click="createAction($event)">
            </div>
            <div class="item-crown">
                <img id="bpmn:ExclusiveGateway" src="../img/exclusive-gateway.svg" height="27"
                     @click="createAction($event)">
            </div>
            <div class="item-crown">
                <img id="bpmn:IntermediateEmailEvent" name="IntermediateEmailEvent"
                     src="../img/intermediate-email-event.svg" height="27" @click="createAction($event)">
            </div>
        </div>
        <div class="d-flex flex-row">
            <div class="item-crown">
                <img id="bpmn:EndEvent" name="EndEvent"
                     src="../img/end-event.svg" height="27" @click="createAction($event)">
            </div>
            <div class="item-crown">
                <img id="bpmn:EndEvent" src="../img/corona-flow.png" height="28"
                     @click="createFlow($event)">
            </div>
            <div class="item-crown" @click="createFlow($event)">
                <i id="cog" class="fas fa-cog icon-crown" draggable="true"></i>
            </div>
        </div>
        <div class="d-flex flex-row">
            <div class="item-crown">
                <i id="trash" class="fas fa-trash-alt icon-crown" draggable="true"></i>
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
                dx: 3,
                dy: 3,
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
             * Method for remove the Selected Shape
             */
            remove (ev){
                let action = actions.designer.shape.remove()
                EventBus.$emit(action.type, action.payload)
            },
            /**
             * Method for show the crown
             */
            show(conf){
                this.x = conf.x + document.getElementById('svgCanvas').getBoundingClientRect().left + this.dx
                this.y = conf.y + document.getElementById('svgCanvas').getBoundingClientRect().top - this.dy
                this.visible = true
            },
            /**
             * Method for hide the crown
             */
            hide(){
                this.visible = false
            },
            createFlow(ev){
                let action = actions.designer.flow.create()
                EventBus.$emit(action.type, action.payload)
            }
        },
        mounted() {

        }
    }
</script>

<style lang="scss" scoped>
    .designer-container-crown {
        position: fixed;
        z-index: 10;
        display: flex;
        flex-direction: column;
    }

    .item-crown {
        text-align: center;
        min-width: 33px;
        padding: 2px;

        img:before {
            content: '';
            display: inline-block;
            height: 100%;
            vertical-align: middle;
            margin-right: -0.25em; /* Adjusts for spacing */
        }
    }

    .icon-crown {
        padding: 3px;
        font-size: 22px;
    }

    .delete-crown {
        padding-right: 4px;
        padding-left: 4px;
    }

    .item-crown:hover {
        background-color: #eff2ed;
    }
</style>
