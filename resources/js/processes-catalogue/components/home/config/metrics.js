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
      percentage: metric.metric_value_unit,
      ...mData,
    };
  });

  return response;
};

export const stagesColors = ["red", "indigo", "sky", "white", "purple", "emerald", "yellow", "indigo", "pink", "orange", "teal", "violet", "fuchsia", "rose", "sky", "lime", "cyan", "gray", "black", "white"];

export const buildStages = (stages) => stages.map((stage, index) => ({
  id: stage.stage_id,
  body: stage.percentage_format,
  header: stage.stage_name,
  content: stage.agregation_sum,
  helper: stage.agregation_count,
  percentage: stage.percentage || 100,
  color: stagesColors.at(index),
}));
