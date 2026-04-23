<template>
  <div>
    <div class="d-flex justify-content-between">
      <label class="m-0">
        {{ $t('Expressions') }}
      </label>
      <b-button
        :aria-label="$t('Add FEEL Expression')"
        class="add-button align-top d-inline rounded-0"
        variant="secondary"
        size="sm"
        @click="showAddCard()"
      >
        +
      </b-button>
    </div>
    <div class="helper-text mb-3">
      <small class="d-block">{{ $t('Expressions are evaluated top to bottom') }}</small>
    </div>

    <div
      v-if="showCard"
      class="card mb-2"
    >
      <div class="card-header">
        {{ title }}
      </div>
      <div class="card-body p-2">
        <div class="form-group">
          <label>{{ $t("FEEL Expression") }}</label>
          <textarea
            ref="specialAssignmentsInput"
            v-model="assignmentExpression"
            class="form-control special-assignment-input"
            :aria-label="$t('FEEL Expression')"
          />
          <small class="form-text text-muted">{{ $t("If the FEEL Expression evaluates to true then") }}</small>
        </div>

        <div class="form-group">
          <select-user-group
            v-model="assignedExpression"
            :label="$t('Assign to User / Group')"
            :hide-users="false"
            :multiple="false"
            :active-tasks-count="true"
          />
        </div>
      </div>
      <div class="card-footer text-right p-2">
        <button
          type="button"
          class="btn btn-sm btn-outline-secondary mr-2"
          @click="hideAddCard"
        >
          {{ $t('Cancel') }}
        </button>
        <button
          type="button"
          class="btn btn-sm btn-secondary"
          @click="addSpecialAssignment(editIndex)"
        >
          {{ buttonLabel }}
        </button>
      </div>
    </div>

    <div v-if="showConfirmationCard">
      <div class="card mb-3 bg-danger text-white text-right">
        <div
          class="card-body p-2"
          v-html="confirmationMessage"
        />
        <div class="card-footer text-right p-2">
          <button
            type="button"
            class="btn btn-sm btn-light mr-2"
            @click="showConfirmationCard = false"
          >
            {{ $t('Cancel') }}
          </button>
          <button
            type="button"
            class="btn btn-sm btn-danger"
            @click="deleteExpression()"
          >
            {{ $t('Delete') }}
          </button>
        </div>
      </div>
    </div>

    <draggable
      v-model="specialAssignmentsList"
      :element="'div'"
      group="assignment"
      @start="drag=true"
      @end="drag=false"
    >
      <div
        v-for="(assignment, index) in specialAssignmentsList"
        :key="index"
        :class="rowCss(index)"
        class="row border-bottom py-2 assignment-list"
      >
        <div class="d-flex col-12">
          <div
            class="col-1 p-0"
            style="cursor:grab"
          >
            <span class="fas fa-arrows-alt-v" />
          </div>
          <div
            class="col-9 p-0"
            style="cursor:grab"
          >
            <div class="displayed-expression text-truncate">
              {{ assignment.expression }}
            </div>
            <div>
              <i
                v-if="assignment.type == 'user'"
                class="fas fa-user"
              />
              <i
                v-else
                class="fas fa-users"
              />
              {{ assignment.assignmentName }}
            </div>
          </div>
          <div class="col-1 p-0 pr-3">
            <a
              class="fas fa-cog text-dark"
              style="cursor:pointer"
              data-cy="inspector-options-edit"
              @click="showEditCard(index)"
            />
          </div>
          <div class="col-1 p-0">
            <a
              class="fas fa-trash-alt text-dark"
              style="cursor:pointer"
              data-cy="inspector-options-remove"
              @click="showDeleteConfirmation(index)"
            />
          </div>
        </div>
      </div>
    </draggable>

    <div class="form-group">
      <select-user-group
        v-model="defaultAssignment"
        :label="$t('Default Assignment')"
        :hide-users="false"
        :multiple="false"
        :helper="$t('If no evaluations are true')"
        :active-tasks-count="true"
      />
    </div>
  </div>
</template>

<script>
import draggable from "vuedraggable";

