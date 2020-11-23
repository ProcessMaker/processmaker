<template>
  <div>
    <div class="d-flex justify-content-between align-items-center mb-3">
      <label class="m-0">
        {{ $t('Expressions') }}
        <small class="d-block">{{ $t('Expressions are evaluated top to bottom') }}</small>
      </label>
      <b-button class="" variant="secondary" size="sm" @click="showCard = true">+</b-button>
    </div>

    <div v-if="showCard" class="card mb-2">
      <div class="card-header">
        {{ $t('Add FEEL Expression') }}
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
        <button type="button" class="btn btn-sm btn-outline-secondary mr-2" @click="editIndex=null">
          {{ $t('Cancel') }}
        </button>
        <button type="button" class="btn btn-sm btn-secondary" @click="saveSpecialAssignment()">
          {{ $t('Add') }}
        </button>
      </div>
    </div>

    <draggable @update="updateSort" :element="'div'" v-model="specialAssignments" group="assignment" @start="drag=true" @end="drag=false" >
      <div v-for="(assignment, index) in specialAssignments" :key="index">
        <div class="row border-top" :class="rowCss(index)">
          <div class="col-1" style="cursor:grab">
            <span class="fas fa-arrows-alt-v"/>
          </div>
          <div class="col-6" style="cursor:grab">
            <div>{{ assignment.expression }}</div>
            <div>
              <i v-if="assignment.assignee.type == 'user'" class="fas fa-user"></i>
              <i v-else class="fas fa-users"></i> 
              {{ assignment.assignee.name }}
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


<!-- 
      <div class="form-group special-assignment-header">
          <label>{{ $t("Assign by Expression Use a rule to assign this Task conditionally") }}</label>
          <button type="button"
                  @click="addingSpecialAssignment = true"
                  class="float-right btn-special-assignment-action btn btn-secondary btn-sm"
                  :class="{inactive: addingSpecialAssignment}">
              <i class="fa fa-plus"/> {{ $t("Rule") }}
          </button>
      </div>

      <div class="special-assignment-wrapper" ref="specialAssignmentWrapper" @transitionend="transitionEnded">
          <div class="special-assignment-form">

              <div class="form-group">
                  <label>{{ $t("Expression") }}</label>
                  <input class="form-control" ref="specialAssignmentsInput" type="text"
                          v-model="assignmentExpression">
                  <small class="form-text text-muted">{{$t("Enter the expression to evaluate Task assignment")}}</small>
              </div>

              <div class="form-group">
                  <label>{{ $t("Select the Task assignee") }}</label>
                  <select
                      ref="specialAssignmentsDropDownList"
                      class="form-control"
                      v-model="typeAssignmentExpression">
                      <option value=""></option>
                      <option v-for="type in assignmentTypes" :key="type.value" :value="type.value">{{
                          $t(type.label) }}
                      </option>
                  </select>
              </div>

              <select-user-group
                  v-if="showSpecialAssignOneUserGroup"
                  ref="userGroupAssignedSpecial"
                  :label="$t('Assigned Users/Groups')"
                  v-model="assignedExpression"
                  :hide-users="hideUsersAssignmentExpression"/>

              <user-by-id
                  v-if="showSpecialAssignUserById"
                  :label="$t('Variable Name of User ID Value')"
                  v-model="specialAssignedUserID"
              ></user-by-id>

              <div class="form-group form-group-actions">
                  <button
                      type="button"
                      @click="addingSpecialAssignment = false"
                      class="btn-special-assignment-action btn-special-assignment-close btn btn-outline-secondary btn-sm">
                      {{ $t("Cancel") }}
                  </button>
                  <button
                      type="button"
                      @click="saveSpecialAssignment"
                      class="btn-special-assignment-action btn btn-secondary btn-sm">
                      {{ $t("Save") }}
                  </button>
              </div>

          </div>
      </div>

      <div v-for="(row, index) in specialAssignmentsData"
              class="list-group-item list-group-item-action pt-0 pb-0"
              :class="{'bg-primary': false}">
          <template>
              <div class="special-assignment-section">
                  <div class="special-assignment-value" :title="row.expression">
                      <strong>{{ $t(row.expression) }}</strong></div>
                  <div class="btn-special-assignment-delete" @click="removeSpecialAssignment(row)">
                      <i class="fa fa-trash"></i>
                  </div>
              </div>
              <div class="special-assignment-section">
                  <small class="special-assignment-value">{{ $t("Assigned to") }}
                      <strong v-if="row.type == 'requester'">{{$t(row.type)}}</strong>
                      <strong v-if="row.type == 'previous_task_assignee'">{{$t('Previous Task Assignee')}}</strong>
                      <strong v-if="row.type == 'user_by_id'">{{$t('User with ID') }} {{row.assignee}}</strong>
                      <strong v-else>{{$t(row.assignmentName)}}</strong>
                  </small>
              </div>
          </template>
      </div> -->
  </div>
</template>

<script>
import draggable from 'vuedraggable';

export default {
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
    }
  },
  methods: {
    updateSort() {
      console.log('update sort');
      // this.jsonData = JSON.stringify(this.optionsList);
      // this.$emit('change', this.dataObjectOptions);
    },
    saveSpecialAssignment() {
      let field;
      console.log('assignedExpression', this.assignedExpression);
      if (this.assignedExpression.users) {
        field = {
          "type" : "user",
          "name": this.assignedExpression.users[0].fullname
        };
      } else if (this.assignedExpression.groups) {
        field = {
          "type" : "group",
          "name": this.assignedExpression.groups[0].name
        };
      }
      let byExpression = {
        // type: this.typeAssignmentExpression,
        assignee: field,
        expression: this.assignmentExpression
      };

      console.log('save special assignment', byExpression);
      if (byExpression.expression) {
        this.specialAssignments.push(byExpression);
        this.assignmentRulesSetter();
        let assignmentName = "";
        console.log('specialAssignments', this.specialAssignments);
        // if (this.typeAssignmentExpression === "user_group" || this.typeAssignmentExpression === "self_service") {
        //   this.$refs.userGroupAssignedSpecial.content.forEach(item => {
        //     assignmentName += assignmentName ? ", " : "";
        //     assignmentName += item.fullname || item.name;
        //   });
        // }

        // this.specialAssignmentsData.push({
        //   type: this.typeAssignmentExpression,
        //   assignee: this.assignedExpression || this.specialAssignedUserID || "",
        //   expression: this.assignmentExpression,
        //   assignmentName,
        // });

      //   this.assignmentExpression = "";
      //   this.typeAssignmentExpression = "";
      //   this.assignedExpression = null;
      //   this.specialAssignedUserID = null;
      // }

      
      }
      // this.addingSpecialAssignment = false;
      this.showCard = false;
    },
    assignmentRulesSetter () {
      this.$set(this.node, "assignmentRules", JSON.stringify(this.specialAssignments));
    },
    rowCss(index) {
      return index % 2 === 0 ? 'striped' : 'bg-default';
    },
    showEditOption(index) {
      console.log('show edit options', index);
    },
    showDeleteConfirmation(index) {
      console.log('show delete confirmation', index);
    }
  }
}
</script>