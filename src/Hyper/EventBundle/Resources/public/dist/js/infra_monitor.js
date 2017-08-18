/* Constants */
const DEFAULT_RANGE = 24;
const DEFAULT_TIMEZONE = "US/Pacific";
const DEFAULT_REGION = "us-west-2";
const DEFAULT_PERIOD = 60;
const DEFAULT_INTERVAL = 60 * 1000;

const ChartType = {
    LINE: 'LineChart',
    BAR: 'BarChart',
    AREA: 'AreaChart',
    COLUMN: 'ColumnChart'
};

const DefaultColors = {
    BG: "#191919",
    DEFAULT_AVG: "#00BCFF",
    DEFAULT_MAX_NEG: "#FF8500",
    DEFAULT_MIN_POS: "#00FF55",
    DEFAULT_TEXT: "#AAAAAA"
};

const ChartGroup = {
    ELB: "group-elb",
    EC2: "group-ec2",
    SQS: "group-sqs",
    RS: "group-redshift"
};

// For default charting options
var DEFAULT_LINE_OPTIONS = {
    "width": "100%",
    "height": "100%",
    "titleTextStyle": {
        color: DefaultColors.DEFAULT_TEXT
    },
    "interpolateNulls": true,
    "legend": {
        "position": "top",
        "textStyle": {
            "color": DefaultColors.DEFAULT_TEXT
        }
    },
    "hAxis": {
        "textStyle": {
            "color": DefaultColors.DEFAULT_TEXT
        }
    },
    "vAxis": {
        "textStyle": {
            "color": DefaultColors.DEFAULT_TEXT
        }
    },
    "backgroundColor": DefaultColors.BG,
    "colors": [DefaultColors.DEFAULT_AVG]

};

// Variables
var range = DEFAULT_RANGE; // range of time covered by chart, in hours
var timezone = DEFAULT_TIMEZONE;
var region = DEFAULT_REGION; // change to to your AWS region
var period = DEFAULT_PERIOD; // interval between sample points, in seconds
var refresh = DEFAULT_INTERVAL;

/*********************************************************************************************************************/

var chartsData = new Array();
var charts = new Array();
var selectTab = ChartGroup.ELB;

/**
 * Prepare dashboard data and values.
 */
function initDashboard() {
    var index, queryString, dataSourceUrl;

    for (index = 0; index < chartsData.length; index++) {
        queryString = JSON.stringify(chartsData[index].cloudviz);
        dataSourceUrl = 'http://' + window.location.host + CLOUD_VIZ_PATH + '?qs=' + queryString;

        // Default values for now
        var chartObj = new google.visualization.ChartWrapper();
        chartObj.setChartType(chartsData[index].chart_type || ChartType.LINE);
        chartObj.setOptions(chartsData[index].chart_options || DEFAULT_LINE_OPTIONS);
        chartObj.setDataSourceUrl(dataSourceUrl);
        chartObj.setContainerId(chartsData[index].container);

        charts.push(chartObj);
    }
}

/**
 * Draw and present dashboard.
 */
var lastCall = 0;
var lastTab = selectTab;
function drawDashboard(forceUpdate) {
    if (charts.length == 0) {
        console.error("initDashboard");
        initDashboard();
    }

    console.log("TAB ::" + $('.nav-pills .active').val());

    console.error("drawDashboard");
    if ((new Date().getTime() - lastCall) > refresh || lastTab != selectTab || forceUpdate) {
        lastCall = new Date().getTime();
        lastTab = selectTab;

        var index = 0;
        async.each(charts, function iteroo(chart, callback) {
            if (chartsData[index].chart_group == selectTab) {
                chart.draw();
            }
            callback();
            index++;
        });

        if (selectTab == ChartGroup.ELB) {
            async.parallel([
                function (callback) {
                    prepareAKHttpBackEndChart();
                    callback();
                }
                //,
                //function (callback) {
                //    prepareAKHostsChart();
                //    callback();
                //},
                //function (callback) {
                //    prepareTKHostsChart();
                //    callback();
                //}
            ]);
        }

        console.error('approved drawDashboard()');
    } else {
        console.error('denied drawDashboard()');
    }
}

/**
 * Redraw dashboard on updated control values.
 */
function updateDashboard() {
    range = parseInt($('#range_select').val());
    timezone = $('#timezone_select').val();
    period = $('#period_select').val();
    refresh = parseInt($('#refresh_select').val());

    var index, queryString, dataSourceUrl;
    for (index = 0; index < chartsData.length; index++) {
        chartsData[index].cloudviz.range = range;
        chartsData[index].cloudviz.period = period;
        chartsData[index].cloudviz.timezone = timezone;

        queryString = JSON.stringify(chartsData[index].cloudviz);
        dataSourceUrl = 'http://' + window.location.host + CLOUD_VIZ_PATH + '?qs=' + queryString;
        charts[index].setDataSourceUrl(dataSourceUrl);

        log(chartsData[index].container + ": " + dataSourceUrl);
    }

    drawDashboard(true);

    // Update refresh dashboard
    setTimeout("updateDashboard();", refresh);
}

/**
 * Update dashboard time.
 */
function updateDashboardTime() {
    var now = new Date();
    $('#clock').html(now.toString());
    setTimeout("updateDashboardTime();", 60 * 1000); // 1min
}

// Helpers
function log(logMessage) {
    //console.log("DEV AWS Dashboard: " + logMessage);
}

function getSumForColumn(dataSourceUrl, columnIndex, callback) {
    var dataSource = new google.visualization.Query(dataSourceUrl);
    dataSource.setQuery("SELECT *");
    dataSource.send(function (response) {
        var data = response.getDataTable();

        var totals = google.visualization.data.group(data, [{
            type: 'number',
            column: 0,
            // make all values the same
            modifier: function () {
                return 0;
            }
        }], [{
            type: 'number',
            column: columnIndex,
            aggregation: google.visualization.data.sum
        }]);

        callback(totals.getValue(0, 1));
    });
}

/*********************************************************************************************************************/

