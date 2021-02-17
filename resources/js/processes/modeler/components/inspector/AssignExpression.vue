<template>
  <div>
    <div class="d-flex justify-content-between">
      <label class="m-0">
        {{ $t('Expressions') }}
      </label>
      <b-button class="add-button align-top d-inline rounded-0" variant="secondary" size="sm" @click="showAddCard()">+</b-button>
    </div>
    <div class="helper-text mb-3"><small class="d-block">{{ $t('Expressions are evaluated top to bottom') }}</small></div>

    <div v-if="showCard" class="card mb-2">
      <div class="card-header">
        {{ title }}
      </div>
      <div class="card-body p-2">
        <div class="form-group">
          <label>{{ $t("FEEL Expression") }}</label>
          <textarea class="form-control special-assignment-input" ref="specialAssignmentsInput"  v-model="assignmentExpression" />
          <small class="form-text text-muted">{{ $t("If the FEEL Expression evaluates to true then") }}</small>
        </div>

        <div class="form-group">
          <select-user-group
            :label="$t('Assign to User / Group')"
            v-model="assignedExpression"
            :hide-users="false"
            :multiple="false"
          />
        </div>
      </div>
      <div class="card-footer text-right p-2">
        <button type="button" class="btn btn-sm btn-outline-secondary mr-2" @click="hideAddCard">
          {{ $t('Cancel') }}
        </button>
        <button type="button" class="btn btn-sm btn-secondary" @click="addSpecialAssignment(editIndex)">
          {{ buttonLabel }}
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
          <button type="button" class="btn btn-sm btn-danger" @click="deleteExpression()">
            {{ $t('Delete') }}
          </button>
        </div>
      </div>
    </div>

    <draggable :element="'div'" v-model="specialAssignmentsList" group="assignment" @start="drag=true" @end="drag=false" >
      <div v-for="(assignment, index) in specialAssignmentsList" :key="index" :class="rowCss(index)" class="row border-bottom py-2 assignment-list">
        <div class="d-flex col-12">
          <div class="col-1 p-0" style="cursor:grab">
            <span class="fas fa-arrows-alt-v"/>
          </div>
          <div class="col-9 p-0" style="cursor:grab" >
            <div class="displayed-expression text-truncate">
              {{ assignment.expression }}
            </div>
            <div>
              <i v-if="assignment.type == 'user'" class="fas fa-user"></i>
              <i v-else class="fas fa-users"></i> 
              {{ assignment.assignmentName }}
            </div>
          </div>
          <div class="col-1 p-0 pr-3">
            <a @click="showEditCard(index)" class="fas fa-cog text-dark" style="cursor:pointer" data-cy="inspector-options-edit"/>
          </div>
          <div class="col-1 p-0">
            <a @click="showDeleteConfirmation(index)" class="fas fa-trash-alt text-dark" style="cursor:pointer" data-cy="inspector-options-remove" />
          </div>
        </div>
      </div>
    </draggable>

    <div class="form-group">
      <select-user-group
        :label="$t('Default Assignment')"
        v-model="defaultAssignment"
        :hide-users="false"
        :multiple="false" 
        :helper="$t('If no evaluations are true')"
      />
    </div>
  </div>
</template>

<script>
import draggable from 'vuedraggable';

