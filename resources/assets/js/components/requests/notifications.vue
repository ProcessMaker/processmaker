<template>
    <div class="notifications">
        <a class="count-info" data-toggle="dropdown" href="#" aria-expanded="false">
            <i class="fas fa-bell fa-lg font-size-23"></i>
            <b-badge pill variant="danger" v-show="messages.length>0">{{messages.length}}</b-badge>
        </a>
        <ul class="dropdown-menu dropdown-alerts">
            <li>
                <div class="arrow-container"><div class="arrow"></div></div>
            </li>
            <li class="dropdown-item">
                <h4 class="mb-3">New Tasks</h4>
            </li>
            <li v-for="task in messages" class="dropdown-item font-weight">
                <div class="mb-1">
                  <small class="float-right task-meta mt-1">{{formatDateTime(task.dateTime)}}</small>
                  <a class="text-primary font-size-16" v-bind:href="task.url" @click.stop="remove(task)" target="_blank">
                    <span>{{task.name}}</span> 
                  </a>
                </div>
                <div>
                  <span class="text-secondary">{{task.processName}}</span> 
                </div>
                <div>
                  <span class="text-secondary">{{task.userName}}</span>
                </div>
                <hr id="divider">
            </li>
            <li class="dropdown-item">
                <div class="link-block mt-2 mb-2">
                    <a href="/task">
                       <span class="text-uppercase font-size-16 font-weight">view ALL TASKS</span> 
                    </a>
                </div>
            </li>
        </ul>
    </div>
</template>

<script>
import moment from "moment";

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
    return {};
  },
  methods: {
    remove(message) {
      this.messages.splice(this.messages.indexOf(message), 1);
    },
    formatDateTime(iso8601) {
      return moment(iso8601).format("hh:mm MM.DD.YYYY");
    }
  },
  mounted() {}
};
</script>

<style lang="scss" scoped>
.dropdown-menu {
  right: 2px;
  margin-top: -2px;
  left: auto;
  width: 300px;
  border-radius: 2px;
  border: none;
  background-color: #ffffff;
  -webkit-box-shadow: 0px 2px 4px 1px rgba(150, 150, 150, 1);
  -moz-box-shadow: 0px 2px 4px 1px rgba(150, 150, 150, 1);
  box-shadow: 0px 2px 4px 1px rgba(150, 150, 150, 1);
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
.arrow {
  -webkit-transform: rotate(45deg);
  transform: rotate(45deg);
  width: 10px;
  height: 25px;
  /* border: 1px solid #222222; */
  -webkit-box-shadow: 0px 0px 3px 0px rgba(150, 150, 150, 0.5);
  -moz-box-shadow: 0px 0px 3px 0px rgba(150, 150, 150, 0.5);
  box-shadow: 0px 0px 3px 0px rgba(150, 150, 150, 0.5);
  position: absolute;
  top: 8px;
  background-color: white;
  right: 25px;
}
.arrow-container {
  position: absolute;
  overflow: hidden;
  height: 16px;
  width: 64px;
  right: 0px;
  top: -16px;
}
#divider {
  margin-bottom: 0px;
  margin-top: 8px;
}
.notifications {
  position: relative;
  padding: 16px;
}
.task-meta {
  color: #788793;
  font-size: 11px;
}
.font-size-16 {
  font-size: 16px;
}
.font-size-23 {
  font-size: 23px;
}
.font-weight {
  font-weight: 200;
}
</style>
