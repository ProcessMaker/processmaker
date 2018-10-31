<template>
    <span :class="classContainer">
        <template v-for="(value, key) in options">
            <template v-if="value.src" class="align-center">
                <a :href="value.id">
                <b-img center :src="value.src" :rounded="round" :width="sizeImage" :height="sizeImage"
                       blank-color="bg-secondary" :class="image" :title="value.tooltip"/>
                </a>
            </template>
            <template v-else>
                <a :href="value.id">
                <button class="rounded-circle bg-warning border-0" :style="styleButton" :title="value.tooltip"
                        :href="value.id">
                    <span class="text-white text-center text-uppercase"> {{value.initials}}</span>
                </button>
                </a>
            </template>
            <span v-if="displayName" class="text-center text-capitalize m-1"> {{value.name}}</span>
        </template>

    </span>
</template>

<script>
    export default {
        props: ['size', 'rounded', 'classContainer', 'classImage', 'inputData', 'displayName'],
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
                this.styleButton = 'width: ' + size + 'px; height: ' + size + 'px; font-size:' + size / 2.5 +
                    'px; margin-right:5px;';
            },
            formatValue(value)  {
                return {
                    id: value.id ? 'profile/' + value.id : '#',
                    src: value.src ? value.src : value.avatar ? value.avatar : '',
                    tooltip: value.tooltip ? value.tooltip : value.fullname ? this.displayName ? value.title : value.fullname : '',
                    name: value.name ? value.name : value.fullname ? value.fullname : '',
                    initials: value.initials ? value.initials : (value.firstname && value.lastname ) ? (value.firstname.match(/./u)[0] + value.lastname.match(/./u)[0]) : ''
                }
            },
            formatInputData(data) {
                let options = [];
                if (data && Array.isArray(data)) {
                    let that = this;
                    data.forEach(value => {
                        options.push(that.formatValue(value));
                    });
                } else {
                    options.push(this.formatValue(data));
                }
                this.options = options;
            }
        },
        mounted() {
            this.default();
        }
    };
</script>
