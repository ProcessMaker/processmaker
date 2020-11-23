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
        <button type="button" class="btn btn-sm btn-outline-secondary mr-2" @click="showCard = false">
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

    <draggable :element="'div'" v-model="specialAssignments" group="assignment" @start="drag=true" @end="drag=false" >
      <div v-for="(assignment, index) in specialAssignments" :key="index">
        <div class="row border-top" :class="rowCss(index)">
          <div class="col-1" style="cursor:grab">
            <span class="fas fa-arrows-alt-v"/>
          </div>
          <div class="col-6" style="cursor:grab">
            <div>{{ assignment.expression }}</div>
            <div>
              <i v-if="assignment.type == 'user'" class="fas fa-user"></i>
              <i v-else class="fas fa-users"></i> 
              {{ assignment.assignmentName }}
            </div>
          </div>
          <div class="col-1">
            <a @click="showEditOption(index)" class="fas fa-cog" style="cursor:pointer" data-cy="inspector-options-edit"/>
          </div>
          <div class="col-1">
            <a @click="showDeleteConfirmation(index)" class="fas fa-trash-alt" style="cursor:pointer" data-cy="inspector-options-remove" />
          </div>
        </div>
      </div>
    </draggable>
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
    }
  },
  watch: {
    specialAssignments: {
      deep:true,
      handler() {
        this.$emit('input', this.specialAssignments);
      }
    }
  },
  methods: {
    addSpecialAssignment(editIndex = null) {
      if (editIndex) {
        console.log('edit expression');
      } else {
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
          this.specialAssignments.push(byExpression);
        }
      }
      
      this.showCard = false;
    },
    rowCss(index) {
      return index % 2 === 0 ? 'striped' : 'bg-default';
    },
    showEditOption(index) { 
      this.cardType = 'edit';
      this.buttonLabel = this.$t('Update');
      this.editIndex = index;
      this.assignmentExpression = this.specialAssignments[index].expression;
      this.assignedExpression = this.specialAssignments[index].assignee;
      this.showCard = true;
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
    }
  },
  mounted() {
    this.specialAssignments = this.value;
  }
}
</script>