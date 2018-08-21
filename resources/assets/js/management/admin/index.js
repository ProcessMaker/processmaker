import Vue from 'vue'
import CustomizeColor from './components/CustomizeColor'


new Vue ({
    el: '#uicustomize',
    components: { 
        CustomizeColor,
    },
    methods: {
        showPickColor() {
            this.$refs.show();
        }
    }
})