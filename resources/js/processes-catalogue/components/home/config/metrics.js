export const defaultMetrics = [{
  id: 1,
  icon: "fas fa-reply",
  active: false,
  color: "blue",
},
{
  id: 2,
  icon: "fas fa-user",
  color: "amber",
  active: false,
},
{
  id: 3,
  icon: "fas fa-user",
  color: "green",
  active: false,
}];

export const buildMetrics = (metrics) => {
  const response = metrics.map((metric) => {
    const mData = defaultMetrics.find((m) => m.id === metric.id);
    if (!mData) {
      return defaultMetrics[defaultMetrics.length - 1];
    }
    return {
      id: metric.metric_id,
      body: metric.metric_description,
      header: metric.metric_count_description,
      content: metric.metric_value,
      percentage: metric.metric_value_unit || 100,
      ...mData,
    };
  });

  return response;
};

export const stagesColors = [
  "red", "indigo", "sky", "white", "purple", "emerald", "yellow",
  "indigo", "pink", "orange", "teal", "violet", "fuchsia", "rose",
  "sky", "lime", "cyan", "gray", "black", "white",
];

export const buildStages = (stages) => stages.map((stage, index) => ({
  id: stage.stage_id,
  body: stage.stage_name,
  header: stage.agregation_count,
  content: stage.agregation_sum,
  helper: stage.agregation_sum,
  percentage: stage.percentage || 100,
  color: stagesColors.at(index),
}));

export const updateActiveStage = (arrayStages, stage) => {
  arrayStages.forEach((s) => {
    s.active = false;
  });

  const stg = arrayStages.find((s) => s.id === stage?.id);
  if (stg) {
    stg.active = true;
  }
};

export const buildAdvancedFilter = (stages) => {
  const stage = stages.find((item) => item.active);

  if (stage.id === "in_progress") {
    return [
      {
        subject: {
          type: "Status",
        },
        operator: "=",
        value: "In Progress",
      },
    ];
  }

  if (stage.id === "completed") {
    return [
      {
        subject: {
          type: "Status",
        },
        operator: "=",
        value: "Completed",
      },
    ];
  }

  return [{
    subject: {
      type: "Stage",
    },
    operator: "=",
    value: stage.id,
  }];
};

export const verifyResponseMetrics = (response) => {
  let isValid = true;
  response.forEach((metric) => {
    if (!metric.id) {
      isValid = false;
    }
    if (!metric.body) {
      isValid = false;
    }
    if (!metric.color) {
      isValid = false;
    }
    if (typeof metric.content !== "number") {
      isValid = false;
    }
    if (!metric.header) {
      isValid = false;
    }
    if (!metric.icon) {
      isValid = false;
    }
    if (!metric.percentage) {
      isValid = false;
    }
    if (typeof metric.active !== "boolean") {
      isValid = false;
    }
  });
  return isValid;
};
