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
            this.$refs.primaryModal.show()
        },
        hidePrimaryModal() {
            this.$refs.primaryModal.hide()
        },
        showSecondaryModal() {
            this.$refs.secondaryModal.show()
        },
        hideSecondaryModal() {
            this.$refs.secondaryModal.hide()
        }
    }
})