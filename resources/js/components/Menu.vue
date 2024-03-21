<template>
  <b-card-header>
    <b-row>
      <b-col v-if="!renderTop">
        <template v-for="(item, index) in section.left">
          <b-button-group
            v-if="isVisible(item, 'group')"
            :key="index"
            size="sm"
          >
            <b-button
              v-for="(button, indexButton) in visibleItems(item)"
              :key="indexButton"
              :variant="button.variant || 'secondary'"
              class="text-capitalize"
              :title="button.title"
              tabindex="1"
              @click="executeFunction(button.action)"
            >
              <i :class="button.icon" />
              {{ button.name }}
            </b-button>
          </b-button-group>

          <b-button
            v-if="isVisible(item, 'button')"
            :key="index"
            class="text-capitalize"
            size="sm"
            :variant="item.variant || 'secondary'"
            :title="item.title"
            @click="executeFunction(item.action)"
          >
            <i :class="item.icon" />
            {{ item.name }}
          </b-button>

          <component
            :is="item.type"
            v-if="item.type !== 'group' && item.type !== 'button'"
            :key="index"
            :options="item.options"
          />
        </template>
      </b-col>

      <b-col
        v-if="sectionRight"
        class="text-right"
      >
        <template v-for="(item, index) in rightMenus">
          <b-button-group
            v-if="isVisible(item, 'group')"
            :key="index"
            size="sm"
          >
            <b-button
              v-for="(button, indexButton) in visibleItems(item)"
              :key="`group-${indexButton}`"
              :variant="button.variant || 'secondary'"
              class="text-capitalize screen-toolbar-button"
              :title="button.title"
              @click="executeFunction(button.action)"
            >
              <i :class="button.icon" />
              {{ button.name }}
            </b-button>
          </b-button-group>

          <b-button
            v-if="isVisible(item, 'button')"
            :key="index"
            size="sm"
            :variant="item.variant || 'secondary'"
            class="text-capitalize screen-toolbar-button"
            :title="item.title"
            @click="executeFunction(item.action)"
          >
            <i :class="item.icon" />
            {{ item.name }}
          </b-button>

          <component
            :is="item.type"
            v-if="item.type !== 'group' && item.type !== 'button' && !item.hide"
            :ref="item.id"
            v-bind="{
              ...item.options
            }"
            :key="index"
            :options="item.options"
            @navigate="handleNavigate"
          />
        </template>
      </b-col>
    </b-row>
  </b-card-header>
</template>

<script>
export default {
  props: {
    options: {
      type: Array,
      default: () => [],
    },
    environment: {
      type: Object,
      default: () => {},
    },
    renderTop: {
      type: Boolean,
      default: false,
    },
    initialNewItems: {
      type: Array,
      default: () => [],
    },
  },
  data() {
    return {
      changeItems: {},
      newItems: this.initialNewItems,
      sectionRight: true,
      items: [],
    };
  },
  computed: {
    rightMenus() {
      return this.renderTop ? this.section.rightTop : this.section.right;
    },
    section() {
      const response = {};
      response.left = [];
      response.right = [];
      response.rightTop = [];
      // eslint-disable-next-line vue/no-side-effects-in-computed-properties
      this.items = [];

      this.options.forEach((item) => {
        if (item.type === "group") {
          item.items.forEach((sub) => {
            this.items.push(sub.id);
            return Object.assign(sub, this.changeItems[sub.id]);
          });
        }
        response[item.section].push(
          Object.assign(item, this.changeItems[item.id]),
        );
        this.items.push(item.id);
      });
      this.newItems.forEach((item) => {
        if (this.items.indexOf(item.id) === -1) {
          response[item.section].push(item);
          this.items.push(item.id);
        }
      });
      return response;
    },
  },
  methods: {
    handleNavigate(action) {
      switch (action?.value) {
        case "discard-draft":
          window.ProcessMaker.EventBus.$emit("open-versions-discard-modal");
          break;
        case "create-template":
          window.ProcessMaker.EventBus.$emit("show-create-template-modal");
          break;
        default:
          if (action.action) {
            this.executeFunction(action.action);
          }
          break;
      }
    },
    executeFunction(callback) {
      if (typeof callback === "function") {
        callback();
      } else {
        // eslint-disable-next-line no-eval
        eval(`this.environment.${callback}`);
      }
    },
    visibleItems(item) {
      return item.items.filter((button) => button.hide !== true);
    },
    isVisible(item, type) {
      if (item.type === type && item.hide !== true) {
        if (item.displayCondition) {
        // eslint-disable-next-line no-eval
          return eval(`this.environment.${item.displayCondition}`);
        }

        return true;
      }

      return false;
    },
    changeItem(id, value) {
      this.changeItems[id] = value;
      this.changeOptions(this.section.left);
      this.changeOptions(this.section.right);
    },
    changeOptions(data) {
      data.forEach((item) => {
        const newItem = Object.assign(item, this.changeItems[item.id]);
        if (newItem.type === "group") {
          this.changeOptions(newItem.items);
        }
      });
    },
    addItem(value, index = null) {
      return index !== null ? this.newItems.splice(index, 0, value) : this.newItems.push(value);
    },
    removeItem(idToRemove) {
      const indexToRemove = this.newItems.findIndex((element) => element.id === idToRemove);
      if (indexToRemove !== -1) {
        this.newItems.splice(indexToRemove, 1);
      }
    },
  },
};
</script>

<style scoped>
.screen-toolbar-button {
  color: #556271;
}
</style>
