// Constants
var CLOUD_VIZ_PATH = '/assets/aws/py/cloudviz.py';
var DEFAULT_LINE_OPTIONS = {
  'interpolateNulls': true,
  'legend': {
    'position': 'top'
  },
};
var DEFAULT_RANGE = 24;
var DEFAULT_TIMEZONE = "US/Pacific";
var DEFAULT_REGION = "us-west-2";
var DEFAULT_PERIOD = 60;

// Variables
var range = DEFAULT_RANGE; // range of time covered by chart, in hours
var timezone = DEFAULT_TIMEZONE;
var region = DEFAULT_REGION; // change to to your AWS region
var period = DEFAULT_PERIOD; // interval between sample points, in seconds

var firstTime = true;
var wrapper = [];

// Charts
var charts = new Array();
var healthyHostChart = {
  "container": "healthyhost",
  "cloudviz": {
    "namespace": "AWS/ELB",
    "metric": "HealthyHostCount",
    "unit": "Count",
    "statistics": ["Average"],
    "period": period,
    "range": range,
    "timezone": timezone,
    "region": region,
    "calc_rate": false,
    "cloudwatch_queries": [{
      "prefix": "production-elb healthy host count",
      "dimensions": {
        "LoadBalancerName": "production-elb"
      }
    }]
  }
};
charts.push(healthyHostChart);

var unhealthyHostChart = {
  "container": "unhealthyhost",
  "cloudviz": {
    "namespace": "AWS/ELB",
    "metric": "UnHealthyHostCount",
    "unit": "Count",
    "statistics": ["Average"],
    "period": period,
    "range": range,
    "timezone": timezone,
    "region": region,
    "calc_rate": false,
    "cloudwatch_queries": [{
      "prefix": "production-elb unhealthy host count",
      "dimensions": {
        "LoadBalancerName": "production-elb"
      }
    }]
  }
};
charts.push(unhealthyHostChart);

var latencyChart = {
  "container": "latency",
  "cloudviz": {
    "namespace": "AWS/ELB", // CloudWatch namespace (string)
    "metric": "Latency", // CloudWatch metric (string)
    "unit": "Seconds", // CloudWatch unit (string)
    "statistics": ["Average", "Maximum"], // CloudWatch statistics (list of strings)
    "period": period, // CloudWatch period (int)
    "range": range,
    "timezone": timezone,
    "region": region,
    "cloudwatch_queries": // (list of dictionaries)
      [{
      "prefix": "production-elb Latency", // label prefix for associated data sets (string)
      "dimensions": {
        "LoadBalancerName": "production-elb"
      } // CloudWatch dimensions (dictionary)
    }]
  }
};
charts.push(latencyChart);

var filerCpuChart = {
  "container": "filercpu",
  "cloudviz": {
    "namespace": "AWS/EC2",
    "metric": "CPUUtilization",
    "unit": "Percent",
    "statistics": ["Average"],
    "period": period,
    "range": range,
    "timezone": timezone,
    "region": region,
    "calc_rate": false,
    "cloudwatch_queries": [{
      "prefix": "event Production CPU1",
      "dimensions": {
        "InstanceId": "i-3e8098c8"
      }
    }, {
      "prefix": "event Production CPU2",
      "dimensions": {
        "InstanceId": "i-203e79f9"
      }
    }]
  }
};
charts.push(filerCpuChart);

var networkInChart = {
  "container": "networkin",
  "cloudviz": {
    "namespace": "AWS/EC2",
    "metric": "NetworkIn",
    "unit": "Bytes",
    "statistics": ["Average"],
    "period": period,
    "range": range,
    "timezone": timezone,
    "region": region,
    "calc_rate": false,
    "cloudwatch_queries": [{
      "prefix": "event Production Main CPU NetworkIn",
      "dimensions": {
        "InstanceId": "i-3e8098c8"
      }
    }]
  }
};
charts.push(networkInChart);

var networkOutChart = {
  "container": "networkout",
  "cloudviz": {
    "namespace": "AWS/EC2",
    "metric": "NetworkOut",
    "unit": "Bytes",
    "statistics": ["Average"],
    "period": period,
    "range": range,
    "timezone": timezone,
    "region": region,
    "calc_rate": false,
    "cloudwatch_queries": [{
      "prefix": "event Production Main CPU NetworkOut",
      "dimensions": {
        "InstanceId": "i-3e8098c8"
      }
    }]
  }
};
charts.push(networkOutChart);

var error4xxChart = {
  "container": "4xxerror",
  "cloudviz": {
    "namespace": "AWS/ELB",
    "metric": "HTTPCode_Backend_4XX",
    "unit": "Count",
    "statistics": ["Sum"],
    "period": period,
    "range": range,
    "timezone": timezone,
    "region": region,
    "calc_rate": false,
    "cloudwatch_queries": [{
      "prefix": "Production ELB HTTPCode_Backend_4XX",
      "dimensions": {
        "LoadBalancerName": "production-elb"
      }
    }]
  }
};
charts.push(error4xxChart);

