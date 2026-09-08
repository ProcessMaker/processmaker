export const CHART_COLORS = {
  pass: '#0CA442',
  redact: '#EC8E00',
  block: '#EC5962',
  info: '#2773F3',
  muted: '#728092',
  completed: '#0CA442',
  error: '#EC5962',
};

export const doughnutOptions = {
  legend: {
    display: true,
    position: 'bottom',
  },
  maintainAspectRatio: false,
  responsive: true,
  cutoutPercentage: 60,
  layout: {
    padding: 4,
  },
};

export const horizontalBarOptions = (valueSuffix = '') => ({
  legend: {
    display: false,
  },
  maintainAspectRatio: false,
  responsive: true,
  layout: {
    padding: {
      right: 12,
    },
  },
  scales: {
    xAxes: [{
      ticks: {
        beginAtZero: true,
        callback: (value) => `${value}${valueSuffix}`,
      },
    }],
    yAxes: [{
      gridLines: {
        display: false,
      },
      afterFit: (scale) => {
        scale.width = Math.min(scale.width, 140);
      },
    }],
  },
});

export const stackedBarOptions = {
  legend: {
    display: true,
    position: 'bottom',
  },
  maintainAspectRatio: false,
  responsive: true,
  layout: {
    padding: 4,
  },
  scales: {
    xAxes: [{ stacked: true }],
    yAxes: [{
      stacked: true,
      ticks: { beginAtZero: true },
    }],
  },
};

export const lineOptions = {
  legend: {
    display: true,
    position: 'bottom',
  },
  maintainAspectRatio: false,
  responsive: true,
  layout: {
    padding: 4,
  },
  scales: {
    yAxes: [{
      ticks: { beginAtZero: true },
    }],
  },
};

export const chartBoxStyles = {
  width: '100%',
  height: '220px',
  position: 'relative',
};
