<template>
  <div class="menu-collapse">
    <div class="menu-width">
      <p class="menu-title">
        {{ $t('Settings') }}
      </p>
      <div
        v-for="menu in menuGroups"
        :key="menu.menu_group"
      >
        <div
          :id="`headingOne${menu.menu_group_order}`"
          @click="updateCollapse(menu)"
        >
          <h5 class="mb-0">
            <div
              class="btn menu-header"
              data-toggle="collapse"
              :data-target="`#collapseOne${menu.id}`"
              aria-expanded="false"
              :aria-controls="`collapseOne${menu.id}`"
            >
              <i :class="{ 'fas fa-caret-down': collapsedMenus[menu.id], 'fas fa-caret-up': !collapsedMenus[menu.id] }" />
              <span>
                <i :class="`fas fa-${menu.ui.icon}`" />
                {{ menu.menu_group }}
              </span>
              <ellipsis-menu
                @navigate="onNavigate"
                :actions="actions"
                :data="menu"
                :custom-button="{icon: 'fas fa-ellipsis-v', content: ''}"
                class="settings-ellipsis-menu"
              />
            </div>
          </h5>
        </div>

        <div
          :id="`collapseOne${menu.id}`"
          class="collapse"
          :class="menu.menu_group_order === 1 ? 'show' : ''"
          :aria-labelledby="`headingOne${menu.menu_group_order}`"
        >
          <b-list-group>
            <b-list-group-item
              v-for="item in menu.groups"
              :key="item.id"
              ref="processItems"
              :class="{ 'list-item-selected': isSelected(item.id) }"
              class="list-item"
              @click="selectItem(item)"
            >
              {{ item.name }}
            </b-list-group-item>
          </b-list-group>
        </div>
      </div>
    </div>
    <add-to-bundle
      :asset-type="selectedMenu.menu_group"
      :setting="true"
      setting-type="settings"
    />
  </div>
</template>

<script>
import AddToBundle from "../../../components/shared/AddToBundle.vue";
import EllipsisMenu from "../../../components/shared/EllipsisMenu.vue";

export default {
  components: { AddToBundle, EllipsisMenu },
  props: [
    "selectGroup",
  ],
  data() {
    return {
      currentTab: 0,
      groups: [],
      menuGroups: [],
      selectedItem: "",
      firstTime: false,
      collapsedMenus: {},
      oldMenuGroups: [],
      changeEmailServers: false,
      selectedMenu: {
        menu_group: "User Settings",
      },
      actions: [
        { value: "add-to-bundle", content: "Add to Bundle", icon: "fp-add-outlined" },
      ],
    };
  },
  mounted() {
    this.firstTime = true;
    this.getMenuGrups();
  },
  methods: {
    getMenuGrups() {
      ProcessMaker.apiClient.get("/settings/menu-groups?order_by=menu_group_order")
        .then((response) => {
          this.transformResponse(response.data.data);
          this.checkCollapedMenus();
          if (this.changeEmailServers) {
            this.checkChangeEmailServer();
          }
          if (this.firstTime) {
            this.selectFirstItem();
          }
        });
    },
    transformResponse(response) {
      this.menuGroups = [];
      response.forEach((element) => {
        element.ui = JSON.parse(element.ui);
        this.menuGroups.push(element);
        element.groups = this.orderGroupAlphabetic(element.groups);
      });
    },
    checkChangeEmailServer() {
      const oldEmailMenu = this.oldMenuGroups.find((menu) => menu.menu_group === "Email").groups;
      const newEmailMenu = this.menuGroups.find((menu) => menu.menu_group === "Email").groups;
      // Check if a Email Server was added
      if (oldEmailMenu.length < newEmailMenu.length) {
        const newGroup = newEmailMenu.filter((group) => !oldEmailMenu.some((oldGroup) => group.id === oldGroup.id));
        if (newGroup[0].id.includes("Email Server")) {
          this.selectItem(newGroup[0]);
        }
      }
      // Check if a Email Server was deleted
      if (oldEmailMenu.length > newEmailMenu.length) {
        const emailDefaultItem = this.menuGroups[0].groups.find((group) => {
          return group.name === "Email Default Settings"
        })
        this.selectItem(emailDefaultItem);
      }
      this.firstTime = false;
      this.changeEmailServers = false;
      this.refreshing = false;
    },
    refresh() {
      this.oldMenuGroups = this.menuGroups;
      this.getMenuGrups();
    },
    orderGroupAlphabetic(groups) {
      // Ignore upper and lowercase
      const newGroups = groups.sort((a, b) => {
        // Alphabetic order
        const nameA = a.name.toUpperCase();
        const nameB = b.name.toUpperCase();
        if (nameA < nameB) {
          return -1;
        }
        if (nameA > nameB) {
          return 1;
        }
        // names must be equal
        return 0;
      });
      return newGroups;
    },
    checkCollapedMenus() {
      const arrayCollaped = {};
      this.menuGroups.forEach((element) => {
        arrayCollaped[element.id] = element.menu_group_order !== 1;
      });
      this.collapsedMenus = arrayCollaped;
    },
    isSelected(item) {
      return this.selectedItem === item;
    },
    selectItem(item) {
      this.selectedItem = item.id;
      this.$emit("selectGroup", item);
    },
    updateCollapse(menu) {
      this.collapsedMenus[menu.id] = !this.collapsedMenus[menu.id];
    },
    selectFirstItem() {
      this.selectItem(this.menuGroups[0].groups[0]);
    },
    onNavigate(action, data, index) {
      switch (action.value) {
        case "add-to-bundle":
          this.selectedMenu = data;
          this.$root.$emit('add-to-bundle', data);
          break;
        default:
          break;
      }
    },
  },
};
</script>

<style lang="css" scoped>
.menu-collapse {
  display: flex;
  justify-content: center;
}
.menu-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  color: #556271;
  font-family: 'Open Sans', sans-serif;
  font-size: 16px;
  padding: 12px 14px;
  font-weight: 600;
  line-height: 22px;
  letter-spacing: -0.02em;
  text-align: left;
  border-radius: 8px;
  width: 100%;
  text-transform: capitalize;
}
.menu-width {
  width: 240px;
}
.menu-title {
  color: #556271;
  font-family: 'Open Sans', sans-serif;
  font-size: 22px;
  font-style: normal;
  font-weight: 600;
  line-height: 29.96px;
  letter-spacing: -2px;
}
.list-group-item {
  background: #f7f9fb;
  border: none;
}
.list-item {
  cursor: pointer;
  padding: 12px 16px 12px 18px;
  color: #4f606d;
  border-radius: 8px;
  font-family: 'Open Sans', sans-serif;
  font-size: 15px;
  font-weight: 400;
  line-height: 20px;
  letter-spacing: -0.02em;
  text-align: left;
}
.list-item:hover,
.menu-header:hover {
  background: #e5edf3;
  color: #4f606d;
}
.list-item-selected,
.menu-header:focus {
  background: #e5edf3;
  color: #1572c2;
  font-weight: 700;
}
</style>
<style lang="scss" scoped>
::v-deep .settings-ellipsis-menu .contracted-menu {
  background-color: transparent;
  border: none;
  box-shadow: none;
}
</style>