var error5xxChart = {
  "container": "5xxerror",
  "cloudviz": {
    "namespace": "AWS/ELB",
    "metric": "HTTPCode_Backend_5XX",
    "unit": "Count",
    "statistics": ["Sum"],
    "period": period,
    "range": range,
    "timezone": timezone,
    "region": region,
    "calc_rate": false,
    "cloudwatch_queries": [{
      "prefix": "Production ELB HTTPCode_Backend_5XX",
      "dimensions": {
        "LoadBalancerName": "production-elb"
      }
    }]
  }
};
charts.push(error5xxChart);

var rscCpuUtilizationChart = {
  "container": "rscpuutilization",
  "cloudviz": {
    "namespace": "AWS/Redshift",
    "metric": "CPUUtilization",
    "unit": "Percent",
    "statistics": ["Average"],
    "period": period,
    "range": range,
    "timezone": timezone,
    "region": region,
    "calc_rate": false,
    "cloudwatch_queries": [{
      "prefix": "Event Prod DB CPUUtilization",
      "dimensions": {
        "ClusterIdentifier": "eventtrackingclustering"
      }
    }]
  }
};
charts.push(rscCpuUtilizationChart);

var dbNetworkInChart = {
  "container": "dbnetworkin",
  "cloudviz": {
    "namespace": "AWS/Redshift",
    "metric": "NetworkReceiveThroughput",
    "unit": "Bytes/Second",
    "statistics": ["Average"],
    "period": period,
    "range": range,
    "timezone": timezone,
    "region": region,
    "calc_rate": false,
    "cloudwatch_queries": [{
      "prefix": "Event Prod DB NetworkReceiveThroughput",
      "dimensions": {
        "ClusterIdentifier": "eventtrackingclustering"
      }
    }]
  }
};
charts.push(dbNetworkInChart);

var dbNetworkOutChart = {
  "container": "dbnetworkout",
  "cloudviz": {
    "namespace": "AWS/Redshift",
    "metric": "NetworkTransmitThroughput",
    "unit": "Bytes/Second",
    "statistics": ["Average"],
    "period": period,
    "range": range,
    "timezone": timezone,
    "region": region,
    "calc_rate": false,
    "cloudwatch_queries": [{
      "prefix": "Event Prod DB NetworktraTransmitThroughput",
      "dimensions": {
        "ClusterIdentifier ": "eventtrackingclustering"
      }
    }]
  }
};
charts.push(dbNetworkOutChart);

var ipCounterChart = {
  "container": "ipcounter",
  "cloudviz": {
    "namespace": "ProdLogs",
    "metric": "IPCounter",
    "unit": "Count",
    "statistics": ["Average"],
    "period": period,
    "range": range,
    "timezone": timezone,
    "region": region,
    "calc_rate": false,
    "cloudwatch_queries": [{
      "prefix": "Prod EC2 IP Count Access ",
      "dimensions": {
        "InstanceId": "i-1a7574de"
      }
    }]
  }
};
charts.push(ipCounterChart);

// Drawing functions
function initDash() {
  var i, qs, url;

  for (i = 0; i < charts.length; i++) {
    qs = JSON.stringify(charts[i].cloudviz);
    url = 'http://' + window.location.host + CLOUD_VIZ_PATH + '?qs=' + qs;
    wrapper[i] = new google.visualization.ChartWrapper();
    wrapper[i].setChartType('LineChart');
    wrapper[i].setDataSourceUrl(url);
    wrapper[i].setOptions(DEFAULT_LINE_OPTIONS);
    wrapper[i].setContainerId(charts[i].container);
  }
}

function drawDash() {
  var i;

  if (firstTime) {
    initDash();
    firstTime = false;
  }
  for (i = 0; i < charts.length; i++) {
    wrapper[i].draw();
  }
  var now = new Date();
  document.getElementById('clock').innerHTML = now.toString();
  setTimeout("drawDash(); ", 60000);
}

function updateDash() {
  var s, i, qs, url;

  s = document.forms[0].elements["range"];
  i = s.selectedIndex;
  range = parseInt(s.options[i].value);
  s = document.forms[0].elements["timezone"];
  i = s.selectedIndex;
  timezone = s.options[i].value;
  s = document.forms[0].elements["period"];
  i = s.selectedIndex;
  period = s.options[i].value;
  for (i = 0; i < charts.length; i++) {
    charts[i].cloudviz.range = range;
    charts[i].cloudviz.period = period;
    charts[i].cloudviz.timezone = timezone;
    qs = JSON.stringify(charts[i].cloudviz);
    url = 'http://' + window.location.host + CLOUD_VIZ_PATH + '?qs=' + qs;
    wrapper[i].setDataSourceUrl(url);
  }
  drawDash();
}

google.setOnLoadCallback(drawDash);
