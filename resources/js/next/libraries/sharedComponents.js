import { setGlobalVariable } from "../globalVariables";
import * as SharedComponents from "../../components/shared";

setGlobalVariable("SharedComponents", SharedComponents);

// import("../../components/shared").then((SharedComponents) => {
//   setGlobalVariable("SharedComponents", SharedComponents);
// });
