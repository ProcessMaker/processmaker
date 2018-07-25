<template>
	<div class="notifications">
		<a class="count-info" data-toggle="dropdown" href="#" aria-expanded="false">
			<i class="fas fa-bell fa-2x"></i>
				<b-badge pill variant="danger" v-show="messages.length>0">{{messages.length}}</b-badge>
		</a>
		<ul class="dropdown-menu dropdown-alerts">
			<li>
				<div class="arrow-container"><div class="arrow"></div></div>
			</li>
			<li class="dropdown-item">
				<span class="drop-head">New Tasks</span>
			</li>
			<li v-for="task in messages" class="dropdown-item">
				<div>
					<a id="task-process" v-bind:href="task.url" @click.stop="remove(task)" target="_blank">
						{{task.name}}
					</a>
					<small class="float-right task-data font-weight-bold">{{formatDateTime(task.dateTime)}}</small>
				</div>
				<div class="task-data">
					{{task.processName}}
				</div>
				<div class="task-data">
					{{task.userName}}
				</div>
			</li>
			<li class="dropdown-divider"></li>
			<li class="dropdown-item">
				<div class="link-block">
					<a href="/task">
						VIEW ALL TASKS
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
  right: -28px;
  margin-top: 16px;
  left: auto;
  width: 400px;
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
  font-size: 0.6em;
  padding: 2px 5px;
  position: absolute;
  right: 0.5em;
  top: 1em;
}
.arrow {
  -webkit-transform: rotate(45deg);
  transform: rotate(45deg);
  width: 25px;
  height: 25px;
  /* border: 1px solid #222222; */
  -webkit-box-shadow: 0px 0px 3px 0px rgba(150, 150, 150, 0.5);
  -moz-box-shadow: 0px 0px 3px 0px rgba(150, 150, 150, 0.5);
  box-shadow: 0px 0px 3px 0px rgba(150, 150, 150, 0.5);
  position: absolute;
  top: 8px;
  background-color: white;
  right: 38px;
}
.arrow-container {
  position: absolute;
  overflow: hidden;
  height: 16px;
  width: 64px;
  right: 0px;
  top: -16px;
}
.notifications {
  position: relative;
  padding: 16px;
}
#task-process {
  color: #3397e1;
  font-size: 16px;
}
.task-data {
  color: rgba(0, 0, 0, 0.5);
  font-size: 12px;
}
.drop-head {
  font-size: 18px;
  font-weight: 400;
}
</style>
