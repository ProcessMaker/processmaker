<template>
    <div class="notifications">
        <a class="count-info" data-toggle="dropdown" href="#" aria-expanded="false" id="exPopover1-bottom">
            <i class="fas fa-bell fa-lg font-size-23"></i>
            <b-badge pill variant="danger" v-show="messages.length>0">{{messages.length}}</b-badge>
        </a>
        <b-popover :target="'exPopover1-bottom'"
                   :placement="'bottomleft'"
                   triggers="click blur"
        >
            <h3 class="popover-header">New Tasks</h3>
            <ul class="list-unstyled tasklist">
                <li v-if="messages.length == 0">No Tasks Found
                    <hr>
                </li>
                <li v-for="task in messages">
                    <small class="float-right muted">{{ moment(task.dateTime).format() }}</small>
                    <h3><a v-bind:href="task.url" @click.stop="remove(task)">{{task.name}}</a></h3>
                    <div class="muted">
                        {{task.processName}}<br>
                        {{task.userName}}
                    </div>
                    <span class="badge badge-pill badge-info float-right" style="cursor:pointer" @click="remove(task)">
                        Dismiss
                    </span>
                    <hr>
                </li>
            </ul>
        </b-popover>
    </div>
</template>

<script>
    import moment from "moment";
    import {Popover} from 'bootstrap-vue/es/components';
    Vue.use(Popover);
    export default {
        props: {
            messages: Array
        },
        watch: {
            messages(value, mutation) {
                $(this.$el)
                    .find(".dropdown-menu")
                    .dropdown("toggle");
            }
        },
        data() {
            return {
                arrowStyle: {
                    top: "0px",
                    left: "0px"
                },
            };
        },
        methods: {
            remove(message) {
                ProcessMaker.removeNotifications([message.id]);
            },
            formatDateTime(iso8601) {
                return moment(iso8601).format("MM/DD/YY HH:mm");
            }
        },
        mounted() {
            this.arrowStyle.top = $("#navbar-request-button").offset().top + 45 + "px";
            this.arrowStyle.left =
                $("#navbar-request-button").offset().left + 53 + "px";

            window.addEventListener("resize", () => {
                this.arrowStyle.top =
                    $("#navbar-request-button").offset().top + 42 + "px";
                this.arrowStyle.left =
                    $("#navbar-request-button").offset().left + 32 + "px";
            });


            ProcessMaker.apiClient.get('/user_notifications')
                .then(function (response) {
                    response.data.forEach(function (element) {
                        ProcessMaker.pushNotification(element);
                    })
                });
        }
    };
</script>

<style lang="scss" scoped>
    .popover-header {
        background-color: #fff;
        font-size: 18px;
        font-weight: 600;
        color: #333333;
        margin: -12px;
        margin-top: -8px;
        margin-bottom: 18px;
        display: block
    }

    .tasklist {
        font-size: 12px;
        width: 250px;
        margin-bottom: 6px;
        h3 {
            font-size: 14px;
            color: #3397e1;
        }
        .muted {
            color: #7b8792
        }
        .footer {
            font-size: 14px;
            font-weight: normal;
            color: #3397e1;
            text-transform: uppercase;
        }

    }

    .count-info {
        color: #788793;
    }

    .count-info .badge {
        font-size: 10px;
        padding: 2px 3px;
        position: absolute;
        right: 10px;
        top: 12px;
    }

    .notifications {
        position: relative;
        padding: 16px;
    }

</style>
