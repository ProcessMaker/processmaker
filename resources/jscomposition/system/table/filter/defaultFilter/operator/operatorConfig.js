import InputOperator from "./InputOperator.vue";
import BetweenOperator from "./BetweenOperator.vue";
import InOperator from "./InOperator.vue";
import DateOperator from "./DateOperator.vue";
import DateBetweenOperator from "./DateBetweenOperator.vue";
import OptionsOperator from "./OptionsOperator.vue";

const operatorType = [
  {
    operator: ["=", ">", "<", ">=", "<=", "contains", "regex"],
    type: ["number", "string"],
    component: () => InputOperator,
  },
  {
    operator: ["between"],
    type: ["number", "string"],
    component: () => BetweenOperator,
  },
  {
    operator: ["in"],
    type: ["number", "string"],
    component: () => InOperator,
  },
  {
    operator: [">", ">=", "<", "<="],
    type: ["datetime"],
    component: () => DateOperator,
  },
  {
    operator: ["between"],
    type: ["datetime"],
    component: () => DateBetweenOperator,
  },
  {
    operator: ["="],
    type: ["enum"],
    component: () => OptionsOperator,
  },
];

export const getOperatorType = (operator = "=", type = "string") => {
  const response = operatorType.find((e) => e.operator.includes(operator) && e.type.includes(type));

  return response;
};
