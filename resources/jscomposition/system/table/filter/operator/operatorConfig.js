
import InputOperator from "./InputOperator.vue"
import BetweenOperator from "./BetweenOperator.vue"
import InOperator from "./InOperator.vue"
import { shallowRef } from "vue"

const operatorType = [
  {
    operator: ['=', '>', '<', '>=', '<=', 'contains', 'regex'],
    type: ['number', 'string'],
    component: () => {
      return InputOperator
    },
  },
  {
    operator: ['between'],
    type: ['number', 'string'],
    component: () => {
      return BetweenOperator
    },
  },
  {
    operator: ['in'],
    type: ['number', 'string'],
    component: () => {
      return InOperator
    },
  }
]

export const getOperatorType = (operator = '=', type = 'string') => {
  const response = operatorType.find(e => {
    return e.operator.includes(operator) && e.type.includes(type)
  })

  return response
}