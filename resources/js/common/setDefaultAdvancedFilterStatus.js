import { get } from "lodash";

/**
 * Whether this advanced filter entry targets the task Status column (saved or live form).
 * Used so we do not add a duplicate default "In Progress" when a status filter already exists
 * under Field/status or _column_field shape.
 *
 * @param {object} f
 * @returns {boolean}
 */
export function isTaskStatusColumnFilter(f) {
  if (!f || typeof f !== "object") {
    return false;
  }
  return (
    f._column_field === "status"
    || f.subject?.type === "Status"
    || (f.subject?.type === "Field" && f.subject?.value === "status")
  );
}

export default (status, ignoreSavedFilter = false, requester = null) => {
  let advancedFilter = get(window, "ProcessMaker.advanced_filter.filters", []);
  if (ignoreSavedFilter) {
    // Remove any Status filters that might be set by the user
    advancedFilter = advancedFilter.filter(
      (f) => !isTaskStatusColumnFilter(f) && f.subject?.value !== "user_id",
    );
  } else if (advancedFilter.some(isTaskStatusColumnFilter)) {
    // Already has a status filter set by the user
    return;
  }

  // Same subject shape as PMColumnFilterForm (Field + value) so merge with user rows works
  advancedFilter.push({
    subject: {
      type: "Field",
      value: "status",
    },
    operator: "=",
    value: status,
    _column_field: "status",
    _column_label: "Status",
  });

  if (requester) {
    advancedFilter.push({
      subject: {
        type: "Field",
        value: "user_id",
      },
      operator: "=",
      value: requester.id,
      _column_field: "requester",
      _column_label: "Requester",
      _display_value: requester.username,
    });
  }

  window.ProcessMaker.advanced_filter.filters = advancedFilter;
};
