import axios from 'axios';

const drawBasic = (chartData, selector, errorTitle, logType, onLabelClick) => {
  const data = new google.visualization.DataTable();
  data.addColumn('string', 'Date');
  data.addColumn('number', `${errorTitle} count`);
  data.addColumn({ type: 'string', role: 'annotation' });
  data.addColumn('number', `Unique count`);
  data.addColumn({ type: 'string', role: 'annotation' });

  data.addRows(
    chartData.map(error => {
      return [
        error.date,
        parseInt(error.cnt),
        error.cnt,
        parseInt(error.uniqCnt),
        error.uniqCnt,
      ];
    })
  );

  const options = {
    title: `${errorTitle} count trend`,
    vAxis: {
      title: `${errorTitle} count`,
    },
    height: 350,
    colors: ['#f39c12', '#00c0ef'],
  };

  const chart = new google.visualization.ColumnChart(
    document.getElementById(selector)
  );

  google.visualization.events.addListener(chart, 'click', e => {
    const match = e.targetID.match(/hAxis#\d#label#(\d)/);

    if (match !== null && match.length) {
      const rowIndex = parseInt(
        e.targetID.substr(e.targetID.lastIndexOf('#') + 1)
      );

      const label = data.getValue(rowIndex, 0);

      onLabelClick(label);
    }
  });

  chart.draw(data, options);
};

const drawChart = (chartData, selector, errorTitle, logType, onLabelClick) => {
  google.charts.load('current', { packages: ['corechart', 'bar'] });
  google.charts.setOnLoadCallback(() => {
    drawBasic(chartData, selector, errorTitle, logType, onLabelClick);
  });
};

const drawBasicPieChart = (chartData, selector, errorTitle, onLabelClick) => {
  let cData = [['Error type', 'Count']];

  chartData.forEach(error => {
    cData.push([error.errorType, parseInt(error.cnt)]);
  });

  const data = google.visualization.arrayToDataTable(cData);

  const options = {
    title: errorTitle,
    height: 500,
    width: 700,
  };

  const chart = new google.visualization.PieChart(
    document.getElementById(selector)
  );

  google.visualization.events.addListener(chart, 'select', () => {
    const selectedItem = chart.getSelection()[0];

    if (selectedItem) {
      const value = data.getValue(selectedItem.row, 0);

      onLabelClick(value);
    }
  });

  chart.draw(data, options);
};

const drawPieChart = (chartData, selector, errorTitle, onLabelClick) => {
  google.charts.load('current', { packages: ['corechart'] });
  google.charts.setOnLoadCallback(() => {
    drawBasicPieChart(chartData, selector, errorTitle, onLabelClick);
  });
};

const API_BASE = 'api/v1';

const vm = {
  data: {
    countData: [
      { caption: 'Error', logType: 'error', data: [], log: [], errorTypes: [] },
      {
        caption: 'Exception',
        logType: 'exception',
        data: [],
        log: [],
        errorTypes: [],
      },
    ],
    menu: [{ page: 'trend', title: 'Trend', callback: null }],
    selectedDay: { date: null, logType: null },
    hideResolved: true,
    groupRows: true,
    project: '',
    projects: [],
  },
  created: function() {
    this.updateAll();

    this.countData.forEach(data => {
      this.menu.push({
        page: `log-${data.logType}`,
        title: `${data.caption} log`,
        callback: null,
      });
    });
  },
  methods: {
    fetchErrorCountData: function(logType) {
      axios
        .get(
          `${API_BASE}/errors/by-date/${this.project}/${logType}/${this
            .hideResolvedParam}/1`
        )
        .then(response => {
          this.countData.filter(log => log.logType === logType)[0].data =
            response.data.errors;

          this.countData.filter(log => log.logType === logType)[0].errorTypes =
            response.data.errorTypes;
        });
    },
    fetchProjects: function(onProjectsFetched) {
      axios.get(`${API_BASE}/projects`).then(response => {
        this.projects = response.data;

        if (this.project === '') {
          this.project = this.projects[0];
        }

        onProjectsFetched();
      });
    },
    updateDayLog: function(error) {
      axios
        .get(
          `${API_BASE}/errors/by-day/${this.project}/${error.logType}/${this
            .hideResolvedParam}/${this.groupRows ? 1 : 0}/${error.date}`
        )
        .then(response => {
          this.countData.filter(log => log.logType === error.logType)[0].log =
            response.data;

          this.countData.filter(
            log => log.logType !== error.logType
          )[0].log = [];

          this.selectedDay = { date: error.date, logType: error.logType };
        });
    },
    updateLastDaysLog: function(logType, errorType = null) {
      axios
        .get(
          `${API_BASE}/errors/by-last-days/${this.project}/${logType}/${this
            .hideResolvedParam}/${this.groupRows ? 1 : 0}${errorType
            ? `?errorType=${errorType}`
            : ``}`
        )
        .then(response => {
          this.countData.filter(log => log.logType === logType)[0].log =
            response.data;

          this.countData.filter(log => log.logType !== logType)[0].log = [];

          this.selectedDay = { date: null, logType: logType };
        });
    },
    setPage: function(page, callback = null) {
      if (callback !== null) {
        this[callback]();
      }

      const url = location.href;

      location.href = `#${page}`;

      history.replaceState(null, null, url);
    },
    checkError: function(error) {
      axios
        .put(`${API_BASE}/errors/resolve/${this.project}/${error.errorHash}`)
        .then(response => {
          if (this.selectedDay.date !== null) {
            this.updateDayLog(
              {
                date: this.selectedDay.date,
                logType: this.selectedDay.logType,
              },
              false
            );
          } else {
            this.updateLastDaysLog(this.selectedDay.logType, false);
          }
        });
    },
    updateAll: function() {
      this.fetchProjects(() => {
        this.countData.forEach(data => {
          this.fetchErrorCountData(data.logType);
        });
      });
    },
    filterErrorLog: function() {
      if (this.selectedDay.date !== null) {
        this.updateDayLog(
          { date: this.selectedDay.date, logType: this.selectedDay.logType },
          false
        );
      } else {
        this.updateLastDaysLog(this.selectedDay.logType, false);
      }
    },
  },
  watch: {
    countData: {
      handler: function() {
        this.countData.forEach(data => {
          drawChart(
            this.countData.filter(log => log.logType === data.logType)[0].data,
            `${data.logType}Chart`,
            data.caption,
            data.logType,
            (date, logType) => {
              this.updateDayLog({ date: date, logType: data.logType });
            }
          );

          drawPieChart(
            this.countData.filter(log => log.logType === data.logType)[0]
              .errorTypes,
            `${data.logType}Piechart`,
            data.caption,
            errorType => {
              this.updateLastDaysLog(data.logType, errorType);
            }
          );
        });
      },
      deep: true,
    },
  },
  computed: {
    hideResolvedParam: function() {
      return this.hideResolved ? 1 : 0;
    },
  },
};

export default vm;
