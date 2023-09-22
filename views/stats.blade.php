<?php
$cpuMetrics = json_decode($httpClient->request("GET", "/api/v1/virtualservers/{$virtualserver->uuid}/metrics/cpu")->getBody()->getContents())->data->metrics;
$memMetrics = json_decode($httpClient->request("GET", "/api/v1/virtualservers/{$virtualserver->uuid}/metrics/memory")->getBody()->getContents())->data->metrics;
$netMetrics = json_decode($httpClient->request("GET", "/api/v1/virtualservers/{$virtualserver->uuid}/metrics/network")->getBody()->getContents())->data->metrics;
$dskMetrics = json_decode($httpClient->request("GET", "/api/v1/virtualservers/{$virtualserver->uuid}/metrics/disk")->getBody()->getContents())->data->metrics;
?>


<!-- Display $stats -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<canvas id="cpu" width="400" height="400" style="max-height: 400px;"></canvas>
<canvas id="mem" width="400" height="400" style="max-height: 400px;"></canvas>
<canvas id="dsk" width="400" height="400" style="max-height: 400px;"></canvas>
<canvas id="net" width="400" height="400" style="max-height: 400px;"></canvas>

<script>
    const formatWithPrefixMultiplier = (n, suffix = "") => {
        var ranges = [
            { divider: 1000 ** 4, suffix: 'T' },
            { divider: 1000 ** 3, suffix: 'G' },
            { divider: 1000 ** 2, suffix: 'M' },
            { divider: 1000 ** 1, suffix: 'k' }
        ];
        for (var i = 0; i < ranges.length; i++) {
            if (n >= ranges[i].divider) {
                return (Math.round((n / ranges[i].divider) * 100) / 100).toString() + " " + ranges[i].suffix + suffix;
            }
        }
        return (n ?? 0) + (suffix != "" && " " + suffix);
    }

    const formatFileSize = (bytes, decimalPoint) => {
        if (bytes == 0) return '0 Bytes';
        var k = 1000,
            dm = decimalPoint || 2,
            sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'],
            i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
    }

    const formatSpeed = (bytes, decimalPoint) => {
        if (bytes == 0) return '0 Bps';
        var k = 1024,
            dm = decimalPoint || 2,
            sizes = ['Bps', 'Kbps', 'Mbps', 'Gbps', 'Tbps', 'Pbps'],
            i = Math.floor(Math.log(bytes) / Math.log(k));

        if (sizes[i] != undefined) {
            return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
        } else {
            return bytes + ' Bps';
        }
    }

    var ctx = document.getElementById('cpu').getContext('2d');
    var myChart = new Chart(ctx, {
        type: 'line',
        options: {
            scales: {
                y: {
                    suggestedMin: 0,
                    beginAtZero: true,
                    ticks: {
                        callback: (value) => `${Math.round(value * 100) / 100} %`
                    }
                },
                x: {
                    display: false
                }
            }
        },
        data: {
            labels: [
                @foreach ($cpuMetrics as $cpuMetric)
                    new Date('{{ $cpuMetric->time }}').toLocaleTimeString(),
                @endforeach
            ],
            datasets: [{
                label: 'CPU Usage',
                fill: true,
                data: [
                    @foreach ($cpuMetrics as $cpuMetric)
                        '{{ $cpuMetric->cpu }}',
                    @endforeach
                ],
                backgroundColor: [
                    'rgba(255, 99, 132, 0.2)',
                ],
                borderColor: [
                    'rgba(255, 99, 132, 1)',
                ],
                borderWidth: 1
            }]
        },
    });

    var ctx = document.getElementById('mem').getContext('2d');
    var myChart = new Chart(ctx, {
        type: 'line',
        options: {
            scales: {
                y: {
                    suggestedMin: 0,
                    beginAtZero: true,
                    ticks: {
                        callback: (value) => formatFileSize(value * 1000000000)
                    }
                },
                x: {
                    display: false
                }
            }
        },
        data: {
            labels: [
                @foreach ($memMetrics as $memMetric)
                    new Date('{{ $memMetric->time }}').toLocaleTimeString(),
                @endforeach
            ],
            datasets: [{
                label: 'Memory Usage',
                fill: true,
                data: [
                    @foreach ($memMetrics as $memMetric)
                        '{{ $memMetric->memory }}',
                    @endforeach
                ],
                backgroundColor: [
                    'rgba(54, 162, 235, 0.2)',
                ],
                borderColor: [
                    'rgba(54, 162, 235, 1)',
                ],
                borderWidth: 1
            }]
        },
    });

    var ctx = document.getElementById('dsk').getContext('2d');
    var myChart = new Chart(ctx, {
        type: 'line',
        options: {
            scales: {
                y: {
                    suggestedMin: 0,
                    beginAtZero: true,
                    ticks: {
                        callback: (value) => formatWithPrefixMultiplier(value * 10, 'B/s')
                    }
                },
                x: {
                    display: false
                }
            }
        },
        data: {
            labels: [
                @foreach ($dskMetrics as $dskMetric)
                    new Date('{{ $dskMetric->time }}').toLocaleTimeString(),
                @endforeach
            ],
            datasets: [{
                label: 'Disk Read',
                fill: true,
                data: [
                    @foreach ($dskMetrics as $dskMetric)
                        '{{ $dskMetric->read }}',
                    @endforeach
                ],
                backgroundColor: [
                    'rgba(255, 206, 86, 0.2)',
                ],
                borderColor: [
                    'rgba(255, 206, 86, 1)',
                ],
                borderWidth: 1
            },{
                label: 'Disk Write',
                fill: true,
                data: [
                    @foreach ($dskMetrics as $dskMetric)
                        '{{ $dskMetric->write }}',
                    @endforeach
                ],
                backgroundColor: [
                    'rgba(255, 256, 86, 0.2)',
                ],
                borderColor: [
                    'rgba(255, 256, 86, 1)',
                ],
                borderWidth: 1
            }]
        },
    });

    var ctx = document.getElementById('net').getContext('2d');
    var myChart = new Chart(ctx, {
        type: 'line',
        options: {
            scales: {
                y: {
                    suggestedMin: 0,
                    beginAtZero: true,
                    ticks: {
                        callback: (value) => formatSpeed(value * 10)
                    }
                },
                x: {
                    display: false
                }
            }
        },
        data: {
            labels: [
                @foreach ($netMetrics as $netMetric)
                    new Date('{{ $netMetric->time }}').toLocaleTimeString(),
                @endforeach
            ],
            datasets: [{
                label: 'Network In',
                fill: true,
                data: [
                    @foreach ($netMetrics as $netMetric)
                        '{{ $netMetric->in }}',
                    @endforeach
                ],
                backgroundColor: [
                    'rgba(49, 196, 141, 0.2)',
                ],
                borderColor: [
                    'rgba(49, 196, 141, 1)',
                ],
                borderWidth: 1
            },{
                label: 'Network Out',
                fill: true,
                data: [
                    @foreach ($netMetrics as $netMetric)
                        '{{ $netMetric->out }}',
                    @endforeach
                ],
                backgroundColor: [
                    'rgba(79, 91, 133, 0.2)',
                ],
                borderColor: [
                    'rgba(79, 91, 133, 1)',
                ],
                borderWidth: 1
            }]
        },
    });
</script>
