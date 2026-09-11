import { setupMain } from "../../next/setupMain";

setupMain();

window.ProcessMaker.packages = window.temporal?.packages || [];
window.packages = window.ProcessMaker.packages;
