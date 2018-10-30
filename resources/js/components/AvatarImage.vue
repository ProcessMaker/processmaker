<template>
    <span :class="classContainer">
        <template v-for="(value, key) in options">
            <template v-if="value.src" class="align-center">
                <b-img center :src="value.src" :rounded="round" :width="sizeImage" :height="sizeImage"
                       blank-color="bg-secondary" :class="image" :title="value.title"/>
            </template>
            <template v-else>
                <button class="rounded-circle bg-warning border-0" :style="styleButton" :title="value.title">
                    <span class="text-white text-center text-uppercase"> {{value.initials}}</span>
                </button>
            </template>
            <span v-if="value.name" class="text-center text-capitalize m-1">  {{value.name}}</span>

        </template>

    </span>
</template>

<script>
    export default {
        props: ['size', 'rounded', 'classContainer', 'classImage', 'inputData'],
        data() {
            return {
                round: 'circle',
                image: 'm-1',
                styleButton: 'width: 25px; height: 25px;',
                options: []
            }
        },
        watch: {
            inputData(value) {
                this.formatInputData(value)
            },
            size(value) {
                this.formatSize(value);
            },
            rounded(value) {
                this.formatRounded(value);
            },
            classImage(value) {
                this.formatClassImage(value);
            }
        },
        methods: {
            default() {
                this.formatRounded(this.rounded);
                this.formatClassImage(this.classImage);
                this.formatInputData(this.inputData);
                this.formatSize(this.size);
            },
            formatClassImage(value) {
                this.image = value ? value : 'm-1';
            },
            formatRounded(value) {
                this.round = value ? value : 'circle';
            },
            formatSize(size) {
                this.sizeImage = size ? size : '25';
                this.formatSizeButton(this.sizeImage);
            },
            formatSizeButton(size) {
                this.styleButton = 'width: ' + size + 'px; height: ' + size + 'px; font-size:' + size / 2.5 + 'px';
            },
            formatInputData(data) {
                let options = [];
                if (data && Array.isArray(data)) {
                    data.forEach(function (value) {
                        options.push({
                            src: value.src ? value.src : '',
                            title: value.title ? value.title : '',
                            name: value.name ? value.name : '',
                            initials: value.initials ? value.initials : ''
                        })
                    });
                }
                this.options = options;
            }
        },
        mounted() {
            this.default();
        }

    }
</script>
