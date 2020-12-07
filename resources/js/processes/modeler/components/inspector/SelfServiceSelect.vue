<template>
  <div>
    <div class="d-flex justify-content-between align-items-center mb-3">
      <label class="m-0">{{ $t('Assigned Users / Groups') }}</label>
      <b-button variant="secondary" size="sm" @click="showCard = true">+</b-button>
    </div>
    <div v-if="showCard" class="card mb-2">
      <div class="card-header">
        {{ $t('Add Self Service') }}
      </div>
      <div class="card-body p-2">
        <select-user-group
            :label="$t('User / Group')"
            v-model="selectedAssignment"
            :hide-users="false"
            :multiple="false" 
        />
      </div>
      <div class="card-footer text-right p-2">
        <button type="button" class="btn btn-sm btn-outline-secondary mr-2" @click="showCard = false">
            {{ $t('Cancel') }}
        </button>
        <button type="button" class="btn btn-sm btn-secondary" @click="addSelfServiceAssignment()">
            {{ $t('Add') }}
        </button>
      </div>
    </div>
    <div v-if="showConfirmationCard">
      <div class="card mb-3 bg-danger text-white text-right">
        <div class="card-body p-2" v-html="confirmationMessage"></div>
        <div class="card-footer text-right p-2">
          <button type="button" class="btn btn-sm btn-light mr-2" @click="showConfirmationCard = false">
            {{ $t('Cancel') }}
          </button>
          <button type="button" class="btn btn-sm btn-danger" @click="deleteOption()">
            {{ $t('Delete') }}
          </button>
        </div>
      </div>
    </div>
    <div class="pb-3">
      <div v-for="(items, itemIndex) in selfServiceAssignments" :key="itemIndex">
        <div v-for="(assignment, assignmentIndex) in items" :key="assignmentIndex">
          <div class="row border-top" :class="rowCss(assignmentIndex)">
            <div class="col-1">
              <i v-if="assignment.type == 'user'" class="fas fa-user"></i>
              <i v-else class="fas fa-users"></i>
            </div>
            <div class="col-8"> 
              {{ assignment.name }} 
            </div>
            <div class="col-1"> 
              <b-button class="p-0 text-secondary" variant="link" @click="showDeleteConfirmation(itemIndex, assignment.name, assignmentIndex)" :title="$t('Delete')">
                <i class="fas fa-trash-alt"/>
              </b-button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
 
<script>
export default {
  props: ['value'],
  data() {
    return {
      showCard: false,
      selfServiceAssignments: {
        groups: [],
        users: []
      },
      selectedAssignment: null,
      showConfirmationCard: false,
      fields: [
        {
          label: '',
          key: '__icon',
        },
        {
          label: '',
          key: 'name',
        },
        {
          key: '__actions',
          label: '',
          class: 'text-right',
        },
      ],
      arrayIndex: null,
      removeIndex: null,
      confirmationMessage: null,
    }
  },
  watch: {
    selfServiceAssignments: {
      deep:true,
      handler() {
        this.$emit('input', this.selfServiceAssignments);
      }
    },
  },
  methods: {
    addSelfServiceAssignment() {
      this.selectedAssignment.groups.forEach(group => {
        const field = {
          "type" : "group",
          "id" : group.id,
          "name": group.name
        };
          this.selfServiceAssignments.groups.push(field);
      });

      this.selectedAssignment.users.forEach(user => {
        const field = {
          "type" : "user",
          "id" : user.id,
          "name": user.fullname
        };
        this.selfServiceAssignments.users.push(field);
      });
      this.selectedAssignment.groups = [];
      this.selectedAssignment.users = [];
      this.showCard = false; 
    },
    showDeleteConfirmation(index, name, assignmentIndex) {
      this.arrayIndex = index;
      this.removeIndex = assignmentIndex;
      this.confirmationMessage = this.$t('Are you sure you want to delete {{item}}', {item: name});
      this.showConfirmationCard = true;
    },
    rowCss(index) {
      return index % 2 === 0 ? 'striped' : 'bg-default';
    },
    deleteOption() {
      this.selfServiceAssignments[this.arrayIndex].splice(this.removeIndex, 1);
      this.showConfirmationCard = false;
    },
    getGroups(ids) {
      if (!ids) {
        return;
      }
      ids.forEach(id => {
        ProcessMaker.apiClient.get("groups/" + id).then(response => {
          const field = {
            "type": "group",
            "id": 'group-' + response.data.id,
            "name": response.data.name
          };
          this.selfServiceAssignments.groups.push(field);
        });
      });
    },
    getUsers(ids) {
      if (!ids) {
        return;
      }
      ids.forEach(id => {
        ProcessMaker.apiClient.get("users/" + id).then(response => {
          const field = {
            "type": "user",
            "id": response.data.id,
            "name": response.data.fullname
          };
          this.selfServiceAssignments.users.push(field);
        });
      });
    }
  },
  mounted() {
    this.getGroups(this.value.groups);
    this.getUsers(this.value.users);
  }
}
</script>

<style scoped lang="scss">
  .striped {
    background-color: rgba(0,0,0,.05);
  }
</style>