export default {
  data() {
    return {
      datasetTemplate: {
        label: null,
        data: [],
        fill: false,
        backgroundColor: [],
        borderColor: [],
        pointBackgroundColor: [],
        pointBorderColor: [],
      }
    };
  },
  methods: {
    transformChartData(chart) {
      let transformed = this.copy(chart);
      if (transformed.chart_data) {
        transformed.chart_data.header = this.transformHeader(transformed);
        transformed.chart_data.rows = this.transformRows(transformed);
        return this.conformData(transformed);
      }
    },
    transformHeader(chart) {
      return chart.chart_data.header.map(column => {
        column.key = column.label;
        column.transform = null;
        if (chart.type !== 'list' || chart.config.display.pivot) {
          ['series', 'category'].forEach(field => {
            if (['date', 'datetime'].includes(column.format)) {
              if (chart.config.pivot[field] === column.label) {
                if (chart.config.pivot[`${field}Function`]) {
                  column.transform = chart.config.pivot[`${field}Function`];
                }
              }
            }
          });
        }
        return column;
      });
    },
    transformRows(chart) {
      let columns = {};

      chart.chart_data.header.forEach(column => {
        columns[column.label] = column;
      });

      return chart.chart_data.rows.map(row => {
        let transformed = {};

        let keys = Object.keys(row);
        let values = Object.values(row);

        keys.forEach((key, index) => {
          transformed[key] = this.transformValue(values[index], columns[key]);
        });

        return transformed;
      });
    },
    transformValue(value, column) {
      if (column) {
        if (column.transform) {
          switch (column.transform) {
            case 'month':
              value = moment().month(value - 1).format('MMM');
              break;
            case 'dayOfWeek':
              value = moment().day(value - 1).format('ddd');
              break;
          }
        } else {
          switch (column.format) {
            case 'date':
            case 'datetime':
              value = moment(value).format();
              break;
          }
        }
      }

      return value;
    },
    getColor(colors, index) {
      if (colors[index]) {
        return colors[index];
      } else {
        let count = colors.length;
        if (index > (count - 1)) {
          let div = parseInt(Math.floor(index / count));
          return this.getColor(colors, index - (count * div));
        }
      }
    },
    fillColorArray(colors, length) {
      let i;
      let filled = [];

      for (i = 0; i < length; i++) {
        filled[i] = this.getColor(colors, i);
      }

      return filled;
    },
    copy(data) {
      return JSON.parse(JSON.stringify(data));
    },
    conformData(chart) {
      let data = chart.chart_data;
      let config = chart.config;

      if (chart.type === 'list') {
        return this.conformList(data, config);
      }
      if (chart.type === 'count') {
        return this.conformCount(data, config);
      }
      if (config.pivot.category) {
        return this.conformComplex(data, config);
      }
      return this.conformSimple(data, config);
    },
    conformList(data, config) {
      if (!config.display.pivot) {
        data.header.push({
          default: false,
          field: 'ProcessMaker.url',
          format: 'link',
          key: 'actions',
          label: '',
          mask: null,
          sortable: false,
          transform: null,
        });
      }
      return data;
    },
    conformCount(data, config) {
      return {
        datasets: [{
          label: config.display.label ? config.display.label : data.header[0].label,
          icon: config.display.icon,
          data: Object.values(data.rows[0]),
          backgroundColor: config.colorScheme.colors,
        }]
      };
    },
    conformSimple(data, config) {
      let parsed = {
        labels: [],
        datasets: [this.copy(this.datasetTemplate)],
      };

      data.rows.forEach(dataPoint => {
        dataPoint = Object.values(dataPoint);
        parsed.labels.push(dataPoint[0]);
        parsed.datasets[0].data.push(dataPoint[1]);
      });
      parsed.datasets[0].label = data.header[1].label;
      parsed.datasets[0].backgroundColor = this.fillColorArray(config.colorScheme.colors, parsed.datasets[0].data.length);
      parsed.datasets[0].borderColor = parsed.datasets[0].backgroundColor;
      parsed.datasets[0].pointBackgroundColor = parsed.datasets[0].backgroundColor;
      parsed.datasets[0].pointBorderColor = parsed.datasets[0].backgroundColor;

      return parsed;
    },
    conformComplex(data, config) {
      let parsed = {
        labels: [],
        datasets: {},
      };

      data.rows.forEach((dataPoint, key) => {
        dataPoint = Object.values(dataPoint);

        if (!parsed.datasets[dataPoint[1]]) {
          parsed.datasets[dataPoint[1]] = this.copy(this.datasetTemplate);
        }

        let label = dataPoint[0];
        if (!parsed.labels.includes(label)) {
          parsed.labels.push(label);
        }

        parsed.datasets[dataPoint[1]].data[key] = dataPoint[2];

        if (!parsed.datasets[dataPoint[1]].label) {
          parsed.datasets[dataPoint[1]].label = dataPoint[1];
        }
      });

      parsed.datasets = Object.values(parsed.datasets);
      parsed.datasets.map((dataset, index) => {
        dataset.backgroundColor = this.getColor(config.colorScheme.colors, index);
        dataset.borderColor = dataset.backgroundColor;
        dataset.pointBackgroundColor = dataset.backgroundColor;
        dataset.pointBorderColor = dataset.backgroundColor;
        return dataset;
      });

      return parsed;
    }
  }
};
