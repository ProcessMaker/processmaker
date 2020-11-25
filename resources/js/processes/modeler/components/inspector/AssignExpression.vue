<template>
  <div>
    <div class="d-flex justify-content-between align-items-center mb-3">
      <label class="m-0">
        {{ $t('Expressions') }}
        <small class="d-block">{{ $t('Expressions are evaluated top to bottom') }}</small>
      </label>
      <b-button class="" variant="secondary" size="sm" @click="showAddCard()">+</b-button>
    </div>

    <div v-if="showCard" class="card mb-2">
      <div class="card-header">
        {{ title }}
      </div>
      <div class="card-body p-2">
        <div class="form-group">
          <label>{{ $t("Expression") }}</label>
          <input class="form-control" ref="specialAssignmentsInput" type="text" v-model="assignmentExpression">
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
      <div v-for="(assignment, index) in specialAssignmentsList" :key="index" :class="rowCss(index)" class="row border-bottom py-2">
        <div class="d-flex">
          <div class="col-1" style="cursor:grab">
            <span class="fas fa-arrows-alt-v"/>
          </div>
          <div class="col-7" style="cursor:grab">
            <div>{{ assignment.expression }}</div>
            <div>
              <i v-if="assignment.type == 'user'" class="fas fa-user"></i>
              <i v-else class="fas fa-users"></i> 
              {{ assignment.assignmentName }}
            </div>
          </div>
          <div class="col-1">
            <a @click="showEditCard(index)" class="fas fa-cog text-secondary" style="cursor:pointer" data-cy="inspector-options-edit"/>
          </div>
          <div class="col-1">
            <a @click="showDeleteConfirmation(index)" class="fas fa-trash-alt text-secondary" style="cursor:pointer" data-cy="inspector-options-remove" />
          </div>
        </div>
      </div>
    </draggable>

    <div class="form-group">
      <select-user-group
        :label="$t('Default Assignment')"
        v-model="defaultExpression"
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
      defaultExpression: null,
    }
  },
  computed: {
    title() {
      if (this.cardType == 'edit') {
        return this.$t('Edit');
      } else {
        return this.$t('Add FEEL Expression');
      }
    },
    confirmationMessage() {
      const item = this.specialAssignments[this.removeIndex].expression;
      return this.$t('Are you sure you want to delete expression {{item}}', {item: item});
    },
    specialAssignmentsList() {
      return this.specialAssignments.filter(assignment => {
        return !assignment.default;
      });
    },
    defaultExpressionIndex() {
      let defaultExpression = this.specialAssignments.filter(assignment => {
        return assignment.default;
      });
      return this.specialAssignments.indexOf(defaultExpression[0]);
    }
  },
  watch: {
    specialAssignments: {
      deep:true,
      handler() {
        this.$emit('input', this.specialAssignments);
      }
    },
    value: {
      deep: true,
      handler() {
        this.specialAssignments = this.value;
      }
    },
    defaultExpression() {
      let field;
      if (this.defaultExpression.users.length) {
        field = {
          "type" : "user",
          "name": this.defaultExpression.users[0].fullname,
          "id": this.defaultExpression.users[0].id,
        };
      } else if (this.defaultExpression.groups.length) {
        field = {
          "type" : "group",
          "name": this.defaultExpression.groups[0].name,
          "id": this.defaultExpression.groups[0].id,
        };
      }
      let byExpression = {
        type: field.type,
        assignee: field.id,
        expression: this.assignmentExpression,
        assignmentName: field.name,
        default: true,
      };
      if (this.defaultExpressionIndex) {
        this.specialAssignments[this.defaultExpressionIndex] = byExpression;
        this.$emit('input', this.specialAssignments);
      } else {
        this.specialAssignments.push(byExpression);
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
            "id": this.assignedExpression.groups[0].id,
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
    }
  },
  mounted() {
    this.specialAssignments = this.value;
  }
}
</script>

<style scoped>
  .striped {
    background-color: rgba(0,0,0,.05);
  }
</style>