export default (isStatusCompletedList = false) => {
    const columns = [
        {
          label: "Case #",
          field: "case_number",
          sortable: true,
          default: true,
          width: 84,
          fixed_width: 84,
          filter_subject: {
            type: "Relationship",
            value: "processRequest.case_number",
          },
          order_column: "process_requests.case_number",
        },
        {
          label: "Case title",
          field: "case_title",
          name: "__slot:case_number",
          sortable: true,
          default: true,
          width: 419,
          fixed_width: 419,
          truncate: true,
          filter_subject: {
            type: "Relationship",
            value: "processRequest.case_title",
          },
          order_column: "process_requests.case_title",
        },
        {
          label: "Priority",
          field: "is_priority",
          sortable: false,
          default: true,
          fixed_width: 20,
          resizable: false,
        },
        {
          label: "Task",
          field: "element_name",
          sortable: true,
          default: true,
          width: 135,
          fixed_width: 135,
          truncate: true,
          filter_subject: { value: "element_name" },
          order_column: "element_name",
        },
        {
          label: "Status",
          field: "status",
          sortable: true,
          default: true,
          width: 183,
          fixed_width: 183,
          filter_subject: { type: "Status" },
        },
        {
          label: "Due date",
          field: "due_at",
          format: "datetime",
          sortable: true,
          default: true,
          width: 200,
          fixed_width: 200,
        },
        {
          label: "Draft",
          field: "draft",
          sortable: false,
          default: true,
          hidden: true,
          width: 40,
        },
      ];
      if (isStatusCompletedList) {
        columns.push({
          label: "Completed",
          field: "completed_at",
          format: "datetime",
          sortable: true,
          default: true,
          width: 200,
          fixed_width: 200,
        });
      }
      
      return columns;
};
