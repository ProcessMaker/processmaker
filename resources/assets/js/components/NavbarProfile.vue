<template>
    <div id="userMenu">
        <div class="my-3">
            <b-btn id="avatarMenu" :disabled="popoverShow" class="avatar-circle btn-warning">
                <template v-if="sourceImage">
                    <img class="avatar-image" :src="user.avatar">
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
                <template v-if="sourceImage">
                    <img class="avatar-image-small" :src="user.avatar">
                    <div class="wrap-name wrap-image">{{fullName}}</div>
                </template>
                <template v-else>
                    <div class="avatar-circle-small">
                        <span class="avatar-initials-small text-uppercase">
                        {{initials}}
                        </span>
                        <div class="wrap-name">{{fullName}}</div>
                    </div>
                </template>

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

<style scoped>

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
        margin-left: -13px;
    }

    .avatar-circle-small {
        width: 40px;
        height: 40px;
        background-color: rgb(251, 181, 4);
        text-align: center;
        border-radius: 50%;
        margin-left: -10px;
    }

    .avatar-initials-small {
        position: relative;
        font-size: 20px;
        line-height: 40px;
        color: #fff;
    }

    .wrap-name{
        font-size: 14px;
        font-weight: 600;
        width: 120px;
        text-overflow: ellipsis;
        white-space: nowrap;
        overflow: hidden;
        margin-top: 10px;
        float: right;
        margin-right: -125px;
        text-align: left;
    }

    .wrap-name:hover {
        white-space: initial;
        overflow:visible;
        cursor: pointer;
    }

    .item {
        font-size: 14px;
        padding: 5px;
        width: 160px;
    }

    .avatar-image {
        width: 40px;
        height: 40px;
        margin-left: -16px;
        margin-top: -7px;
    }

    .avatar-image-small {
        width: 40px;
        height: 40px;
        margin-left: -5px;
    }
    .wrap-image {
        margin-top: 10px;
        margin-left: 10px;
        margin-right: 0px;
    }

</style>