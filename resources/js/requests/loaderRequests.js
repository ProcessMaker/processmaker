import { setupMain } from "../next/setupMain";

setupMain();

window.ProcessMaker.packages = window.temporal?.packages || [];
window.packages = window.ProcessMaker.packages;
window.Processmaker.user = window.temporal.user;
window.Processmaker.status = window.temporal.status;
window.ProcessMaker.advanced_filter = window.temporal.advanced_filter;
window.Processmaker.defaultColumns = window.temporal.defaultColumns;