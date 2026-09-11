import { setupMain } from "../../next/setupMain";
import monaco from "../../next/monaco";

setupMain();
monaco();

window.ProcessMaker.packages = window.temporal?.packages || [];
window.packages = window.ProcessMaker.packages;
