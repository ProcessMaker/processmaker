<template>
  <div class="objects-menu">
    <div class="object-menu-title">Process Objects</div>
    <div class="object-menu-items-container">
    <designer-options-menu-item @title-click="handleItemClick(item)" @button-click="handleAddClick(item)" v-for="(item, index) in menuItems" :key="item.id" :title="item.title" data-target="#item.key" data-toggle="modal"></designer-options-menu-item>
    </div>
  </div>
</template>

<script>
import EventBus from '../lib/event-bus'
import designerOptionsMenuItem from "./designer-options-menu-item.vue";
export default {
  components: {
    "designer-options-menu-item": designerOptionsMenuItem
  },
  data() {
    return {
      menuItems: [
        {
          title: "Variables",
          key: 'variables'
        },
        {
          title: "Message Types",
          key: 'message-types'
        },
        {
          title: "Forms",
          key: 'forms'
        },
        {
          title: "Input Documents",
          key: 'input-documents'
        },
        {
          title: "Output Documents",
          key: 'output-documents'
        },
        {
          title: "Triggers",
          key: 'triggers'
        },
        {
          title: "Report Tables",
          key: 'report-tables'
        },
        {
          title: "Database Connections",
          key: 'database-connections'
        },
        {
          title: "Templates",
          key: 'templates'
        },
        {
          title: "Public Files",
          key: 'public-files'
        },
        {
          title: "Permissions",
          key: 'permissions'
        },
        {
          title: "Case Tracker",
          key: 'case-tracker'
        },
        {
          title: "Supervisiors",
          key: 'supervisors'
        }
      ]
    };
  },
  methods: {
    handleItemClick(item) {
      alert('Behavior TBD');
    },
    handleAddClick(item) {
      EventBus.$emit('open-add-dialog', item.key);
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
    background-color: rgb(174, 183, 190);
    font-size: 10px;
    text-transform: uppercase;
    font-weight: bold;
    color: white;
    padding-left: 16px;
  }

  .object-menu-items-container {
    overflow: auto;
    padding: 4px;
  }
}
</style>