// 0: Healthy Hosts Count
addChart(
    ChartGroup.ELB,
    'healthyhost',
    {
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
            "prefix": "Prod ELB Healthy Host ",
            "dimensions": {
                "LoadBalancerName": "production-elb"
            }
        }]
    },
    ChartType.COLUMN,
    {
        "interpolateNulls": true,
        "legend": {
            "position": "top",
            "textStyle": {
                "color": DefaultColors.DEFAULT_TEXT
            }
        },
        "hAxis": {
            "textStyle": {
                "color": DefaultColors.DEFAULT_TEXT
            }
        },
        "vAxis": {
            "textStyle": {
                "color": DefaultColors.DEFAULT_TEXT
            }
        },
        "backgroundColor": DefaultColors.BG,
        "colors": [DefaultColors.DEFAULT_AVG]
    }
);

// 1: Unhealthy Hosts Count
addChart(
    ChartGroup.ELB,
    'unhealthyhost',
    {
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
            "prefix": "Prod ELB Unhealthy Host ",
            "dimensions": {
                "LoadBalancerName": "production-elb"
            }
        }]
    },
    ChartType.COLUMN,
    {
        "interpolateNulls": true,
        "legend": {
            "position": "top",
            "textStyle": {
                "color": DefaultColors.DEFAULT_TEXT
            }
        },
        "hAxis": {
            "textStyle": {
                "color": DefaultColors.DEFAULT_TEXT
            }
        },
        "vAxis": {
            "textStyle": {
                "color": DefaultColors.DEFAULT_TEXT
            }
        },
        "backgroundColor": DefaultColors.BG,
        "colors": [DefaultColors.DEFAULT_MAX_NEG]
    }
);

// 2: Latency
addChart(
    ChartGroup.ELB,
    'latency',
    {
        "namespace": "AWS/ELB",
        "metric": "Latency",
        "unit": "Seconds",
        "statistics": ["Average"],
        "period": period,
        "range": range,
        "timezone": timezone,
        "region": region,
        "cloudwatch_queries": [{
            "prefix": "Prod ELB Latency ",
            "dimensions": {
                "LoadBalancerName": "production-elb"
            }
        }]
    },
    ChartType.AREA,
    {
        "titleTextColor": {
            "color": DefaultColors.DEFAULT_TEXT
        },
        "interpolateNulls": true,
        "legend": {
            "position": "top",
            "textStyle": {
                "color": DefaultColors.DEFAULT_TEXT
            }
        },
        "hAxis": {
            "textStyle": {
                'title': 'Time of the Day',
                "color": DefaultColors.DEFAULT_TEXT
            }
        },
        "vAxis": {
            "textStyle": {
                "title": 'Latency (sec)',
                "color": DefaultColors.DEFAULT_TEXT
            }
        },
        "backgroundColor": DefaultColors.BG,
        "colors": [DefaultColors.DEFAULT_AVG],
        "is3D": true,
        "isStacked": true
    }
);

// 3: Filer CPU Utilization
addChart(
    ChartGroup.EC2,
    'filercpu',
    {
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
            "prefix": "Production CPU1",
            "dimensions": {
                "InstanceId": "i-3e8098c8"
            }
        }]
    },
    ChartType.LINE,
    {
        "interpolateNulls": true,
        "legend": {
            "position": "top",
            "textStyle": {
                "color": DefaultColors.DEFAULT_TEXT
            }
        },
        "hAxis": {
            "textStyle": {
                "color": DefaultColors.DEFAULT_TEXT
            }
        },
        "vAxis": {
            "textStyle": {
                "color": DefaultColors.DEFAULT_TEXT
            }
        },
        "backgroundColor": DefaultColors.BG,
        "colors": [DefaultColors.DEFAULT_AVG]
    }
);


// 4: AWS/EC2 Network In
addChart(
    ChartGroup.EC2,
    'networkin',
    {
        "namespace": "AWS/EC2",
        "metric": "NetworkIn",
        "unit": "Bytes",
        "statistics": ["Minimum", "Average", "Maximum"],
        "period": period,
        "range": range,
        "timezone": timezone,
        "region": region,
        "calc_rate": false,
        "cloudwatch_queries": [{
            "prefix": "Production Main CPU NetworkIn ",
            "dimensions": {
                "InstanceId": "i-3e8098c8"
            }
        }]
    },
    ChartType.AREA,
    {
        "interpolateNulls": true,
        "legend": {
            "position": "top",
            "textStyle": {
                "color": DefaultColors.DEFAULT_TEXT
            }
        },
        "hAxis": {
            "textStyle": {
                "color": DefaultColors.DEFAULT_TEXT
            }
        },
        "vAxis": {
            "textStyle": {
                "color": DefaultColors.DEFAULT_TEXT
            }
        },
        "backgroundColor": DefaultColors.BG,
        "colors": [DefaultColors.DEFAULT_MIN_POS, DefaultColors.DEFAULT_AVG, DefaultColors.DEFAULT_MAX_NEG]
    }
);

// 5: AWS/EC2 Network Out
addChart(
    ChartGroup.EC2,
    'networkout',
    {
        "namespace": "AWS/EC2",
        "metric": "NetworkOut",
        "unit": "Bytes",
        "statistics": ["Minimum", "Average", "Maximum"],
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
    },
    ChartType.AREA,
    {
        "interpolateNulls": true,
        "legend": {
            "position": "top",
            "textStyle": {
                "color": DefaultColors.DEFAULT_TEXT
            }
        },
        "hAxis": {
            "textStyle": {
                "color": DefaultColors.DEFAULT_TEXT
            }
        },
        "vAxis": {
            "textStyle": {
                "color": DefaultColors.DEFAULT_TEXT
            }
        },
        "backgroundColor": DefaultColors.BG,
        "colors": [DefaultColors.DEFAULT_MIN_POS, DefaultColors.DEFAULT_AVG, DefaultColors.DEFAULT_MAX_NEG]
    }
);

// 6: HTTP Backend 4xx Errors
addChart(
    ChartGroup.ELB,
    '4xxerror',
    {
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
    },
    ChartType.LINE,
    {
        "interpolateNulls": true,
        "legend": "none",
        "hAxis": {
            "textStyle": {
                "color": DefaultColors.DEFAULT_TEXT
            }
        },
        "vAxis": {
            "textStyle": {
                "color": DefaultColors.DEFAULT_TEXT
            }
        },
        "explorer": {},
        "backgroundColor": DefaultColors.BG,
        "curveType": "function",
        "colors": [DefaultColors.DEFAULT_AVG]
    }
);

