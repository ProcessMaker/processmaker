export const defaultMetrics = [{
  id: 1,
  icon: "fas fa-reply",
  active: true,
  className: "tw-bg-white hover:tw-bg-gray-200",
},
{
  id: 2,
  icon: "fas fa-user",
  className: "tw-bg-amber-100 hover:tw-bg-amber-200",
  active: false,
},
{
  id: 3,
  icon: "fas fa-user",
  className: "tw-bg-green-100 hover:tw-bg-green-200",
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
