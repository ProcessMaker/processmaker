<template>
  <span>
    <button class="btn btn-outline-secondary btn-block" @click="show">
      <i class="fas fa-user-friends"></i> Reassign
    </button>
    <b-modal v-model="showReassignment" size="md" centered title="Reassign to" v-cloak>
      <div class="list-users-groups">
        <span
          v-for="(row, index) in usersList"
          class="list-group-item list-group-item-action pt-1 pb-1"
          :class="{'bg-primary': selectedIndex == index}"
          @click="selectedItem(row, index)"
          @dblclick="selectedItem(row, index);reassignUser();"
        >
          <avatar-image class-container size="12" class-image :input-data="row"></avatar-image>
        </span>
      </div>
      <div slot="modal-footer">
        <b-button
          :disabled="selectedIndex < 0"
          @click="reassignUser"
          class="btn btn-outline-success btn-sm text-uppercase"
        >REASSIGN</b-button>
        <b-button @click="cancelReassign" class="btn btn-success btn-sm text-uppercase">CANCEL</b-button>
      </div>
    </b-modal>
  </span>
</template>

<script>
export default {
  props: {
    task: Object
  },
  data() {
    return {
      selected: null,
      selectedIndex: -1,
      usersList: [],
      filter: "",
      showReassignment: false,
    };
  },
  watch: {
    showReassignment(show) {
      show ? this.loadUsers() : null;
    }
  },
  methods: {
    show() {
      this.showReassignment = true;
    },
    cancelReassign() {
      this.showReassignment = false;
      this.selectedItem(null, -1);
    },
    reassignUser() {
      if (this.selected) {
        ProcessMaker.apiClient
          .put("tasks/" + this.task.id, {
            user_id: this.selected.id
          })
          .then(response => {
            this.showReassignment = false;
            this.selectedItem(null, -1);
            window.location.href =
              "/requests/" + response.data.process_request_id;
          });
      }
    },
    selectedItem(selected, index) {
      this.selected = selected;
      this.selectedIndex = index;
    },
    loadUsers() {
      ProcessMaker.apiClient
        .get("tasks/" + this.task.id, {
          params: {
            include: "assignableUsers"
          }
        })
        .then(response => {
          this.$set(this, "usersList", response.data.assignableUsers);
        });
    }
  }
};
</script>

<style lang="scss" scoped>
.icon {
  width: 1.25em;
}
.list-users-groups {
  border: 1px solid #b6bfc6;
  border-radius: 2px;
  height: 10em;
}
</style>
