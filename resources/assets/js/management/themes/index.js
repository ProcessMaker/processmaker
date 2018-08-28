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
        colorOne: "#3397e1",
        colorTwo: "#788793"
    },
    methods: {
        showPrimaryModal() {
            this.$refs.primaryModal.show()
        },
        hidePrimaryModal() {
            this.colorOne = "#3397e1"
            this.$refs.primaryModal.hide()
        },
        showSecondaryModal() {
            this.$refs.secondaryModal.show()
        },
        hideSecondaryModal() {
            this.colorTwo = "#788793"
            this.$refs.secondaryModal.hide()
        },
        changeColor(val) {
            this.colors = val
        },
        onImgUpload1(input) {
            let file = event.target.files[0];
            let reader = new FileReader();
            reader.onload = function (input) {
                $('#file1Img')
                    .attr('src', reader.result);
            };

            reader.readAsDataURL(file);
        },
        onImgUpload2(input) {
            let file = event.target.files[0];
            let reader = new FileReader();
            reader.onload = function (input) {
                $('#file2Img')
                    .attr('src', reader.result);
            };

            reader.readAsDataURL(file);
        }

    }
})