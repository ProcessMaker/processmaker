export const dataA = {
  series: [
    {
      data: [
        21, 22, 10, 28, 16, 21, 13, 30, 21, 22, 10, 28, 16, 21, 13,
        30,
      ],
    },
  ],
  chartOptions: {
    chart: {
      height: 350,
      type: "bar",
      events: {
        click: function (chart, w, e) {
          // console.log(chart, w, e)
        },
      },
    },
    colors: [
      "#008FFB",
      "#00E396",
      "#FEB019",
      "#FF4560",
      "#775DD0",
      "#00E396",
      "#FEB019",
      "#FF4560",
      "#008FFB",
      "#00E396",
      "#FEB019",
      "#FF4560",
      "#775DD0",
      "#00E396",
      "#FEB019",
      "#FF4560",
    ],
    plotOptions: {
      bar: {
        columnWidth: "80%",
        distributed: true,
      },
    },
    dataLabels: {
      enabled: false,
    },
    legend: {
      show: false,
    },
    xaxis: {
      categories: [
        ["John", "Doe"],
        ["Joe", "Smith"],
        ["Jake", "Williams"],
        "Amber",
        ["Peter", "Brown"],
        ["Mary", "Evans"],
        ["David", "Wilson"],
        ["Lily", "Roberts"],
        ["John", "Doe"],
        ["Joe", "Smith"],
        ["Jake", "Williams"],
        "Amber",
        ["Peter", "Brown"],
        ["Mary", "Evans"],
        ["David", "Wilson"],
        ["Lily", "Roberts"],
      ],
      labels: {
        style: {
          colors: [
            "#008FFB",
            "#00E396",
            "#FEB019",
            "#FF4560",
            "#775DD0",
            "#00E396",
            "#FEB019",
            "#FF4560",
            "#008FFB",
            "#00E396",
            "#FEB019",
            "#FF4560",
            "#775DD0",
            "#00E396",
            "#FEB019",
            "#FF4560",
          ],
          fontSize: "12px",
        },
      },
    },
  },
};

export const dataB = {
  series: [
    {
      name: "Actual",
      data: [
        {
          x: "2011",
          y: 12,
          goals: [
            {
              name: "Expected",
              value: 14,
              strokeWidth: 2,
              strokeDashArray: 2,
              strokeColor: "#775DD0",
            },
          ],
        },
        {
          x: "2012",
          y: 44,
          goals: [
            {
              name: "Expected",
              value: 54,
              strokeWidth: 5,
              strokeHeight: 10,
              strokeColor: "#775DD0",
            },
          ],
        },
        {
          x: "2013",
          y: 54,
          goals: [
            {
              name: "Expected",
              value: 52,
              strokeWidth: 10,
              strokeHeight: 0,
              strokeLineCap: "round",
              strokeColor: "#775DD0",
            },
          ],
        },
        {
          x: "2014",
          y: 66,
          goals: [
            {
              name: "Expected",
              value: 61,
              strokeWidth: 10,
              strokeHeight: 0,
              strokeLineCap: "round",
              strokeColor: "#775DD0",
            },
          ],
        },
        {
          x: "2015",
          y: 81,
          goals: [
            {
              name: "Expected",
              value: 66,
              strokeWidth: 10,
              strokeHeight: 0,
              strokeLineCap: "round",
              strokeColor: "#775DD0",
            },
          ],
        },
        {
          x: "2016",
          y: 67,
          goals: [
            {
              name: "Expected",
              value: 70,
              strokeWidth: 5,
              strokeHeight: 10,
              strokeColor: "#775DD0",
            },
          ],
        },
      ],
    },
  ],
  chartOptions: {
    chart: {
      height: 350,
      type: "bar",
    },
    plotOptions: {
      bar: {
        horizontal: true,
      },
    },
    colors: ["#00E396"],
    dataLabels: {
      formatter: function (val, opt) {
        const goals =
          opt.w.config.series[opt.seriesIndex].data[
            opt.dataPointIndex
          ].goals;

        if (goals && goals.length) {
          return `${val} / ${goals[0].value}`;
        }
        return val;
      },
    },
    legend: {
      show: true,
      showForSingleSeries: true,
      customLegendItems: ["Actual", "Expected"],
      markers: {
        fillColors: ["#00E396", "#775DD0"],
      },
    },
  },
};

function generateData(baseval, count, yrange) {
  let i = 0;
  const series = [];
  while (i < count) {
    const x = Math.floor(Math.random() * (750 - 1 + 1)) + 1;
    const y = Math.floor(Math.random() * (yrange.max - yrange.min + 1)) + yrange.min;
    const z = Math.floor(Math.random() * (75 - 15 + 1)) + 15;

    series.push({
      x,
      y,
      z
    });
    i++;
  }
  return series;
}


export const dataC = {
  series: [
    {
      name: "Bubble1",
      data: generateData(new Date("11 Feb 2017 GMT").getTime(), 20, {
        min: 10,
        max: 60,
      }),
    },
    {
      name: "Bubble2",
      data: generateData(new Date("11 Feb 2017 GMT").getTime(), 20, {
        min: 10,
        max: 60,
      }),
    },
    {
      name: "Bubble3",
      data: generateData(new Date("11 Feb 2017 GMT").getTime(), 20, {
        min: 10,
        max: 60,
      }),
    },
    {
      name: "Bubble4",
      data: generateData(new Date("11 Feb 2017 GMT").getTime(), 20, {
        min: 10,
        max: 60,
      }),
    },
  ],
  chartOptions: {
    chart: {
      height: 350,
      type: "bubble",
    },
    dataLabels: {
      enabled: false,
    },
    fill: {
      opacity: 0.8,
    },
    title: {
      text: "Simple Bubble Chart",
    },
    xaxis: {
      tickAmount: 12,
      type: "category",
    },
    yaxis: {
      max: 70,
    },
  },
};
