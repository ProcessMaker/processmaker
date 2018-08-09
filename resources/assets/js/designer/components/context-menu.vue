<template>
    <div id="pm-context-menu">
        <easy-cm :list="cmList"
                 @ecmcb="test"
                 :underline="true"
                 :arrow="true">
        </easy-cm>
    </div>
</template>
<script>
    import EasyCm from 'vue-easycm'
    import actions from '../actions'
    import EventBus from '../lib/event-bus'
    import _ from "lodash"
    Vue.use(EasyCm)

    export default {
        data () {
            return {
                // 配置数组
                cmList: []
            }
        },
        created(){
            EventBus.$on(actions.designer.contextMenu.show().type, (value) => this.show(value))
        },
        methods: {
            // 回调函数
            test(indexList){
                if (_.isFunction(this.cmList[indexList].handler)) {
                    this.cmList[indexList].handler()
                }
            },
            show(data){
                debugger
                this.cmList = data.options
                this.$easycm(data.event, this.$root)
            }
        }
    }
</script>
<style>
    .jonas {
        height: 100px;
        width: 200px;
        background-color: aqua;
    }
</style>