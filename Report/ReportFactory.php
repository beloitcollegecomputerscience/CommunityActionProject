<?php

/**
 * Class ViewFactory
 * Used to generate this plugins various views
 */
class ReportFactory
{
    /**
     * @param $surveys the data needed to populate the report
     * @return string the html content we want rendered as the actual report
     */
    public function buildReportUI($surveys, $yearToFeature)
    {
        //Add google charts  dependency and all chart types we need
        $content = <<<HTML
<script type="text/javascript" src="https://www.google.com/jsapi"></script>
<script type="text/javascript">
    google.load("visualization", "1.1", {packages:["bar", "corechart"]});
</script>
HTML;
        $content .= '<div class="container">';
        $content .= "<br/><h1>Community Action Annual Report " . $yearToFeature . "</h1><br/>";
        $i = 0;
        foreach ($surveys as $survey) {
            $content .= '<br/>';
            //Program Title
            $content .= "<h2>Program: " . $survey["programTitle"] . "</h2>";
            //Program Description
            $content .= '<p>' . $survey['programDescription'] . '</p>';

            //Survey Title
            $content .= "<br /><h2>" . $survey['title'] . "</h2>";

            //Check if survey uses tokens
            if (!is_null($survey['tokenCount'])) {
                //Total tokens originally sent out
                $content .= "Surveys sent out: " . $survey['tokenCount'] . "<br/>";
            } else {
                $content .= "Open survey.<br/>";
            }

            //Total survey responses in feature year
            $content .= "Responses received in feature year: " . $survey['totalResponses'] . "<br/>";

            //Loop for each registered question group
            foreach ($survey['questionGroups'] as $questionGroup) {
                //Check for if question group actually exists
                if (!empty($questionGroup['questions'])) {

                    $questionGroupTitle = $questionGroup['questionGroupTitle'];
                    $content .= "<h3>$questionGroupTitle</h3><br/>";

                    $content .= '<p>' . $questionGroup['description'] . '</p><br/>';

                    //Loop for each question inside of question group
                    foreach ($questionGroup['questions'] as $question) {
                        $content .= "<h4>" . $question['title'] . "</h4><br/>";

                        //**Generate Charts**

                        //Check for if feature year has data to show
                        if (!is_null($question['answerCount'][$yearToFeature])) {
                            $content .= '<div class="row">';
                            //Draw Column Chart
                            $content .= $this->generateColumnChart($question['answerCount'][$yearToFeature], $i++);
                            //Draw Pie Chart
                            $content .= $this->generatePieChart($question['answerCount'][$yearToFeature], $i++);

                            $content .= '</div>';
                        } else {
                            //No Data for feature year
                            $content .= "<h2>No data for selected feature year.</h2>";
                        }

                        //Get first year of valid data
                        $firstYearData = reset($question['answerCount']);

                        //Always Draw Line Chart
                        $content .= '<div class="row">' . $this->generateAreaChart($question['answerCount'], $i++, $firstYearData['year']) . '</div>';

                        //Build up possible answers list
                        $x = !is_null($question['answerCount'][$firstYearData['year']]['0']['0']['A0']) ? 0 : 1;
                        $content .= '<div style="margin-left: 60px">';
                        foreach ($question['possibleAnswers'] as $answer) {
                            $content .= '<br/>A' . $x . " : " . $answer;
                            $x++;
                        }
                        $content .= "</div><br /><br/>";
                        $i += 2;
                    }
                }

            }
        }
        $content .= '</div>';
        return $content;
    }

    /**
     * Generates a google pie chart
     * @param $graphData question data to generate
     * @param $number numberOf Chart we are generating
     * @return string The html Markup for the graph
     */
    private function generatePieChart($questionData, $number)
    {
        $content = "";
        $content .= <<<HTML
                    <script type="text/javascript">


                      google.setOnLoadCallback(drawPieChart);

                      function drawPieChart() {
                        var data = new google.visualization.arrayToDataTable(
HTML;

        //Build the JSON Data for the graph
        $graphData = array();
        //Push graph header data first
        array_push($graphData, array('Answer', 'Count', array('role' => 'style')));
        //Check if question had no answer option
        $i = !is_null($questionData['0']['0']['A0']) ? 0 : 1;
        foreach ($questionData['0'] as $responseData) {
            array_push($graphData, array('A' . $i, $responseData['A' . $i], '#b87333'));
            $i++;
        }
        $content .= json_encode($graphData);

        $content .= <<<HTML
                        );

                        var options = {
                          width: 450,
                          height: 400
                        };

                      var chart = new google.visualization.PieChart(document.getElementById('dual_y_div_$number'));
                      chart.draw(data, options);
                    };
                </script>
                <div class="dual_y_div pull-left" id="dual_y_div_$number"></div>
HTML;
        return $content;
    }

