<template>
    <div class="notifications" style="position: relative">
        <a class="count-info" data-toggle="dropdown" href="#" aria-expanded="false">
            <i class="fa fa-bell"></i>
            <b-badge pill variant="success" v-show="messages.length>0">{{messages.length}}</b-badge>
        </a>
        <ul class="dropdown-menu dropdown-alerts">
            <li v-for="message in messages" class="dropdown-item">
                <a v-bind:href="message.href" @click.stop="remove(message)" target="_blank">
                    <i v-bind:class="message.icon"></i> {{message.text}}
                </a>
                <a class="float-right small" href="javascript:void(0)" @click="remove(message)"><i class="fas fa-times"></i></a>
            </li>
            <li class="dropdown-divider"></li>
            <li>
                <div class="text-center link-block">
                    <a href="/notifications" class="dropdown-item">
                        <strong>See All Notifications</strong>
                        <i class="fa fa-angle-right"></i>
                    </a>
                </div>
            </li>
        </ul>
    </div>
</template>

<script>

    export default {
        props: {
            messages: Array
        },
        watch: {
            messages(value, mutation) {
                $(this.$el).find(".dropdown-menu").dropdown('toggle');
            }
        },
        data() {
            return {
            };
        },
        methods: {
            remove(message) {
                this.messages.splice(this.messages.indexOf(message), 1);
            }
        },
        mounted() {
        }
    };
</script>

<style lang="scss" scoped>
    .dropdown-menu {
        right: 0;
        left: auto;
        width: 400px;
    }
    .count-info {
    }
    .count-info .badge {
        font-size: 0.6em;
        padding: 2px 5px;
        position: absolute;
        right: -0.5em;
        top: -0.5em;
    }
</style>
