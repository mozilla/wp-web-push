var data = {
  labels: webPushChartData.labels,
  datasets: [
  {
    label: "Sent notifications",
    fillColor: "rgba(220,220,220,0.5)",
    strokeColor: "rgba(220,220,220,0.8)",
    highlightFill: "rgba(220,220,220,0.75)",
    highlightStroke: "rgba(220,220,220,1)",
    data: webPushChartData.sent,
  },
  {
    label: 'Opened notifications',
    fillColor: 'rgba(151,187,205,0.5)',
    strokeColor: 'rgba(151,187,205,0.8)',
    highlightFill: 'rgba(151,187,205,0.75)',
    highlightStroke: 'rgba(151,187,205,1)',
    data: webPushChartData.opened,
  }
  ]
};

var options = {
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

window.onload = function() {
  var ctx = document.getElementById('notifications-chart').getContext('2d');
  var barChart = new Chart(ctx).Bar(data, options);
};

