import { get } from "lodash";

export default (status, ignoreSavedFilter = false, requester = null) => {
  let advancedFilter = get(window, 'ProcessMaker.advanced_filter.filters', []);
  if (ignoreSavedFilter) {
    // Remove any Status filters that might be set by the user
    advancedFilter = advancedFilter.filter(f => f.subject?.type !== "Status" && f.subject?.value !== 'user_id');
  } else if (advancedFilter.some(f => f.subject?.type === "Status")) {
    // Already has a status filter set by the user
    return;
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

  if (requester) {
    advancedFilter.push({
      subject: {
        type: "Field",
        value: 'user_id'
      },
      operator: "=",
      value: requester.id,
      _column_field: "requester",
      _column_label: "Requester",
      _display_value: requester.username,
    });
  }

  window.ProcessMaker.advanced_filter.filters = advancedFilter;

}