// 7: HTTP Backend 5xx Errors
addChart(
    ChartGroup.ELB,
    '5xxerror',
    {
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
    },
    ChartType.LINE,
    {
        "interpolateNulls": true,
        "legend": "none",
        "hAxis": {
            "textStyle": {
                "color": DefaultColors.DEFAULT_TEXT
            }
        },
        "vAxis": {
            "textStyle": {
                "color": DefaultColors.DEFAULT_TEXT
            }
        },
        "backgroundColor": DefaultColors.BG,
        "curveType": "function",
        "colors": [DefaultColors.DEFAULT_MAX_NEG]
    }
);

// 8: RedShift CPU Utilization
addChart(
    ChartGroup.RS,
    'rscpuutilisation',
    {
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
);

// 9: DB Network In
addChart(
    ChartGroup.RS,
    'dbnetworkin',
    {
        "namespace": "AWS/Redshift",
        "metric": "NetworkReceiveThroughput",
        "unit": "Bytes/Second",
        "statistics": ["Minimum", "Average", "Maximum"],
        "period": period,
        "range": range,
        "timezone": timezone,
        "region": region,
        "calc_rate": false,
        "cloudwatch_queries": [
            {
                "prefix": "Event Prod DB NetworkReceiveThroughput ",
                "dimensions": {"ClusterIdentifier": "eventtrackingclustering"}
            },
        ]
    },
    ChartType.AREA,
    {
        "legend": {
            "position": "top",
            "textStyle": {
                "color": DefaultColors.DEFAULT_TEXT
            }
        },
        "hAxis": {
            "textStyle": {
                "color": DefaultColors.DEFAULT_TEXT
            }
        },
        "vAxis": {
            "textStyle": {
                "color": DefaultColors.DEFAULT_TEXT
            }
        },
        "backgroundColor": DefaultColors.BG,
        "colors": [DefaultColors.DEFAULT_MIN_POS, DefaultColors.DEFAULT_AVG, DefaultColors.DEFAULT_MAX_NEG]

    }
);


// 10: DB Network Out
addChart(
    ChartGroup.RS,
    'dbnetworkout',
    {
        "namespace": "AWS/Redshift",
        "metric": "NetworkTransmitThroughput",
        "unit": "Bytes/Second",
        "statistics": ["Minimum", "Average", "Maximum"],
        "period": period,
        "range": range,
        "timezone": timezone,
        "region": region,
        "calc_rate": false,
        "cloudwatch_queries": [
            {
                "prefix": "Event Prod DB NetworkTransmitThroughput ",
                "dimensions": {"ClusterIdentifier": "eventtrackingclustering"}
            },
        ]
    },
    ChartType.AREA,
    {
        "legend": {
            "position": "top",
            "textStyle": {
                "color": DefaultColors.DEFAULT_TEXT
            }
        },
        "hAxis": {
            "textStyle": {
                "color": DefaultColors.DEFAULT_TEXT
            }
        },
        "vAxis": {
            "textStyle": {
                "color": DefaultColors.DEFAULT_TEXT
            }
        },
        "backgroundColor": DefaultColors.BG,
        "colors": [DefaultColors.DEFAULT_MIN_POS, DefaultColors.DEFAULT_AVG, DefaultColors.DEFAULT_MAX_NEG],

    }
);

// 11: IP Counter
addChart(
    ChartGroup.EC2,
    'ipcounter',
    {
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
    },
    ChartType.LINE,
    {
        "interpolateNulls": true,
        "legend": {
            "position": "top",
            "textStyle": {
                "color": DefaultColors.DEFAULT_TEXT
            }
        },
        "hAxis": {
            "textStyle": {
                "color": DefaultColors.DEFAULT_TEXT
            }
        },
        "vAxis": {
            "textStyle": {
                "color": DefaultColors.DEFAULT_TEXT
            }
        },
        "backgroundColor": DefaultColors.BG,
        "curveType": "function",
        "colors": [DefaultColors.DEFAULT_AVG]
    }
);

// 12: ELB Request Count
addChart(
    ChartGroup.ELB,
    'elb-request-count',
    {
        "namespace": "AWS/ELB",
        "metric": "RequestCount",
        "unit": "Count",
        "statistics": ["Sum"],
        "period": period,
        "range": range,
        "timezone": timezone,
        "region": region,
        "calc_rate": false,
        "cloudwatch_queries": [
            {
                "prefix": "Production ELB Request Count ",
                "dimensions": {"LoadBalancerName": "production-elb"}
            },
        ]
    },
    ChartType.LINE,
    {
        "legend": {
            "position": "top",
            "textStyle": {
                "color": DefaultColors.DEFAULT_TEXT
            }
        },
        "hAxis": {
            "textStyle": {
                "color": DefaultColors.DEFAULT_TEXT
            }
        },
        "vAxis": {
            "textStyle": {
                "color": DefaultColors.DEFAULT_TEXT
            }
        },
        "backgroundColor": DefaultColors.BG,
        "curveType": "function",
        "colors": [DefaultColors.DEFAULT_AVG]

    }
);

// 13: SQS
addChart(
    ChartGroup.SQS,
    'post-queue-delayed-messages',
    {
        "namespace": "AWS/SQS",
        "metric": "ApproximateNumberOfMessagesDelayed",
        "unit": "Count",
        "statistics": ["Sum"],
        "period": period,
        "range": range,
        "timezone": timezone,
        "region": region,
        "calc_rate": false,
        "cloudwatch_queries": [
            {
                "prefix": "Postback-Queue Delayed ",
                "dimensions": {"QueueName": "postback-queue"}
            },
        ]
    }
);

// 14
addChart(
    ChartGroup.SQS,
    'post-queue-not-visible-messages',
    {
        "namespace": "AWS/SQS",
        "metric": "ApproximateNumberOfMessagesNotVisible",
        "unit": "Count",
        "statistics": ["Sum"],
        "period": period,
        "range": range,
        "timezone": timezone,
        "region": region,
        "calc_rate": false,
        "cloudwatch_queries": [
            {
                "prefix": "Postback-Queue Not Visible ",
                "dimensions": {"QueueName": "postback-queue"}
            },
        ]
    }
);

// 15
addChart(
    ChartGroup.SQS,
    'post-queue-visible-messages',
    {
        "namespace": "AWS/SQS",
        "metric": "ApproximateNumberOfMessagesVisible",
        "unit": "Count",
        "statistics": ["Sum"],
        "period": period,
        "range": range,
        "timezone": timezone,
        "region": region,
        "calc_rate": false,
        "cloudwatch_queries": [
            {
                "prefix": "Postback-Queue Visible ",
                "dimensions": {"QueueName": "postback-queue"}
            },
        ]
    }
);

// 16: SQS Row Two
addChart(
    ChartGroup.SQS,
    'post-queue-messages-received',
    {
        "namespace": "AWS/SQS",
        "metric": "NumberOfMessagesReceived",
        "unit": "Count",
        "statistics": ["Sum"],
        "period": period,
        "range": range,
        "timezone": timezone,
        "region": region,
        "calc_rate": false,
        "cloudwatch_queries": [
            {
                "prefix": "Postback-Queue Received Messages ",
                "dimensions": {"QueueName": "postback-queue"}
            },
        ]
    }
);

// 17
addChart(
    ChartGroup.SQS,
    'post-queue-messages-sent',
    {
        "namespace": "AWS/SQS",
        "metric": "NumberOfMessagesSent",
        "unit": "Count",
        "statistics": ["Sum"],
        "period": period,
        "range": range,
        "timezone": timezone,
        "region": region,
        "calc_rate": false,
        "cloudwatch_queries": [
            {
                "prefix": "Postback-Queue Sent Messages ",
                "dimensions": {"QueueName": "postback-queue"}
            },
        ]
    }
);

// 18
addChart(
    ChartGroup.RS,
    'db-disk-space',
    {
        "namespace": "AWS/Redshift",
        "metric": "PercentageDiskSpaceUsed",
        "unit": "Percent",
        "statistics": ["Minimum", "Average", "Maximum"],
        "period": period,
        "range": range,
        "timezone": timezone,
        "region": region,
        "calc_rate": false,
        "cloudwatch_queries": [
            {
                "prefix": "DB Disk Space ",
                "dimensions": {"ClusterIdentifier": "eventtrackingclustering"}
            },
        ]
    },
    ChartType.AREA,
    {
        "legend": {
            "position": "top",
            "textStyle": {
                "color": DefaultColors.DEFAULT_TEXT
            }
        },
        "hAxis": {
            "textStyle": {
                "color": DefaultColors.DEFAULT_TEXT
            }
        },
        "vAxis": {
            "textStyle": {
                "color": DefaultColors.DEFAULT_TEXT
            }
        },
        "backgroundColor": DefaultColors.BG,
        "colors": [DefaultColors.DEFAULT_MIN_POS, DefaultColors.DEFAULT_AVG, DefaultColors.DEFAULT_MAX_NEG]
    }
);

// 19: Tracking Kit
addChart(
    ChartGroup.ELB,
    'tk-healthy-hosts',
    {
        "namespace": "AWS/ELB",
        "metric": "HealthyHostCount",
        "unit": "Count",
        "statistics": ["Average"],
        "period": period,
        "range": range,
        "timezone": timezone,
        "region": region,
        "calc_rate": false,
        "cloudwatch_queries": [
            {
                "prefix": "Tracking Kit Healthy Hosts ",
                "dimensions": {"LoadBalancerName": "production-adops"}
            },
        ]
    },
    ChartType.COLUMN,
    {
        "interpolateNulls": true,
        "legend": {
            "position": "top",
            "textStyle": {
                "color": DefaultColors.DEFAULT_TEXT
            }
        },
        "hAxis": {
            "textStyle": {
                "color": DefaultColors.DEFAULT_TEXT
            }
        },
        "vAxis": {
            "textStyle": {
                "color": DefaultColors.DEFAULT_TEXT
            }
        },
        "backgroundColor": DefaultColors.BG,
        "colors": [DefaultColors.DEFAULT_AVG]
    }
);

// 20
addChart(
    ChartGroup.ELB,
    'tk-unhealthy-hosts',
    {
        "namespace": "AWS/ELB",
        "metric": "UnHealthyHostCount",
        "unit": "Count",
        "statistics": ["Average"],
        "period": period,
        "range": range,
        "timezone": timezone,
        "region": region,
        "calc_rate": false,
        "cloudwatch_queries": [
            {
                "prefix": "Tracking Kit UnHealthy Hosts ",
                "dimensions": {"LoadBalancerName": "production-adops"}
            },
        ]
    },
    ChartType.COLUMN,
    {
        "interpolateNulls": true,
        "legend": {
            "position": "top",
            "textStyle": {
                "color": DefaultColors.DEFAULT_TEXT
            }
        },
        "hAxis": {
            "textStyle": {
                "color": DefaultColors.DEFAULT_TEXT
            }
        },
        "vAxis": {
            "textStyle": {
                "color": DefaultColors.DEFAULT_TEXT
            }
        },
        "backgroundColor": DefaultColors.BG,
        "colors": [DefaultColors.DEFAULT_MAX_NEG]
    }
);

// 21
addChart(
    ChartGroup.ELB,
    'tk-4xxerror',
    {
        "namespace": "AWS/ELB",
        "metric": "HTTPCode_Backend_4XX",
        "unit": "Count",
        "statistics": ["Sum"],
        "period": period,
        "range": range,
        "timezone": timezone,
        "region": region,
        "calc_rate": false,
        "cloudwatch_queries": [
            {
                "prefix": "Tracking Kit HTTP4xx error ",
                "dimensions": {"LoadBalancerName": "production-adops"}
            },
        ]
    },
    ChartType.LINE,
    {
        "interpolateNulls": true,
        "legend": "none",
        "hAxis": {
            "textStyle": {
                "color": DefaultColors.DEFAULT_TEXT
            }
        },
        "vAxis": {
            "textStyle": {
                "color": DefaultColors.DEFAULT_TEXT
            }
        },
        "backgroundColor": DefaultColors.BG,
        "curveType": "function",
        "colors": [DefaultColors.DEFAULT_AVG]
    }
);

// 22: HTTP Backend 5xx Errors
addChart(
    ChartGroup.ELB,
    'tk-5xxerror',
    {
        "namespace": "AWS/ELB",
        "metric": "HTTPCode_Backend_5XX",
        "unit": "Count",
        "statistics": ["Sum"],
        "period": period,
        "range": range,
        "timezone": timezone,
        "region": region,
        "calc_rate": false,
        "cloudwatch_queries": [
            {
                "prefix": "Tracking Kit HTTP5xx error ",
                "dimensions": {"LoadBalancerName": "production-adops"}
            }
        ]
    },
    ChartType.LINE,
    {
        "legend": "none",
        "hAxis": {
            "textStyle": {
                "color": DefaultColors.DEFAULT_TEXT
            }
        },
        "vAxis": {
            "textStyle": {
                "color": DefaultColors.DEFAULT_TEXT
            }
        },
        "backgroundColor": DefaultColors.BG,
        "curveType": "function",
        "colors": [DefaultColors.DEFAULT_MAX_NEG]
    }
);

// 23: Latency
addChart(
    ChartGroup.ELB,
    'tk-latency',
    {
        "namespace": "AWS/ELB",
        "metric": "Latency",
        "unit": "Seconds",
        "statistics": ["Minimum", "Maximum", "Average"],
        "period": period,
        "range": range,
        "timezone": timezone,
        "region": region,
        "calc_rate": false,
        "cloudwatch_queries": [
            {
                "prefix": "Tracking Kit ELB Latency ",
                "dimensions": {"LoadBalancerName": "production-adops"}
            },
        ]
    },
    ChartType.AREA,
    {
        "titleTextColor": {
            "color": DefaultColors.DEFAULT_TEXT
        },
        "interpolateNulls": true,
        "legend": {
            "position": "top",
            "textStyle": {
                "color": DefaultColors.DEFAULT_TEXT
            }
        },
        "hAxis": {
            "textStyle": {
                'title': 'Time of the Day',
                "color": DefaultColors.DEFAULT_TEXT
            }
        },
        "vAxis": {
            "textStyle": {
                "title": 'Latency (sec)',
                "color": DefaultColors.DEFAULT_TEXT
            }
        },
        "backgroundColor": DefaultColors.BG,
        "colors": [DefaultColors.DEFAULT_MIN_POS, DefaultColors.DEFAULT_AVG, DefaultColors.DEFAULT_MAX_NEG],
        "isStacked": true
    }
);

// 24: ELB Request Count
addChart(
    ChartGroup.ELB,
    'tk-elb-request-count',
    {
        "namespace": "AWS/ELB",
        "metric": "RequestCount",
        "unit": "Count",
        "statistics": ["Sum"],
        "period": period,
        "range": range,
        "timezone": timezone,
        "region": region,
        "calc_rate": false,
        "cloudwatch_queries": [
            {
                "prefix": "Tracking Kit ELB Request Count ",
                "dimensions": {"LoadBalancerName": "production-adops"}
            },
        ]
    },
    ChartType.LINE,
    {
        "legend": {
            "position": "top",
            "textStyle": {
                "color": DefaultColors.DEFAULT_TEXT
            }
        },
        "hAxis": {
            "textStyle": {
                "color": DefaultColors.DEFAULT_TEXT
            }
        },
        "vAxis": {
            "textStyle": {
                "color": DefaultColors.DEFAULT_TEXT
            }
        },
        "backgroundColor": DefaultColors.BG,
        "curveType": "function",
        "colors": [DefaultColors.DEFAULT_AVG]
    }
);

// 25
addChart(
    ChartGroup.EC2,
    'tk-filercpu',
    {
        "namespace": "AWS/EC2",
        "metric": "CPUUtilization",
        "unit": "Percent",
        "statistics": ["Average"],
        "period": period,
        "range": range,
        "timezone": timezone,
        "region": region,
        "calc_rate": false,
        "cloudwatch_queries": [
            {
                "prefix": "Tracking Kit Main EC2 CPU Util ",
                "dimensions": {"InstanceId": "i-1a7574de"}
            },
        ]
    },
    ChartType.LINE,
    {
        "interpolateNulls": true,
        "legend": {
            "position": "top",
            "textStyle": {
                "color": DefaultColors.DEFAULT_TEXT
            }
        },
        "hAxis": {
            "textStyle": {
                "color": DefaultColors.DEFAULT_TEXT
            }
        },
        "vAxis": {
            "textStyle": {
                "color": DefaultColors.DEFAULT_TEXT
            }
        },
        "backgroundColor": DefaultColors.BG,
        "colors": [DefaultColors.DEFAULT_AVG]
    }
);

// 26: AWS/EC2 Network In
addChart(
    ChartGroup.EC2,
    'tk-networkin',
    {
        "namespace": "AWS/EC2",
        "metric": "NetworkIn",
        "unit": "Bytes",
        "statistics": ["Minimum", "Average", "Maximum"],
        "period": period,
        "range": range,
        "timezone": timezone,
        "region": region,
        "calc_rate": false,
        "cloudwatch_queries": [
            {
                "prefix": "Tracking Kit Main EC2 Network In ",
                "dimensions": {"InstanceId": "i-1a7574de"}
            },
        ]
    },
    ChartType.AREA,
    {
        "interpolateNulls": true,
        "legend": {
            "position": "top",
            "textStyle": {
                "color": DefaultColors.DEFAULT_TEXT
            }
        },
        "hAxis": {
            "textStyle": {
                "color": DefaultColors.DEFAULT_TEXT
            }
        },
        "vAxis": {
            "textStyle": {
                "color": DefaultColors.DEFAULT_TEXT
            }
        },
        "backgroundColor": DefaultColors.BG,
        "colors": [DefaultColors.DEFAULT_MIN_POS, DefaultColors.DEFAULT_AVG, DefaultColors.DEFAULT_MAX_NEG]
    }
);

// 27: AWS/EC2 Network Out
addChart(
    ChartGroup.EC2,
    'tk-networkout',
    {
        "namespace": "AWS/EC2",
        "metric": "NetworkOut",
        "unit": "Bytes",
        "statistics": ["Minimum", "Average", "Maximum"],
        "period": period,
        "range": range,
        "timezone": timezone,
        "region": region,
        "calc_rate": false,
        "cloudwatch_queries": [
            {
                "prefix": "Tracking Kit Main EC2 Network Out ",
                "dimensions": {"InstanceId": "i-1a7574de"}
            },
        ]
    },
    ChartType.AREA,
    {
        "interpolateNulls": true,
        "legend": {
            "position": "top",
            "textStyle": {
                "color": DefaultColors.DEFAULT_TEXT
            }
        },
        "hAxis": {
            "textStyle": {
                "color": DefaultColors.DEFAULT_TEXT
            }
        },
        "vAxis": {
            "textStyle": {
                "color": DefaultColors.DEFAULT_TEXT
            }
        },
        "backgroundColor": DefaultColors.BG,
        "colors": [DefaultColors.DEFAULT_MIN_POS, DefaultColors.DEFAULT_AVG, DefaultColors.DEFAULT_MAX_NEG]
    }
);

// Dev AK
// 28
addChart(
    ChartGroup.EC2,
    'dev-ak-cpu',
    {
        "namespace": "AWS/EC2",
        "metric": "CPUUtilization",
        "unit": "Percent",
        "statistics": ["Average"],
        "period": period,
        "range": range,
        "timezone": timezone,
        "region": region,
        "calc_rate": false,
        "cloudwatch_queries": [
            {
                "prefix": "Dev AK EC2 CPU Util ",
                "dimensions": {"InstanceId": "i-133f97da"}
            },
        ]
    },
    ChartType.LINE,
    {
        "interpolateNulls": true,
        "legend": {
            "position": "top",
            "textStyle": {
                "color": DefaultColors.DEFAULT_TEXT
            }
        },
        "hAxis": {
            "textStyle": {
                "color": DefaultColors.DEFAULT_TEXT
            }
        },
        "vAxis": {
            "textStyle": {
                "color": DefaultColors.DEFAULT_TEXT
            }
        },
        "backgroundColor": DefaultColors.BG,
        "colors": [DefaultColors.DEFAULT_AVG]
    }
);

// 29: Dev AWS/EC2 Network In
addChart(
    ChartGroup.EC2,
    'dev-ak-networkin',
    {
        "namespace": "AWS/EC2",
        "metric": "NetworkIn",
        "unit": "Bytes",
        "statistics": ["Minimum", "Average", "Maximum"],
        "period": period,
        "range": range,
        "timezone": timezone,
        "region": region,
        "calc_rate": false,
        "cloudwatch_queries": [
            {
                "prefix": "Dev AK NetworkIn ",
                "dimensions": {"InstanceId": "i-133f97da"}
            },
        ]
    },
    ChartType.AREA,
    {
        "interpolateNulls": true,
        "legend": {
            "position": "top",
            "textStyle": {
                "color": DefaultColors.DEFAULT_TEXT
            }
        },
        "hAxis": {
            "textStyle": {
                "color": DefaultColors.DEFAULT_TEXT
            }
        },
        "vAxis": {
            "textStyle": {
                "color": DefaultColors.DEFAULT_TEXT
            }
        },
        "backgroundColor": DefaultColors.BG,
        "colors": [DefaultColors.DEFAULT_MIN_POS, DefaultColors.DEFAULT_AVG, DefaultColors.DEFAULT_MAX_NEG]
    }
);

// 30: Dev AWS/EC2 Network Out
addChart(
    ChartGroup.EC2,
    'dev-ak-networkout',
    {
        "namespace": "AWS/EC2",
        "metric": "NetworkOut",
        "unit": "Bytes",
        "statistics": ["Minimum", "Average", "Maximum"],
        "period": period,
        "range": range,
        "timezone": timezone,
        "region": region,
        "calc_rate": false,
        "cloudwatch_queries": [
            {
                "prefix": "Dev AK NetworkOut ",
                "dimensions": {"InstanceId": "i-133f97da"}
            },
        ]
    },
    ChartType.AREA,
    {
        "interpolateNulls": true,
        "legend": {
            "position": "top",
            "textStyle": {
                "color": DefaultColors.DEFAULT_TEXT
            }
        },
        "hAxis": {
            "textStyle": {
                "color": DefaultColors.DEFAULT_TEXT
            }
        },
        "vAxis": {
            "textStyle": {
                "color": DefaultColors.DEFAULT_TEXT
            }
        },
        "backgroundColor": DefaultColors.BG,
        "colors": [DefaultColors.DEFAULT_MIN_POS, DefaultColors.DEFAULT_AVG, DefaultColors.DEFAULT_MAX_NEG]
    }
);

// 31
addChart(
    ChartGroup.EC2,
    'prod-tk-mem-util',
    {
        "namespace": "System/Linux",
        "metric": "MemoryUtilization",
        "unit": "Percent",
        "statistics": ["Average"],
        "period": period,
        "range": range,
        "timezone": timezone,
        "region": region,
        "calc_rate": false,
        "cloudwatch_queries": [
            {
                "prefix": "Prod TK Memory Utilization ",
                "dimensions": {"InstanceId": "i-1a7574de"}
            }
        ]
    }
);

// 32
//addChart(
//    ChartGroup.EC2,
//    'prod-tk-mem-free',
//    {
//        "namespace": "AWS/Linux System",
//        "metric": "	MemoryAvailable",
//        "unit": "Percent",
//        "statistics": ["Average"],
//        "period": period,
//        "range": range,
//        "timezone": timezone,
//        "region": region,
//        "calc_rate": false,
//        "cloudwatch_queries": [
//            {
//                "prefix": "Prod TK Free Memory ",
//                "dimensions": {"InstanceId": "i-1a7574de"}
//            }
//        ]
//    }
//);

// 33 TODO Restore
//addChart(
//    ChartGroup.EC2,
//    'prod-tk-disk-util',
//    {
//        "namespace": "System/Linux",
//        "metric": "	DiskSpaceUtilization",
//        "unit": "Percent",
//        "statistics": ["Average"],
//        "period": period,
//        "range": range,
//        "timezone": timezone,
//        "region": region,
//        "calc_rate": false,
//        "cloudwatch_queries": [
//            {
//                "prefix": "Prod TK Free Memory ",
//                "dimensions": {"InstanceId": "i-1a7574de"}
//            }
//        ]
//    }
//);

// 34
addChart(
    ChartGroup.EC2,
    'prod-ak-mem-util',
    {
        "namespace": "System/Linux",
        "metric": "MemoryUtilization",
        "unit": "Percent",
        "statistics": ["Average"],
        "period": period,
        "range": range,
        "timezone": timezone,
        "region": region,
        "calc_rate": false,
        "cloudwatch_queries": [
            {
                "prefix": "Prod AK Memory Utilization ",
                "dimensions": {"InstanceId": "i-3e8098c8"}
            }
        ]
    }
);

//addChart(
//    ChartGroup.RS,
//    'billings-aws',
//    {
//        "namespace": "AWS/Billing",
//        "metric": "EstimatedCharges",
//        "unit": "Count",
//        "statistics": ["Max"],
//        "period": period,
//        "range": range,
//        "timezone": timezone,
//        "region": region,
//        "calc_rate": false,
//        "cloudwatch_queries": []
//    }
//);

// 35
//addChart(
//    'prod-ak-mem-free',
//    {
//        "namespace": "AWS/Linux System",
//        "metric": "	MemoryAvailable",
//        "unit": "Percent",
//        "statistics": ["Average"],
//        "period": period,
//        "range": range,
//        "timezone": timezone,
//        "region": region,
//        "calc_rate": false,
//        "cloudwatch_queries":
//            [
//                {
//                    "prefix": "Prod AK Free Memory ",
//                    "dimensions": { "InstanceId": "i-3e8098c8"}
//                }
//            ]
//    }
//);

// FIXME add object builders for cloudviz and chart options
function addChart(chartGroup, containerId, cloudvizOptions, chartType, chartOptions) {
    if (chartGroup == undefined || chartGroup == null) {
        throw new Error('chartGroup must be defined');
    }

    if (containerId == undefined || containerId == null) {
        throw new Error('containerId must be defined');
    }

    if (cloudvizOptions == undefined || cloudvizOptions == null) {
        throw new Error('cloudvizOptions must be defined');
    }

    var chartData = {};
    chartData.chart_group = chartGroup;
    chartData.container = containerId;
    chartData.cloudviz = cloudvizOptions;

    chartData.chart_type = chartType;
    chartData.chart_options = chartOptions;
    chartsData.push(chartData);
}

// Hosts
function prepareAKHostsChart() {
    var healthyHosts, unhealthyHosts;
    var healthyHostsDataSource = new google.visualization.Query(getDataSourceUrl(0));
    healthyHostsDataSource.setQuery("SELECT *");
    healthyHostsDataSource.send(function (response) {
        healthyHosts = response.getDataTable();

        createAKHostsChart(healthyHosts, unhealthyHosts);
    });

    var unhealthyHostsDataSource = new google.visualization.Query(getDataSourceUrl(1));
    unhealthyHostsDataSource.setQuery("SELECT *");
    unhealthyHostsDataSource.send(function (response) {
        unhealthyHosts = response.getDataTable();

        createAKHostsChart(healthyHosts, unhealthyHosts);
    });
}

function createAKHostsChart(healthyHosts, unhealthyHosts) {
    if ((healthyHosts != undefined || healthyHosts != null) && (unhealthyHosts != undefined || unhealthyHosts != null)) {
        var hostsBarChart = new google.visualization.ColumnChart($('#combined_hosts')[0]);

        var combinedData = new google.visualization.DataTable();
        combinedData.addColumn('string', 'Time');
        combinedData.addColumn('number', 'Healthy');
        combinedData.addColumn('number', 'Unhealthy');

        var rowIndex = 0;

        if (healthyHosts.getNumberOfRows() > 50) {
            rowIndex = healthyHosts.getNumberOfRows() - 50;
        }

        for (; rowIndex < healthyHosts.getNumberOfRows(); rowIndex++) {
            combinedData.addRow([new Date(healthyHosts.getValue(rowIndex, 0)).toLocaleString("en-US"), healthyHosts.getValue(rowIndex, 1), unhealthyHosts.getValue(rowIndex, 1)]);
        }

        var options = {
            hAxis: {
                minValue: 0,
                title: 'Time'
            },
            vAxis: {
                title: 'Hosts'
            },
            "legend": {
                "position": "top",
                "textStyle": {
                    "color": DefaultColors.DEFAULT_TEXT
                }
            },
            "hAxis": {
                "textStyle": {
                    "color": DefaultColors.DEFAULT_TEXT
                }
            },
            "vAxis": {
                "textStyle": {
                    "color": DefaultColors.DEFAULT_TEXT
                }
            },
            "backgroundColor": DefaultColors.BG,
            "colors": [DefaultColors.DEFAULT_AVG, DefaultColors.DEFAULT_MAX_NEG]
        };

        hostsBarChart.draw(combinedData, options);
    }
}

function prepareTKHostsChart() {
    var healthyHosts, unhealthyHosts;
    var healthyHostsDataSource = new google.visualization.Query(getDataSourceUrl(19));
    healthyHostsDataSource.setQuery("SELECT *");
    healthyHostsDataSource.send(function (response) {
        healthyHosts = response.getDataTable();

        createTKHostsChart(healthyHosts, unhealthyHosts);
    });

    var unhealthyHostsDataSource = new google.visualization.Query(getDataSourceUrl(20));
    unhealthyHostsDataSource.setQuery("SELECT *");
    unhealthyHostsDataSource.send(function (response) {
        unhealthyHosts = response.getDataTable();

        createTKHostsChart(healthyHosts, unhealthyHosts);
    });
}

function createTKHostsChart(healthyHosts, unhealthyHosts) {
    if ((healthyHosts != undefined || healthyHosts != null) && (unhealthyHosts != undefined || unhealthyHosts != null)) {
        var hostsBarChart = new google.visualization.ColumnChart($('#tk-host-count')[0]);

        var combinedData = new google.visualization.DataTable();
        combinedData.addColumn('string', 'Time');
        combinedData.addColumn('number', 'Healthy');
        combinedData.addColumn('number', 'Unhealthy');

        log("Rows :: " + healthyHosts.getNumberOfRows() + ", " + unhealthyHosts.getNumberOfRows());
        var rowIndex = 0;

        if (healthyHosts.getNumberOfRows() > 50) {
            rowIndex = healthyHosts.getNumberOfRows() - 50;
        }

        for (; rowIndex < healthyHosts.getNumberOfRows(); rowIndex++) {
            // log("Date Row :: " + healthyHosts.getValue(rowIndex, 0) + ", " + healthyHosts.getColumnType(0));

            combinedData.addRow([new Date(healthyHosts.getValue(rowIndex, 0)).toLocaleString("en-US"), healthyHosts.getValue(rowIndex, 1), unhealthyHosts.getValue(rowIndex, 1)]);
        }

        var options = {
            hAxis: {
                minValue: 0,
                title: 'Time'
            },
            vAxis: {
                title: 'Hosts'
            },
            "legend": {
                "position": "top",
                "textStyle": {
                    "color": DefaultColors.DEFAULT_TEXT
                }
            },
            "hAxis": {
                "textStyle": {
                    "color": DefaultColors.DEFAULT_TEXT
                }
            },
            "vAxis": {
                "textStyle": {
                    "color": DefaultColors.DEFAULT_TEXT
                }
            },
            "backgroundColor": DefaultColors.BG,
            "colors": [DefaultColors.DEFAULT_AVG, DefaultColors.DEFAULT_MAX_NEG]
        };

        hostsBarChart.draw(combinedData, options);
    }
}

// HTTP Backend Errors
function prepareAKHttpBackEndChart() {
    var http4xxCount = -1, http5xxCount = -1;
    getSumForColumn(getDataSourceUrl(6), 1, function (sum4xx) {
        http4xxCount = sum4xx;

        $('#4xxerror-footer').html(http4xxCount.toLocaleString("en-US") + " hit(s)");

        getSumForColumn(getDataSourceUrl(7), 1, function (sum5xx) {
            http5xxCount = sum5xx;
            $('#5xxerror-footer').html(http5xxCount.toLocaleString("en-US") + " hit(s)");

            createAKHttpBackEndChart(http4xxCount, http5xxCount);
        });
    });
}

function createAKHttpBackEndChart(http4xxCount, http5xxCount) {
    if (http4xxCount > -1 && http5xxCount > -1) {
        var httpBackEndPieChart = new google.visualization.PieChart($('#error_share')[0]);
        var hackishCompensation = 5;
        var data = google.visualization.arrayToDataTable([
            ['Error', 'Occurrence'],
            ['4xx Hits', {
                v: http4xxCount,
                f: http4xxCount.toLocaleString("en-US")
            }],
            ['5xx Hits', {
                v: http5xxCount,
                f: http5xxCount.toLocaleString("en-US")
            }]
        ]);

        var options = {
            "is3D": true,
            "legend": {
                "position": "left",
                "textStyle": {
                    "color": DefaultColors.DEFAULT_TEXT
                }
            },
            "hAxis": {
                "textStyle": {
                    "color": DefaultColors.DEFAULT_TEXT
                }
            },
            "vAxis": {
                "textStyle": {
                    "color": DefaultColors.DEFAULT_TEXT
                }
            },
            "backgroundColor": DefaultColors.BG,
            "colors": [DefaultColors.DEFAULT_AVG, DefaultColors.DEFAULT_MAX_NEG]
        };

        httpBackEndPieChart.draw(data, options);
    }
}

function getDataSourceUrl(chartIndex) {
    var range = parseInt($('#range_select').val());
    var timezone = $('#timezone_select').val();
    var period = $('#period_select').val();

    var queryString, dataSourceUrl;
    chartsData[chartIndex].cloudviz.range = range;
    chartsData[chartIndex].cloudviz.period = period;
    chartsData[chartIndex].cloudviz.timezone = timezone;

    queryString = JSON.stringify(chartsData[chartIndex].cloudviz);
    dataSourceUrl = 'http://' + window.location.host + CLOUD_VIZ_PATH + '?qs=' + queryString;

    //log(chartsData[chartIndex].container + ": " + dataSourceUrl);

    return dataSourceUrl;
}

$(document).ready(function () {
    log("Ready");
    updateDashboardTime();

    $('#myTabs a[href="#group-elb"]').tab('show') // Select tab by name

    $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
        var tab = (e.target.toString().split("#")[1]);
        selectTab = tab;
        console.log(selectTab);
        updateDashboard();
    })

    google.setOnLoadCallback(drawDashboard());

    // Add progress bars to `panel-body`
    $('div.panel-default .panel-body').each(function (index, panelObj) {
            if (panelObj.innerHTML.toString().trim() != 'Temporarily Unavailable') {
                panelObj.innerHTML = bsProgress;
            }
        }
    );

    var index;
    for (index = 0; index < charts.length; index++) {
        var containerId = '#' + charts[index].getContainerId();
        log("containerId=" + containerId);

        chartsData[index].cloudviz.range = range;
        chartsData[index].cloudviz.period = period;
        chartsData[index].cloudviz.timezone = timezone;
        var queryString = JSON.stringify(chartsData[index].cloudviz);
        var dataSourceUrl = 'http://' + window.location.host + CLOUD_VIZ_PATH + '?qs=' + queryString;

        // Default values for now
        var cloneChart = new google.visualization.ChartWrapper();
        cloneChart.setChartType(chartsData[index].chart_type || ChartType.LINE);
        cloneChart.setOptions(chartsData[index].chart_options || DEFAULT_LINE_OPTIONS);
        cloneChart.setDataSourceUrl(dataSourceUrl);
        cloneChart.setContainerId('modal-body');

        addClickHandler($(containerId).parent()[0], containerId, cloneChart);
    }

});

function addClickHandler(chart, containerId, cloneChart) {
    chart.addEventListener('click', function (e) {
        console.log("containerId.id=" + containerId);

        $('#my-modal-title').html($(containerId).parent().children().first().html());

        $('#my-modal-dialog').on('show.bs.modal', function () {
            cloneChart.draw();
        });

        $('#my-modal-dialog').on('shown.bs.modal', function () {
            $('#modal-body div').children().first().css(
                {
                    'margin': '0 auto'
                });
            $('#modal-body').focus();
        });

        $('#my-modal-dialog').on('hide.bs.modal', function () {
            $('#modal-body').html(bsDialogProgress);
        });

        $('#my-modal-dialog').modal('show');
    }, false);
}

const bsProgress = '<div class="progress progress-striped active center-block" style="width: 50%; margin-top: 75px;"><div class="progress-bar" style="width: 100%; "></div></div>';
const bsDialogProgress = '<div class="progress progress-striped active center-block" style="width: 50%; margin-top: 150px;"><div class="progress-bar" style="width: 100%; "></div></div>';

