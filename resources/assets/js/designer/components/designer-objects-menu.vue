<template>
    <div class="objects-menu">
        <div class="object-menu-title"><span class="menu-title-span">Process Objects</span></div>
        <div class="object-menu-items-container">
            <designer-options-menu-item @title-click="handleItemClick(item)" @button-click="handleAddClick(item)"
                                        v-for="(item, index) in menuItems" :key="item.id" :title="item.title"
                                        v-bind:hasadd="item.hasadd" :hasmodal="item.hasmodal"
                                        v-bind:hasVisible="item.visible">
            </designer-options-menu-item>
        </div>
    </div>
</template>

<script>
import EventBus from "../lib/event-bus";
import designerOptionsMenuItem from "./designer-options-menu-item.vue";

export default {
  components: {
    "designer-options-menu-item": designerOptionsMenuItem
  },
  data() {
    return {
      menuItems: [
        {
          title: "Message Types",
          key: "message-types",
          hasadd: true,
          visible: false
        },
        {
          title: "Forms",
          key: "forms",
          hasadd: true,
          visible: true
        },
        {
          title: "Input Documents",
          key: "input-documents",
          hasadd: true,
          visible: true
        },
        {
          title: "Output Documents",
          key: "output-documents",
          hasadd: true,
          visible: true
        },
        {
          title: "Scripts",
          key: "scripts",
          hasadd: true,
          visible: true
        },
        {
          title: "Report Tables",
          key: "report-tables",
          hasadd: true,
          visible: false
        },
        {
          title: "Database Connections",
          key: "database-connections",
          hasadd: true,
          visible: false
        },
        {
          title: "Templates",
          key: "templates",
          hasadd: true,
          visible: false
        },
        {
          title: "Public Files",
          key: "public-files",
          hasadd: true,
          visible: false
        },
        {
          title: "Permissions",
          key: "permissions",
          hasadd: true,
          visible: false
        },
        {
          title: "Case Tracker",
          key: "case-tracker",
          hasadd: false,
          visible: false
        },
        {
          title: "Supervisors",
          key: "supervisors",
          hasadd: false,
          visible: false
        },
        {
          title: "Task List View",
          key: "task-list-view",
          hasadd: false,
          visible: false
        },
        {
          title: "Request Summary",
          key: "request-summary",
          hasadd: false,
          visible: false
        }
      ]
    };
  },
  methods: {
    handleItemClick(item) {
      EventBus.$emit("open-title-dialog", item.key);
    },
    handleAddClick(item) {
      EventBus.$emit("open-add-dialog", item.key);
    }
  }
};
</script>

<style lang="scss" scoped>
.objects-menu {
  width: 220px;
  font-size: 12px;
  min-height: 100%;
  max-height: 100%;
  display: flex;
  flex-direction: column;
  background-color: #fff;
  border-left: 2px solid #e1dbd9;

  .object-menu-title {
    min-height: 32px;
    max-height: 32px;
    line-height: 32px;
    background-color: #b6bfc6;
    font-size: 10px;
    text-transform: uppercase;
    font-weight: bold;
    color: white;
    padding-left: 16px;
  }
  .menu-title-span {
    margin-left: -9px;
  }
  .object-menu-items-container {
    overflow: auto;
    padding: 4px;
  }
}
</style>