    /**
     * Generates a google column chart
     * @param $graphData question data to generate
     * @param $number numberOf Chart we are generating
     * @return string The html Markup for the graph
     */
    private function generateColumnChart($questionData, $number)
    {
        $content = "";
        $content .= <<<HTML
                    <script type="text/javascript">

                      google.setOnLoadCallback(drawColumnChart);

                      function drawColumnChart() {
                        var data = new google.visualization.arrayToDataTable(
HTML;

        //Build the JSON Data for the graph
        $graphData = array();

        //Push graph header data first
        array_push($graphData, array('Answer', 'Count', array('role' => 'style')));
        //Check if question had no answer option
        $i = !is_null($questionData['0']['0']['A0']) ? 0 : 1;

//        print_r($i);
        foreach ($questionData['0'] as $responseData) {
            array_push($graphData, array('A' . $i, $responseData['A' . $i], '#b87333'));
            $i++;
        }

        $content .= json_encode($graphData);

        $content .= <<<HTML
                        );

                        var options = {
                          width: 450,
                          height: 400,
                          series: {
                            0: { axis: 'count' }, // Bind series 0 to an axis named 'count'.
                          },
                          axes: {
                            y: {
                              count: {label: 'Count'}, // Left y-axis.
                            }
                          }
                        };

                      var chart = new google.charts.Bar(document.getElementById('dual_y_div_$number'));
                      chart.draw(data, options);
                    };
                </script>
                <div class="dual_y_div pull-right" id="dual_y_div_$number"></div>
HTML;
        return $content;
    }

    /**
     * Generates a google column chart
     * @param $graphData question data to generate
     * @param $number numberOf Chart we are generating
     * @return string The html Markup for the graph
     */
    private function generateAreaChart($questionData, $number, $firstYear)
    {
        $content = "";
        $content .= <<<HTML
                    <script type="text/javascript">

                      google.setOnLoadCallback(drawColumnChart);

                      function drawColumnChart() {
                        var data = new google.visualization.DataTable();
HTML;

        $content .= "data.addColumn('date', 'Year');";

        //Build the JSON Data for the graph
        $graphData = array();
        $finalData = array();

        $onFirstYear = true;
        foreach ($questionData as $currentYearData) {

            $currentYear = $currentYearData['year'];

            $i = !is_null($questionData[$currentYear]['0']['0']['A0']) ? 0 : 1;

            $graphData[$currentYear] = array();
            $jsDateString = $currentYear;
            array_push($graphData[$currentYear], $jsDateString);

            foreach ($currentYearData['0'] as $answerValue) {
                if ($onFirstYear) {
                    $content .= "data.addColumn('number', 'A" . $i . "');";
                }
                array_push($graphData[$currentYear], $answerValue['A' . $i]);
                $i++;
            }
            array_push($finalData, $graphData[$currentYear]);
            $onFirstYear = false;
        }
        $content .= "data.addRows(";

        //Replace year strings with javascript date time constructors
        $value_arr = array();
        $replace_keys = array();
        $i = 0;
        foreach ($finalData as $key => &$value) {
            // Store JS Date creation string.
            array_push($value_arr, 'new Date(' . $value['0'] . ', 0, 0)');
            // Replace year string with a 'unique' special key.
            $value['0'] = '%YEAR' . $i . '%';
            // Later on, we'll look for the value, and replace it.
            array_push($replace_keys, '"' . $value['0'] . '"');
            $i++;
        }

        //Encode array
        $json = json_encode($finalData);

        //Replace special place holder values with constructor
        $json = str_replace($replace_keys, $value_arr, $json);

        //Actually add formatted graph data to return string
        $content .= $json;

        $content .= <<<HTML
                );

                        var options = {
                            height: 400,
                            width: 1000,
          hAxis: {title: 'Year',  titleTextStyle: {color: '#333'}, format: 'MMM y'},
          vAxis: {minValue: 0}
        };

                      var chart = new google.visualization.LineChart(document.getElementById('dual_y_div_$number'));
                      chart.draw(data, options);
                    };
                </script>
                <div class="dual_y_div" id="dual_y_div_$number"></div>
HTML;
        return $content;
    }

}
