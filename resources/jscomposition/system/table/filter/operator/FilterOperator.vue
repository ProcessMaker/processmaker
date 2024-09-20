<template>
  <div class="tw-space-y-2 tw-text-xs">
    <slot name="header"></slot>

    <Dropdown
      v-model="operator"
      @change="onChangeOperator"
      :options="operatorsModel">
    </Dropdown>

    <component
      placeholder="Type value"
      class="tw-flex-1"
      :is="operatorType?.component()"
      @change="onChangeValue" />
  </div>
</template>
<script>
import { Dropdown } from "../../../../base";
import { defineComponent, computed, ref, onMounted } from "vue";
import { getOperatorType } from "./operatorConfig";

const defaultOperators = [
  {
    value: "=",
    label: "=",
  },
  {
    value: ">",
    label: ">",
  },
  {
    value: ">=",
    label: ">=",
  },
  {
    value: "<",
    label: ">",
  },
  {
    value: "<=",
    label: "<=",
  },
  {
    value: "between",
    label: "between",
  },
  {
    value: "in",
    label: "in",
  },
  {
    value: "contains",
    label: "contains",
  },
  {
    value: "regex",
    label: "regex",
  },
];

export default defineComponent({
  components: {
    Dropdown,
  },
  props: {
    operators: {
      type: Array,
      default: () => [],
    },
    type: {
      type: String,
      default: () => "string", // string , number , datetime
    },
  },
  setup(props, { emit }) {
    const operatorsModel = ref(defaultOperators);

    const operator = ref({
      value: "=",
      label: "=",
    });

    const operatorType = ref();

    const onChangeOperator = (val) => {
      operatorType.value = getOperatorType(val.value, props.type);
    };

    // Get the values of the input, datetime, between etc and emit the change to the parent
    const onChangeValue = (e) => {
      emit("change", {
        operator: operator.value.value,
        value: e,
      });
    };

    onMounted(() => {
      //Selecting the operators to show in dropdown
      operatorsModel.value = defaultOperators.filter((op) => {
        if (!props.operators.length) {
          return op;
        }

        return props.operators.includes(op.value);
      });

      // Load first operator with its components
      operatorType.value = getOperatorType(
        operatorsModel.value[0].value,
        props.type
      );
    });

    return {
      operator,
      operatorType,
      operatorsModel,
      onChangeOperator,
      onChangeValue,
    };
  },
});
</script>