export default {
  props: ['value'],
  components: {
    draggable
  },
  data() {
    return {
      showCard: false,
      assignmentExpression: null,
      assignmentList: [],
      assignedExpression: null,
      specialAssignments: [],
      cardType: null,
      buttonLabel: null,
      editIndex: null,
      removeIndex: null,
      showConfirmationCard: false,
      defaultAssignment: {
        users: [],
        groups: []
      },
    }
  },
  computed: {
    title() {
      if (this.cardType == 'edit') {
        return this.$t('Edit FEEL Expression');
      } else {
        return this.$t('Add FEEL Expression');
      }
    },
    confirmationMessage() {
      const item = this.specialAssignments[this.removeIndex].expression;
      return this.$t('Are you sure you want to delete expression {{item}}', {item: item});
    },
    specialAssignmentsList: {
      get() {
        return this.specialAssignments.filter(assignment => {
          return !assignment.default;
        });
      },
      set(value) {
        this.specialAssignments = value;
      }
    },
    defaultAssignmentIndex() {
      let defaultAssignment = this.specialAssignments.filter(assignment => {
        return assignment.default;
      });
      let index = this.specialAssignments.indexOf(defaultAssignment[0]);
      return index >= 0 ? index : null;
    }
  },
  watch: {
    specialAssignments: {
      deep:true,
      handler() {
        this.setDefaultAssignmentToEndOfArray();
        this.$emit('input', this.specialAssignments);
      }
    },
    value: {
      deep: true,
      handler() {
        this.specialAssignments = this.value;
      }
    },
    defaultAssignment: {
      deep: true,
      handler() {
        if (this.defaultAssignment.users.length === 0 && this.defaultAssignment.groups.length === 0) {
          return;
        }
        let field;
        if (this.defaultAssignment.users.length && Object.keys(this.defaultAssignment.users[0]).length) {        
          let name = this.defaultAssignment.users[0].fullname ? this.defaultAssignment.users[0].fullname : this.defaultAssignment.users[0].assignmentName;
          let id = this.defaultAssignment.users[0].id ? this.defaultAssignment.users[0].id : this.defaultAssignment.users[0].assignee;
          field = {
            "type" : "user",
            "name": name,
            "id": id,
          };
        } else if (this.defaultAssignment.groups.length && Object.keys(this.defaultAssignment.groups[0]).length)  {
          let name = this.defaultAssignment.groups[0].name ? this.defaultAssignment.groups[0].name : this.defaultAssignment.groups[0].assignmentName;
          let id;
          if (this.defaultAssignment.groups[0].id) {
            if (this.defaultAssignment.groups[0].id.includes("group")){
              id = this.defaultAssignment.groups[0].id.replace("group-", "");
            } else {
              id = this.defaultAssignment.groups[0].id;
            }
          } else {
            if (this.defaultAssignment.groups[0].assignee.includes("group")) {
              id = this.defaultAssignment.groups[0].assignee.replace("group-", "");
            } else {
              id = this.defaultAssignment.groups[0].assignee;
            }
          }
          field = {
            "type" : "group",
            "name": name,
            "id": id,
          };
        }
        
        if (!field) {
          return;
        }

        let byExpression = {
          type: field.type,
          assignee: field.id,
          expression: this.assignmentExpression,
          assignmentName: field.name,
          default: true,
        };
        if (this.defaultAssignmentIndex != null) {
          this.specialAssignments[this.defaultAssignmentIndex] = byExpression;
          this.$emit('input', this.specialAssignments);
        } else {
          this.specialAssignments.push(byExpression);
        }
      }
    }
  },
  methods: {
    addSpecialAssignment(editIndex = null) {
        let field;
        if (this.assignedExpression.users.length) {
          field = {
            "type" : "user",
            "name": this.assignedExpression.users[0].fullname,
            "id": this.assignedExpression.users[0].id,
          };
        } else if (this.assignedExpression.groups.length) {
          field = {
            "type" : "group",
            "name": this.assignedExpression.groups[0].name,
            "id": this.assignedExpression.groups[0].id.replace('group-', ''),
          };
        }
        let byExpression = {
          type: field.type,
          assignee: field.id,
          expression: this.assignmentExpression,
          assignmentName: field.name
        };

        if (byExpression.expression) {
          if (editIndex !== null)  {
            if (byExpression.assignee == null) {
              byExpression.assignee = this.specialAssignments[editIndex].assignee;
              byExpression.assignmentName = this.specialAssignments[editIndex].assignmentName;
            }
            this.specialAssignments[editIndex] = byExpression;
            this.$emit('input', this.specialAssignments);
          } else {
            this.specialAssignments.push(byExpression);
          }
        }
        this.hideAddCard();
    },
    rowCss(index) {
      return index % 2 === 0 ? 'striped' : 'bg-default';
    },
    showEditCard(index) { 
      this.showCard = true;
      this.cardType = 'edit';
      this.buttonLabel = this.$t('Update');
      this.editIndex = index;
      this.assignmentExpression = this.specialAssignments[index].expression;
      let assignee = {
        users: [],
        groups: []
      };
      if (this.specialAssignments[index].type == 'user') {
        assignee.users.push(this.specialAssignments[index].assignee);
      } else if (this.specialAssignments[index].type == 'group') {
        assignee.groups.push(parseInt(this.specialAssignments[index].assignee.substr(6)));
      }

      this.assignedExpression = assignee;
    },
    showDeleteConfirmation(index) {
      this.removeIndex = index;
      this.showConfirmationCard = true;
    },
    showAddCard() {
      this.buttonLabel = this.$t('Add');
      this.showCard = true;
    },
    deleteExpression() {
      this.specialAssignments.splice(this.removeIndex, 1);
      this.showConfirmationCard = false;
    },
    hideAddCard() {
      this.showCard = false;
      this.assignmentExpression = null;
      this.assignedExpression = null;
      this.editIndex = null;
    },
    setDefaultAssignmentToEndOfArray() {
      let index = this.specialAssignments.findIndex(item => item.default == true);
      let length = this.specialAssignments.length - 1;
      if (index == -1) {
        return;
      }
      if (index != length) {
        this.specialAssignments.push(this.specialAssignments.splice(index,1)[0]);
      }
    },
    loadDefaultAssignment() {
      let defaultAssignment = this.specialAssignments.filter(assignment => { return assignment.default;});
      if (defaultAssignment.length == 0) {
        return;
      } 
      if (defaultAssignment[0].type == 'user') {
        this.defaultAssignment.users.push(defaultAssignment[0]);
      } else if (defaultAssignment[0].type == 'group') {
        if (typeof defaultAssignment[0].assignee != 'number') {
          defaultAssignment[0].assignee = defaultAssignment[0].assignee.replace("group-", "");
        }
        this.defaultAssignment.groups.push(defaultAssignment[0]);
      }
    },
  },
  mounted() {
    this.specialAssignments = this.value;
    this.loadDefaultAssignment();
  }
}
</script>

<style scoped>
  .striped {
    background-color: rgba(0,0,0,.05);
  }
  .add-button {
    padding: 0;
    height: 14px;
    width: 13px;
    line-height: 0;
  }
  .helper-text {
    font-size: 12px;
  }

  .displayed-expression {
    width: 146px;
  }
  
  .displayed-expression,
  .special-assignment-input {
    font-family: monospace;
  }

  .assignment-list {
    font-size:13px;
  }
</style>