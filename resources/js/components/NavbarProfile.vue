<template>
    <div id="userMenu">
        <div class="my-3">
            <b-btn id="avatarMenu" :disabled="popoverShow" class="avatar-circle">
                <template v-if="sourceImage">
                    <img class="avatar-image avatar-circle" :src="user.avatar">
                </template>
                <template v-else>
                    <span class="avatar-initials"> {{ initials }} </span>
                </template>
            </b-btn>
        </div>

        <b-popover target="avatarMenu"
                   triggers="click blur"
                   placement="bottomleft"
                   container="userMenu"
                   ref="popover"
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
    export default {
        data() {
            return {
                user: null,
                sourceImage: false,
                initials: null,
                fullName: null,
                popoverShow: false,
            }
        },
        props: ['info', 'url', 'items'],
        watch: {
            info(val) {
                console.log(val);
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

    .avatar-circle {
        width: 40px;
        height: 40px;
        background-color: rgb(251, 181, 4);
        text-align: center;
        border-radius: 50%;
        -webkit-border-radius: 50%;
        -moz-border-radius: 50%;
        margin-left: 10px;
        border: none;
    }

    .avatar-initials {
        position: relative;
        font-size: 20px;
        line-height: 18px;
        color: #fff;
        margin: -12px;
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