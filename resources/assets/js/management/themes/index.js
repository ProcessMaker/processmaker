import Vue from 'vue'
import CustomizeColor from './components/CustomizeColor'


new Vue({
    el: '#uicustomize',
    components: {
        CustomizeColor,
    },
    data: {
        file: {}
    },
    methods: {
        keepPickColor() {
            // this.$refs.show();
            alert('pickcolor')
        },
    }
})