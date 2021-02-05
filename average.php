<?php

date_default_timezone_set('America/New_York');

$locations = array('DUM', 'LIC', 'VAL', 'CAL');

$weekday = (int) $_GET['weekday'];
$start = mktime(6, 0, 0, 9, 6 + $weekday, 2020);
$end = $start + 64800;

$db = new PDO('sqlite:db.sqlite');

$capacity = 0;

$accumulator = array();
foreach ($locations as $location) {
    $accumulator[$location] = array(0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0);
}

$counter = array();
foreach ($locations as $location) {
    $counter[$location] = array(0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0);
}

for ($low = $start; $low < time(); $low += 604800) {
    $high = $low + 64800;
    $rows = $db->query("SELECT * FROM data WHERE time BETWEEN {$low} AND {$high}")->fetchAll();
    foreach ($rows as $row) {
        $location = $row['location'];
        $hour = date('G', $row['time']);
        $accumulator[$location][$hour] += $row['count'];
        ++$counter[$location][$hour];
        if ($row['capacity'] > $capacity) {
            $capacity = $row['capacity'];
        }
    }
}

$averages = array();
foreach($locations as $location) {
    for($i = 0; $i < 24; ++$i) {
        $averages[$location][$i] = $counter[$location][$i] != 0 ? $accumulator[$location][$i] / $counter[$location][$i] : 0;
    }
}

foreach ($averages as $loc => $val) {
    for ($i = 0; $i < 24; ++$i) {
        $dt = date('r', mktime($i));
        $data[$loc]['data'][] = "{x: new Date('{$dt}'), y: {$val[$i]}}";
        $data[$loc]['labels'][] = "'{$dt}'";
    }
}

// echo '<pre>';
// print_r($averages);

foreach ($data as $location => $locationData) {
    $data[$location]['data'] =  implode(',', $data[$location]['data']);
    $data[$location]['labels'] =  implode(',', $data[$location]['labels']);
}

?>
<!doctype html>
<html lang="en" style="height: 100%; margin: 0; padding: 0;">
<head>
  <meta charset="utf-8">
  <title>Cliffs Tracker</title>
</head>
<body style="height: 100%; margin: 0; padding: 0; overflow: hidden;">
<h1 style="text-align: center; margin: 0; padding: 0;">
  <?php echo date ('D', $start); ?>
  <a href="/cliffs-tracker">Home</a>
</h1>
  <canvas id="chart" style="width: 100%; height: calc(100% - 2em);"></canvas>
  <script src="/cliffs-tracker/node_modules/moment/min/moment.min.js"></script>
  <script src="/cliffs-tracker/node_modules/chart.js/dist/Chart.min.js"></script>
  <script src="/cliffs-tracker/node_modules/chartjs-plugin-annotation/chartjs-plugin-annotation.min.js"></script>
  <script>
      var ctx = document.getElementById('chart');
      var myLineChart = new Chart(ctx, {
          type: 'line',
          labels: [
<?php for ($i = 0; $i <= 14; ++$i) { ?>
              new Date('<?php echo date('r', $start + $i * 3600); ?>'),
<?php } ?>
          ],
          data: {
              datasets: [
<?php if (isset($data['DUM']['data'])) { ?>
                  {
                      label: 'DUMBO',
                      data: [
                          <?php echo $data['DUM']['data']; ?>
                        ],
                      borderColor: 'rgb(255, 0, 0)',
                      backgroundColor: 'rgba(255, 0, 0, 0.1)'
                  },
<?php } ?>
<?php if (isset($data['LIC']['data'])) { ?>
                  {
                      label: 'LIC',
                      data: [
                          <?php echo $data['LIC']['data']; ?>
                        ],
                      borderColor: 'rgb(0, 255, 0)',
                      backgroundColor: 'rgba(0, 255, 0, 0.1)'
                  },
<?php } ?>
<?php if (isset($data['VAL']['data'])) { ?>
                  {
                      label: 'Valhalla',
                      data: [
                          <?php echo $data['VAL']['data']; ?>
                        ],
                      borderColor: 'rgb(255, 165, 0)',
                      backgroundColor: 'rgba(255, 165, 0, 0.1)'
                  },
<?php } ?>
<?php if (isset($data['CAL']['data'])) { ?>
                  {
                      label: 'Callowhill',
                      data: [
                          <?php echo $data['CAL']['data']; ?>
                        ],
                      borderColor: 'rgb(0, 0, 255)',
                      backgroundColor: 'rgba(0, 0, 255, 0.1)'
                  },
<?php } ?>
              ]
          },
          options: {
<?php if (date('D') == date('D', $start)) { ?>
              annotation: {
                  annotations: [
                      {
                          id: 'vline',
                          type: 'line',
                          mode: 'vertical',
                          scaleID: 'x-axis-0',
                          value: new Date(),
                          borderColor: 'black',
                          borderWidth: 3,
                          label: {
                              backgroundColor: 'black',
                              content: 'Now',
                              enabled: true
                          }
                      }
                  ]
              },
<?php } ?>
              elements: {
                  point: {
                      radius: 0,
                      hitRadius: 10
                  }
              },
              scales: {
                  xAxes: [{
                      type: 'time',
                      time: {
                          unit: 'hour'
                      }
                  }],
                  yAxes: [{
                      ticks: {
                        suggestedMin: 0,
                        suggestedMax: <?php echo $capacity; ?>
                      }
                  }]
              }
          }
      });
  </script>
</body>
</html>
