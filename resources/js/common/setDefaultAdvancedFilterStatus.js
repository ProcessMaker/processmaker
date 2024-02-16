import { get } from "lodash";

export default (status, ignoreSavedFilter = false) => {
  let advancedFilter = get(window, 'ProcessMaker.advanced_filter.filters', []);
  if (ignoreSavedFilter) {
    // Remove any Status filters that might be set by the user
    advancedFilter = advancedFilter.filter(f => f.subject?.type !== "Status");
  } else {
    if (advancedFilter.some(f => f.subject?.type === "Status")) {
      // Already has a status filter set by the user
      return;
    }
  }
  advancedFilter.push({
    subject: {
      type: "Status"
    },
    operator: "=",
    value: status,
    _column_field: "status",
    _column_label: "Status"
  });
  window.ProcessMaker.advanced_filter.filters = advancedFilter;

}