import AccessibilityMixin from "../../components/common/mixins/accessibility";
import { getGlobalVariable } from "../globalVariables";

const Vue = getGlobalVariable("Vue");

Vue.mixin(AccessibilityMixin);
