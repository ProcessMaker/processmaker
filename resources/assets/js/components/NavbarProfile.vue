<template>
    <div id="userMenu">
        <div class="my-3">
            <b-btn id="avatarMenu" :disabled="popoverShow" class="avatar-circle btn-outline-warning">
                <span class="avatar-initials"> {{ initials }} </span>
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
                    <img class="avatar-small" :src="sourceImage">
                </template>
                <template v-else>
                    <div class="avatar-circle-small">
                        <span class="avatar-initials-small text-uppercase">
                        {{initials}}
                        </span>
                    </div>
                </template>
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
                sourceImage: null,
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
                this.sourceImage = this.avatar;
                this.user = this.info;
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
        margin-left: -15px;
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
        margin-top: -30px;
        float: right;
        margin-right: -10px;
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
        width: 150px;
    }

</style>