export default {
  components: {
    draggable,
  },
  props: ["value"],
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
        groups: [],
      },
    };
  },
  computed: {
    title() {
      if (this.cardType == "edit") {
        return this.$t("Edit FEEL Expression");
      }
      return this.$t("Add FEEL Expression");
    },
    confirmationMessage() {
      const item = this.specialAssignments[this.removeIndex].expression;
      return this.$t("Are you sure you want to delete expression {{item}}", { item });
    },
    specialAssignmentsList: {
      get() {
        return this.specialAssignments.filter((assignment) => !assignment.default);
      },
      set(value) {
        this.specialAssignments = value;
      },
    },
    defaultAssignmentIndex() {
      const defaultAssignment = this.specialAssignments.filter((assignment) => assignment.default);
      const index = this.specialAssignments.indexOf(defaultAssignment[0]);
      return index >= 0 ? index : null;
    },
  },
  watch: {
    specialAssignments: {
      deep: true,
      handler() {
        this.setDefaultAssignmentToEndOfArray();
        this.$emit("input", this.specialAssignments);
      },
    },
    value: {
      deep: true,
      handler() {
        this.specialAssignments = this.value;
      },
    },
    defaultAssignment: {
      deep: true,
      handler() {
        if (this.defaultAssignment.users.length === 0 && this.defaultAssignment.groups.length === 0) {
          return;
        }
        let field;
        if (this.defaultAssignment.users.length && Object.keys(this.defaultAssignment.users[0]).length) {
          const name = this.defaultAssignment.users[0].fullname ? this.defaultAssignment.users[0].fullname : this.defaultAssignment.users[0].assignmentName;
          const id = this.defaultAssignment.users[0].id ? this.defaultAssignment.users[0].id : this.defaultAssignment.users[0].assignee;
          field = {
            type: "user",
            name,
            id,
          };
        } else if (this.defaultAssignment.groups.length && Object.keys(this.defaultAssignment.groups[0]).length) {
          const name = this.defaultAssignment.groups[0].name ? this.defaultAssignment.groups[0].name : this.defaultAssignment.groups[0].assignmentName;
          let id;
          if (this.defaultAssignment.groups[0].id) {
            if (this.defaultAssignment.groups[0].id.includes("group")) {
              id = this.defaultAssignment.groups[0].id.replace("group-", "");
            } else {
              id = this.defaultAssignment.groups[0].id;
            }
          } else if (this.defaultAssignment.groups[0].assignee.includes("group")) {
            id = this.defaultAssignment.groups[0].assignee.replace("group-", "");
          } else {
            id = this.defaultAssignment.groups[0].assignee;
          }
          field = {
            type: "group",
            name,
            id,
          };
        }

        if (!field) {
          return;
        }

        const byExpression = {
          type: field.type,
          assignee: field.id,
          expression: this.assignmentExpression,
          assignmentName: field.name,
          default: true,
        };
        if (this.defaultAssignmentIndex != null) {
          this.specialAssignments[this.defaultAssignmentIndex] = byExpression;
          this.$emit("input", this.specialAssignments);
        } else {
          this.specialAssignments.push(byExpression);
        }
      },
    },
  },
  mounted() {
    this.specialAssignments = this.value;
    this.loadDefaultAssignment();
  },
  methods: {
    addSpecialAssignment(editIndex = null) {
      let field;
      if (this.assignedExpression.users.length) {
        field = {
          type: "user",
          name: this.assignedExpression.users[0].fullname,
          id: this.assignedExpression.users[0].id,
        };
      } else if (this.assignedExpression.groups.length) {
        let { id } = this.assignedExpression.groups[0];
        if (this.assignedExpression.groups[0].id) {
          id = this.assignedExpression.groups[0].id.replace("group-", "");
        }
        field = {
          type: "group",
          name: this.assignedExpression.groups[0].name,
          id,
        };
      }
      const byExpression = {
        type: field.type,
        assignee: field.id,
        expression: this.assignmentExpression,
        assignmentName: field.name,
      };

      if (byExpression.expression) {
        if (editIndex !== null) {
          if (byExpression.assignee == null) {
            byExpression.assignee = this.specialAssignments[editIndex].assignee;
            byExpression.assignmentName = this.specialAssignments[editIndex].assignmentName;
          }
          this.specialAssignments[editIndex] = byExpression;
          this.$emit("input", this.specialAssignments);
        } else {
          this.specialAssignments.push(byExpression);
        }
      }
      this.hideAddCard();
    },
    rowCss(index) {
      return index % 2 === 0 ? "striped" : "bg-default";
    },
    showEditCard(index) {
      this.showCard = true;
      this.cardType = "edit";
      this.buttonLabel = this.$t("Update");
      this.editIndex = index;
      this.assignmentExpression = this.specialAssignments[index].expression;
      const assignee = {
        users: [],
        groups: [],
      };
      if (this.specialAssignments[index].type == "user") {
        assignee.users.push(this.specialAssignments[index].assignee);
      } else if (this.specialAssignments[index].type == "group") {
        assignee.groups.push(parseInt(this.specialAssignments[index].assignee.replace("group-", "")));
      }

      this.assignedExpression = assignee;
    },
    showDeleteConfirmation(index) {
      this.removeIndex = index;
      this.showConfirmationCard = true;
    },
    showAddCard() {
      this.buttonLabel = this.$t("Add");
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
      const index = this.specialAssignments.findIndex((item) => item.default == true);
      const length = this.specialAssignments.length - 1;
      if (index == -1) {
        return;
      }
      if (index != length) {
        this.specialAssignments.push(this.specialAssignments.splice(index, 1)[0]);
      }
    },
    loadDefaultAssignment() {
      const defaultAssignment = this.specialAssignments.filter((assignment) => assignment.default);
      if (defaultAssignment.length == 0) {
        return;
      }
      if (defaultAssignment[0].type == "user") {
        this.defaultAssignment.users.push(defaultAssignment[0]);
      } else if (defaultAssignment[0].type == "group") {
        if (typeof defaultAssignment[0].assignee !== "number") {
          defaultAssignment[0].assignee = defaultAssignment[0].assignee.replace("group-", "");
        }
        this.defaultAssignment.groups.push(defaultAssignment[0]);
      }
    },
  },
};
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
