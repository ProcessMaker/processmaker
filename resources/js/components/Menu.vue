<template>
  <b-card-header>
    <b-row>
      <b-col>
        <template v-for="(item, index) in section.left">
          <b-button-group v-if="isVisible(item, 'group')" size="sm" :key="index">
            <b-button
              v-for="(button, indexButton) in item.items"
              :variant="button.variant || 'secondary'"
              class="text-capitalize"
              :title="button.title"
              :key="indexButton"
              @click="executeFunction(button.action)"
              v-if="button.hide !== true"
            >
              <i :class="button.icon"></i>
              {{ button.name }}
            </b-button>
          </b-button-group>

          <b-button
            v-if="isVisible(item, 'button')"
            :variant="item.variant || 'secondary'"
            size="sm"
            class="text-capitalize"
            :title="item.title"
            :key="index"
            @click="executeFunction(item.action)"
          >
            <i :class="item.icon"></i>
            {{ item.name }}
          </b-button>

          <component
            v-if="item.type !== 'group' && item.type !== 'button'"
            :is="item.type"
            :options="item.options"
            :key="index"
          ></component>
        </template>
      </b-col>

      <b-col class="text-right" v-if="sectionRight">
        <template v-for="(item, index) in section.right">
          <b-button-group v-if="isVisible(item, 'group')" size="sm" :key="index">
            <b-button
              v-for="(button, indexButton) in item.items"
              :variant="button.variant || 'secondary'"
              class="text-capitalize"
              :title="button.title"
              :key="`group-${indexButton}`"
              @click="executeFunction(button.action)"
              v-if="button.hide !== true"
            >
              <i :class="button.icon"></i>
              {{ button.name }}
            </b-button>
          </b-button-group>

          <b-button
            v-if="isVisible(item, 'button')"
            size="sm"
            :variant="item.variant || 'secondary'"
            class="text-capitalize"
            :title="item.title"
            :key="index"
            @click="executeFunction(item.action)"
          >
            <i :class="item.icon"></i>
            {{ item.name }}
          </b-button>

          <component
            v-if="item.type !== 'group' && item.type !== 'button'"
            :is="item.type"
            :options="item.options"
            :key="index"
          ></component>
        </template>
      </b-col>
    </b-row>
  </b-card-header>
</template>


<script>
export default {
  props: ["options", "environment"],
  data() {
    return {
      changeItems: {},
      newItems: [],
      sectionRight: true,
      items: []
    };
  },
  watch: {},
  computed: {
    section() {
      let response = {};
      response.left = [];
      response.right = [];
      this.items = [];

      this.options.forEach(item => {
        if (item.type === "group") {
          item.items.forEach(sub => {
            this.items.push(sub.id);
            return Object.assign(sub, this.changeItems[sub.id]);
          });
        }
        response[item.section].push(
          Object.assign(item, this.changeItems[item.id])
        );
        this.items.push(item.id);
      });
      this.newItems.forEach(item => {
        if (this.items.indexOf(item.id) === -1) {
          response[item.section].push(item);
          this.items.push(item.id);
        }
      });
      return response;
    }
  },
  methods: {
    executeFunction(callback) {
      if (typeof callback === "function") {
        callback();
      } else {
        eval(`this.environment.${callback}`);
      }
    },
    isVisible(item, type) {
      return item.type === type && item.hide !== true;
    },
    changeItem(id, value) {
      this.changeItems[id] = value;
      this.changeOptions(this.section.left);
      this.changeOptions(this.section.right);
    },
    changeOptions(data) {
      data.forEach(item => {
        item = Object.assign(item, this.changeItems[item.id]);
        if (item.type === "group") {
          this.changeOptions(item.items);
        }
      });
    },
    addItem(value) {
      this.newItems.push(value);
    }
  }
};
</script>
