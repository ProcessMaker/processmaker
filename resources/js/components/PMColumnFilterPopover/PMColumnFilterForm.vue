<template>
  <div class="pm-filter-form">
    <b-form>
      <b-form-group>
        <b-button variant="light"
                  @click="onSortAscending"
                  :pressed.sync="viewToggleAsc"
                  squared
                  size="sm">
          <PMColumnFilterIconAsc></PMColumnFilterIconAsc>
          {{$t("Sort Ascending")}}
        </b-button>
        <b-button variant="light"
                  @click="onSortDescending"
                  :pressed.sync="viewToggleDesc"
                  squared
                  size="sm">
          <PMColumnFilterIconDesc></PMColumnFilterIconDesc>
          {{$t("Sort Descending")}}
        </b-button>
      </b-form-group>

      <div class="pm-filter-form-area" ref="pmFilterFormArea">
        <template v-for="(item, index) in items">
          <b-form-group :key="'buttonRemove' + index">
            <div class="d-flex justify-content-between align-items-center">
              <p class="mb-0">{{$t("Filter the column")}}:</p>
              <b-button variant="light"
                        size="sm"
                        class="pm-filter-form-button"
                        @click="onClickButtonRemove(item,index)">
                <PMColumnFilterIconMinus></PMColumnFilterIconMinus>
              </b-button>
            </div>
          </b-form-group>

          <b-form-group :key="'operator' + index">
            <b-form-select v-model="item.operator" 
                           :options="getOperators()"
                           @change="onChangeOperator(item,index)"
                           size="sm">
            </b-form-select>
          </b-form-group>

          <b-form-group :key="'value' + index">
            <b-form-input v-if="switchOperator('BFormInput',item)"
                          v-model="item.value" 
                          placeholder="Type value"
                          size="sm">
            </b-form-input>
            <PMColumnFilterOpBetween v-if="switchOperator('PMColumnFilterOpBetween',item)"
                                     v-model="item.value">
            </PMColumnFilterOpBetween>
            <PMColumnFilterOpIn v-if="switchOperator('PMColumnFilterOpIn',item)"
                                v-model="item.value">
            </PMColumnFilterOpIn>
          </b-form-group>

          <b-form-group :key="'logical' + index"
                        v-if="switchLogical(index)">
            <b-form-select v-model="item.logical" 
                           :options="getLogicals()"
                           class="pm-filter-form-logical-operators"
                           @change="onChangeLogicalOp(item,index)"
                           size="sm">
            </b-form-select>
          </b-form-group>
        </template>
      </div>

      <b-form-group>
        <b-button variant="light"
                  size="sm"
                  class="pm-filter-form-button"
                  @click="onClickButtonAdd">
          <PMColumnFilterIconPlus></PMColumnFilterIconPlus>
        </b-button>
      </b-form-group>

      <b-form-group>
        <b-button variant="outline-secondary"
                  size="sm"
                  @click="onCancel">
          {{$t("Cancel")}}
        </b-button>
        <span>&nbsp;</span>
        <b-button variant="outline-secondary"
                  size="sm"
                  @click="onClear">
          {{$t("Clear")}}
        </b-button>
        <span>&nbsp;</span>
        <b-button size="sm" 
                  @click="onApply">
          {{$t("Apply")}}
        </b-button>
      </b-form-group>
    </b-form>

  </div>
</template>

<script>
  import PMColumnFilterIconAsc from "./PMColumnFilterIconAsc"
  import PMColumnFilterIconDesc from "./PMColumnFilterIconDesc"
  import PMColumnFilterIconMinus from "./PMColumnFilterIconMinus"
  import PMColumnFilterIconPlus from "./PMColumnFilterIconPlus"
  import PMColumnFilterOpBetween from "./PMColumnFilterOpBetween"
  import PMColumnFilterOpIn from "./PMColumnFilterOpIn"

  export default {
    components: {
      PMColumnFilterIconAsc,
      PMColumnFilterIconDesc,
      PMColumnFilterIconMinus,
      PMColumnFilterIconPlus,
      PMColumnFilterOpBetween,
      PMColumnFilterOpIn
    },
    props: ["type", "value"],
    data() {
      return {
        items: [],
        viewToggleAsc: true,
        viewToggleDesc: false,
        viewItemsChanged: false
      };
    },
    created() {
    },
    mounted() {
      this.addItem(0);
    },
    updated() {
      this.updatedScroll();
    },
    watch: {
      items() {
        this.viewItemsChanged = true;
      }
    },
    methods: {
      onSortAscending() {
        this.viewToggleDesc = false;
        this.$emit("onSortAscending", "asc");
      },
      onSortDescending() {
        this.viewToggleAsc = false;
        this.$emit("onSortDescending", "desc");
      },
      onApply() {
        let json = this.getValues();
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
      onChangeOperator() {
      },
      onChangeLogicalOp() {
      },
      setValues(json) {
        console.log(json);
      },
      getValues() {
        let json = JSON.parse(JSON.stringify(this.items));
        return this.transformToPmSyntax(json);
      },
      transformToPmSyntax(json) {
        //I did not modify the assignment of 'or' with the 'root' array; later on, 
        //'or' will change its reference, so the values of 'root' will be retained.
        let root = [];
        let or = root;
        for (let i in json) {
          or.push(json[i]);
          if (json[i].logical === "or") {
            json[i].or = [];
            or = json[i].or;
          }
          delete json[i].logical;
        }
        return root;
      },
      addItem(index) {
        let item = {
          subject: {
            type: this.type,
            value: this.value
          },
          operator: "=",
          value: "",
          logical: "and"
        };
        this.items.splice(index, 0, item);
      },
      removeItem(index) {
        this.items.splice(index, 1);
      },
      getOperators() {
        return [
          {value: "=", text: "="},
          {value: "<", text: "<"},
          {value: "<=", text: "<="},
          {value: ">", text: ">"},
          {value: ">=", text: ">="},
          {value: "between", text: "between"},
          {value: "in", text: "in"},
          {value: "contains", text: "contains"},
          {value: "regex", text: "regex"}
        ];
      },
      getLogicals() {
        return [
          {value: "and", text: "and"},
          {value: "or", text: "or"}
        ];
      },
      switchOperator(type, item) {
        let sw;
        switch (type) {
          case "BFormInput":
            sw = ["", "=", "<", "<=", ">", ">=", "contains", "regex"].includes(item.operator);
            if (sw && !this.isScalar(item.value)) {
              item.value = "";
            }
            return sw;
          case "PMColumnFilterOpBetween":
            sw = ["between"].includes(item.operator);
            if (sw && this.isScalar(item.value)) {
              item.value = [];
            }
            return sw;
          case "PMColumnFilterOpIn":
            sw = ["in"].includes(item.operator);
            if (sw && this.isScalar(item.value)) {
              item.value = [];
            }
            return sw;
        }
        return false;
      },
      switchLogical(index) {
        let sw = this.items.length > 1 && index + 1 !== this.items.length;
        return sw;
      },
      isScalar(value) {
        return ["string", "number", "boolean", "undefined", "symbol"].includes(typeof value);
      },
      updatedScroll() {
        if (this.viewItemsChanged === true) {
          let myDiv = this.$refs.pmFilterFormArea;
          myDiv.scrollTop = 185 * (this.items.length);
          this.viewItemsChanged = false;
        }
      }
    }
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
