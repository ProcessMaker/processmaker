<template>
  <div class="pm-filter-form">
    <b-form>
      <PMColumnFilterToggleAscDesc
        v-model="viewSort"
        @onChange="onChangeSort"
      />

      <div
        ref="pmFilterFormArea"
        class="pm-filter-form-area"
        data-cy="pmFilterFormArea"
      >
        <template v-for="(item, index) in items">
          <b-form-group :key="'buttonRemove' + index">
            <div class="d-flex justify-content-between align-items-center">
              <p class="mb-0">
                {{ $t("Filter the column:") }}
              </p>
              <b-button
                variant="light"
                size="sm"
                class="pm-filter-form-button"
                :data-cy="'buttonRemove' + index"
                @click="onClickButtonRemove(item,index)"
              >
                <PMColumnFilterIconMinus />
              </b-button>
            </div>
          </b-form-group>

          <b-form-group :key="'operator' + index">
            <b-form-select
              v-model="item.operator"
              :options="getOperators()"
              :data-cy="'operator' + index"
              size="sm"
              @change="onChangeOperator(item,index)"
            />
          </b-form-group>

          <b-form-group :key="'value' + index">
            <component
              :is="item.viewControl"
              v-model="item.value"
              :data-cy="'value' + index"
              :format-range="formatRange"
            />
          </b-form-group>

          <b-form-group
            v-if="switchLogical(index)"
            :key="'logical' + index"
          >
            <b-form-select
              v-model="item.logical"
              :options="getLogicals()"
              :data-cy="'logical' + index"
              class="pm-filter-form-logical-operators"
              size="sm"
              @change="onChangeLogicalOp(item,index)"
            />
          </b-form-group>
        </template>
      </div>

      <b-form-group>
        <b-button
          variant="light"
          size="sm"
          class="pm-filter-form-button"
          @click="onClickButtonAdd"
        >
          <PMColumnFilterIconPlus />
        </b-button>
      </b-form-group>

      <b-form-group>
        <b-button
          variant="outline-secondary"
          size="sm"
          class="pm-filter-form-button-cancel"
          @click="onCancel"
        >
          {{ $t("Cancel") }}
        </b-button>
        <span>&nbsp;</span>
        <b-button
          variant="outline-secondary"
          size="sm"
          @click="onClear"
        >
          {{ $t("Clear") }}
        </b-button>
        <span>&nbsp;</span>
        <b-button
          size="sm"
          @click="onApply"
        >
          {{ $t("Apply") }}
        </b-button>
      </b-form-group>
    </b-form>
  </div>
</template>

<script>
import * as Components from "./PMColumnFilterOp";

export default {
  components: {
    ...Components,
  },
  props: ["type", "value", "format", "formatRange", "operators", "viewConfig", "sort"],
  data() {
    return {
      items: [],
      viewSort: "asc",
      viewItemsChanged: false,
    };
  },
  watch: {
    items() {
      this.viewItemsChanged = true;
    },
    sort: {
      handler(newValue) {
        this.viewSort = newValue;
      },
      immediate: true,
    },
  },
  mounted() {
    this.addItem(0);
  },
  updated() {
    this.updatedScroll();
  },
  methods: {
    onChangeSort(value) {
      this.$emit("onChangeSort", value);
    },
    onApply() {
      const json = this.getValues();
      this.$emit("onApply", json);
    },
    onClear() {
      this.$emit("onClear");
      this.items = [];
      this.addItem(0);
    },
    onCancel() {
      this.$emit("onCancel");
    },
    onClickButtonAdd() {
      this.addItem(this.items.length);
    },
    onClickButtonRemove(item, index) {
      if (this.items.length === 1) {
        return;
      }
      this.removeItem(index);
    },
    onChangeOperator(item) {
      this.switchViewControl(item);
    },
    onChangeLogicalOp() {
    },
    setValues(json) {
      const items = this.transformToFilterSyntax(json);
      this.items = items;
    },
    getValues() {
      const json = JSON.parse(JSON.stringify(this.items));
      return this.transformToPmSyntax(json);
    },
    transformToFilterSyntax(json) {
      const items = JSON.parse(JSON.stringify(json));
      let n = items.length;
      let or = [];
      for (let i = 0; i < n; i += 1) {
        items[i].logical = "and";
        items[i].viewControl = items[i].viewControl ?? "";
        this.switchViewControl(items[i], false);

        if ("or" in items[i]) {
          // save and delete the 'or' property.
          items[i].logical = "or";
          or = items[i].or;
          delete items[i].or;

          // add the elements from the 'or' variable into 'items'.
          n += or.length;
          for (const j in or) {
            or[j].logical = "and";
            or[j].viewControl = "";
            this.switchViewControl(or[j], false);
            items.splice(i + 1, 0, or[j]);
          }
        }
      }
      return items;
    },
    /**
     * I did not modify the assignment of 'or' with the 'root' array; later on,
     * 'or' will change its reference, so the values of 'root' will be retained.
     * @param {JSON} json
     * @returns {Array}
     */
    transformToPmSyntax(json) {
      const root = [];
      let or = root;
      for (const i in json) {
        or.push(json[i]);
        if (json[i].logical === "or") {
          json[i].or = [];
          or = json[i].or;
        }
        delete json[i].logical;
        delete json[i].viewControl;
      }
      return root;
    },
    addItem(index) {
      const item = {
        subject: {
          type: this.type,
          value: this.value,
        },
        operator: "=",
        value: "",
        logical: "and",
        viewControl: "PMColumnFilterOpInput",
      };
      this.items.splice(index, 0, item);
      this.switchViewControl(item);
    },
    removeItem(index) {
      this.items.splice(index, 1);
    },
    getOperators() {
      let operators = [
        { value: "=", text: "=" },
        { value: "<", text: "<" },
        { value: "<=", text: "<=" },
        { value: ">", text: ">" },
        { value: ">=", text: ">=" },
        { value: "between", text: "between" },
        { value: "in", text: "in" },
        { value: "contains", text: "contains" },
        { value: "regex", text: "regex" },
      ];
      if (this.operators && this.operators.length > 0) {
        operators = this.operators;
      }
      return operators;
    },
    getLogicals() {
      return [
        { value: "and", text: "and" },
        { value: "or", text: "or" },
      ];
    },
    switchLogical(index) {
      const sw = this.items.length > 1 && index + 1 !== this.items.length;
      return sw;
    },
    switchViewControl(item, defaultValue) {
      let sw3 = true;
      if (defaultValue === false) {
        sw3 = false;
      }
      let sw1; let
        sw2;
      for (const i in this.viewConfig) {
        sw1 = this.viewConfig[i].type === this.format;
        sw2 = this.viewConfig[i].includes.includes(item.operator);
        if (sw1 && sw2) {
          item.viewControl = this.viewConfig[i].control;
        }
        if (sw1 && sw2 && sw3) {
          item.value = this.viewConfig[i].input;
        }
      }
    },
    updatedScroll() {
      if (this.viewItemsChanged === true) {
        const myDiv = this.$refs.pmFilterFormArea;
        myDiv.scrollTop = 185 * (this.items.length);
        this.viewItemsChanged = false;
      }
    },
  },
};
</script>

<style scoped>
  .btn{
    text-transform: none;
  }
  .pm-filter-form{
    width: 285px;
  }
  .pm-filter-form-area{
    overflow-y: scroll;
    height: 185px;
  }
  .pm-filter-form-logical-operators{
    width: 160px;
  }
  .pm-filter-form-button{
    padding: 0px;
  }
</style>
