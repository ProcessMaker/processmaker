<template>
  <div class="tw-space-y-2 tw-text-xs">
    <slot name="header" />

    <Dropdown
      v-model="operator"
      :options="operatorsModel"
      @change="onChangeOperator" />

    <component
      :is="operatorType?.component()"
      :value="modelValue"
      placeholder="Type value"
      class="tw-flex-1"
      @change="onChangeValue" />
  </div>
</template>
<script>
import {
  defineComponent, ref, onMounted,
} from "vue";
import { Dropdown } from "../../../../../base";
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
    value: {
      type: Object,
      default: () => null,
    },
  },
  setup(props, { emit }) {
    const operatorsModel = ref(defaultOperators);
    const modelValue = ref(props.value?.value);

    const operator = ref();

    const operatorType = ref();

    const onChangeOperator = (val) => {
      operatorType.value = getOperatorType(val.value, props.type);

      // Reset the model value when change the operator
      modelValue.value = null;

      emit("change", {
        operator: operator.value.value,
        value: modelValue.value,
      });
    };

    // Get the values of the input, datetime, between etc and emit the change to the parent
    const onChangeValue = (e) => {
      modelValue.value = e;

      emit("change", {
        operator: operator.value.value,
        value: e,
      });
    };

    const loadValue = (valueOperator, type) => {
      operator.value = operatorsModel.value.find((e) => valueOperator === e.value);

      // Load first operator with its components
      operatorType.value = getOperatorType(
        valueOperator,
        type,
      );
    };

    const loadOperators = (operatorsInput) => {
      operatorsModel.value = defaultOperators.filter((op) => {
        if (!operatorsInput.length) {
          return op;
        }

        return operatorsInput.includes(op.value);
      });
    };

    onMounted(() => {
      loadOperators(props.operators || defaultOperators);

      operator.value = operatorsModel.value[0];

      loadValue(props.value?.operator || operator.value.value, props.type);
    });

    return {
      modelValue,
      operator,
      operatorType,
      operatorsModel,
      onChangeOperator,
      onChangeValue,
    };
  },
});
</script>
