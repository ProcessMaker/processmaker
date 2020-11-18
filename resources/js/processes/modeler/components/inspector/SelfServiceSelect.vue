<template>
  <div>
    <div class="d-flex justify-content-between align-items-center mb-3">
      <label class="m-0">{{ $t('Assigned Users / Groups') }}</label>
      <b-button class="" variant="secondary" size="sm" @click="showAddCard">+</b-button>
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
        <button type="button" class="btn btn-sm btn-outline-secondary mr-2" @click="editIndex=null">
            {{ $t('Cancel') }}
        </button>
        <button type="button" class="btn btn-sm btn-secondary" @click="addSelfServiceAssignment()">
            {{ $t('Add') }}
        </button>
      </div>
    </div>
    <div v-for="(assignment, index) in selfServiceAssignments" :key="index">
      <div v-if="showConfirmationCard">
        <div class="card mb-3 bg-danger text-white text-right">
          <div class="card-body p-2">
            {{ $t('Are you sure you want to delete' + assignment.name + '?') }}
          </div>
          <div class="card-footer text-right p-2">
            <button type="button" class="btn btn-sm btn-light mr-2" @click="showConfirmationCard = false">
              {{ $t('Cancel') }}
            </button>
            <button type="button" class="btn btn-sm btn-danger" @click="deleteOption(index)">
              {{ $t('Delete') }}
            </button>
          </div>
        </div>
      </div>
      <div class="row border-top" :class="rowCss(index)">
        <div class="col-1">
          <i v-if="assignment.type == 'user'" class="fas fa-user"></i>
          <i v-else class="fas fa-users"></i>
        </div>
        <div class="col-5"> 
          {{ assignment.name }} 
        </div>
        <div class="col-1"> 
          <b-button variant="link" @click="showDeleteConfirmation(index)" :title="$t('Delete')">
            <i class="fas fa-trash-alt"/>
          </b-button>
        </div>
      </div>
    </div>
  </div>
</template>
 
<script>
export default {
  data() {
    return {
      showCard: false,
      selfServiceAssignments: [],
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
    }
  },
  methods: {
    showAddCard() {
      this.showCard = true;
    },
    addSelfServiceAssignment() {
      this.selectedAssignment.groups.forEach(group => {
        const field = {
          "type" : "group",
          "id" : group.id,
          "name": group.name
        };
          this.selfServiceAssignments.push(field);
      });

      this.selectedAssignment.users.forEach(user => {
        const field = {
          "type" : "user",
          "id" : user.id,
          "name": user.fullname
        };
        this.selfServiceAssignments.push(field);
      });

      this.showCard = false; 
    },
    showDeleteConfirmation() {
      this.showConfirmationCard = true;
    },
    rowCss(index) {
      return index % 2 === 0 ? 'striped' : 'bg-default';
    },
    deleteOption(index) {
      this.selfServiceAssignments.splice(index, 1);
      this.showConfirmationCard = false;
    }
  }
}
</script>

<style scoped lang="scss">
  .striped {
    background-color: rgba(0,0,0,.05);
  }
</style>