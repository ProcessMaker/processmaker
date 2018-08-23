import Vue from 'vue'
import CustomizeColor from './components/CustomizeColor'


new Vue({
    el: '#uicustomize',
    components: {
        CustomizeColor,
    },
    data: {
        file1: null,
        file2: null,
    },
    methods: {
        showPrimaryModal() {
            this.$refs.myModalRef.show()
        },
        hidePrimaryModal() {
            this.$refs.myModalRef.hide()
        },
        showSecondaryModal() {
            this.$refs.myModalRef.show()
        },
        hideSecondaryModal() {
            this.$refs.myModalRef.hide()
        }
    }
})