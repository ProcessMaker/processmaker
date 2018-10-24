<template>
    <div id="userMenu">
        <avatar-image id="avatarMenu" class-container="d-flex m-1" size="40" class-image="m-1"
                      :input-data="information"></avatar-image>

        <b-popover target="avatarMenu" triggers="click blur" placement="bottomleft" container="userMenu" ref="popover"
                   @hidden="onHidden">
            <template slot="title">
                <div class="wrap-name">{{fullName}}</div>
            </template>
            <template>
                <template v-for="item in items">
                    <a class="dropdown-item item" :href="item.url">
                        <i :class="item.class"></i>
                        {{item.title}}
                    </a>
                </template>
            </template>
        </b-popover>
    </div>
</template>

<script>
    import Vue from 'vue';
    import AvatarImage from '../components/AvatarImage';

    Vue.component('avatar-image', AvatarImage);

    export default {
        data() {
            return {
                user: null,
                sourceImage: false,
                initials: null,
                fullName: null,
                popoverShow: false,
                information: []
            }
        },
        props: ['info', 'url', 'items'],
        watch: {
            info(val) {
                this.user = val;
            },
        },
        methods: {
            onClose() {
                this.popoverShow = false;
            },
            onHidden() {
                this.popoverShow = false;
            },
            formatData() {
                this.user = this.info;
                if (this.info.avatar) {
                    this.sourceImage = true;
                }
                this.initials = this.user.firstname[0] + this.user.lastname[0];
                this.fullName = this.user.firstname + ' ' + this.user.lastname;
                this.information = [{
                    src: this.user.avatar,
                    title: '',
                    initials: this.initials
                }];
                console.log(this.information);

            }
        },
        mounted() {
            this.formatData();
        }
    }
</script>

<style lang="scss" scoped>

    /deep/ .popover-header {
        background-color: #fff;
        font-size: 16px;
        font-weight: 600;
        color: #333333;
    }

    .wrap-name {
        font-size: 16px;
        font-weight: 600;
        width: 140px;
        text-overflow: ellipsis;
        white-space: nowrap;
        overflow: hidden;
    }

    .wrap-name:hover {
        white-space: initial;
        overflow: visible;
        cursor: pointer;
    }

    .item {
        font-size: 12px;
        padding: 5px;
        width: 160px;
    }

    .avatar-image {
        width: 40px;
        height: 40px;
        margin-left: -16px;
        margin-top: -7px;
    }

</style>
