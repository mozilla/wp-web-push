window.onload = function() {
  var data = {
    labels: webPushChartData.labels,
    datasets: [
    {
      label: webPushChartData.legendSent,
      fillColor: 'rgba(220,220,220,0.5)',
      strokeColor: 'rgba(220,220,220,0.8)',
      highlightFill: 'rgba(220,220,220,0.75)',
      highlightStroke: 'rgba(220,220,220,1)',
      data: webPushChartData.sent,
    },
    {
      label: webPushChartData.legendOpened,
      fillColor: 'rgba(151,187,205,0.5)',
      strokeColor: 'rgba(151,187,205,0.8)',
      highlightFill: 'rgba(151,187,205,0.75)',
      highlightStroke: 'rgba(151,187,205,1)',
      data: webPushChartData.opened,
    }
    ]
  };

  var options = {
    responsive: true,
    maintainAspectRatio: false,
    multiTooltipTemplate: '<%= datasetLabel %> - <%= value %>',
    scaleBeginAtZero: true,
    scaleShowGridLines: true,
    scaleGridLineColor: 'rgba(0,0,0,.05)',
    scaleGridLineWidth: 1,
    scaleShowHorizontalLines: true,
    scaleShowVerticalLines: true,
    barShowStroke: true,
    barStrokeWidth: 2,
    barValueSpacing: 5,
    barDatasetSpacing: 1,
  };

  var chartElement = document.getElementById('notifications-chart');
  var barChart = new Chart(chartElement.getContext('2d')).Bar(data, options);
  chartElement.insertAdjacentHTML('afterend', barChart.generateLegend());
};

