import { setGlobalVariable } from "../globalVariables";

import("../../components/shared").then((SharedComponents) => {
  setGlobalVariable("SharedComponents", SharedComponents);
});
