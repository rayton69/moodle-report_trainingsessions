<?php

/**
 * direct log construction implementation
 *
 */
ob_start();

require_once($CFG->dirroot.'/blocks/use_stats/locallib.php');
require_once($CFG->dirroot.'/report/trainingsessions/locallib.php');
require_once($CFG->dirroot.'/report/trainingsessions/selector_form.php');

$id = required_param('id', PARAM_INT) ; // the course id

// calculate start time

$selform = new SelectorForm($id, 'course');
if ($data = $selform->get_data()) {
} else {
    $data = new StdClass;
    $data->from = optional_param('from', -1, PARAM_NUMBER);
    $data->userid = optional_param('userid', $USER->id, PARAM_INT);
    $data->fromstart = optional_param('fromstart', 0, PARAM_BOOL);
    $data->output = optional_param('output', 'html', PARAM_ALPHA);
    $data->groupid = optional_param('group', '0', PARAM_ALPHA);
}

$context = context_course::instance($id);

// calculate start time

if ($data->from == -1 || @$data->fromstart) { // maybe we get it from parameters
    $data->from = $course->startdate;
}

if ($data->output == 'html') {
    echo $OUTPUT->box_start('block');
    $selform->set_data($data);
    $selform->display();
    echo $OUTPUT->box_end();
}

// compute target group

$allgroupsaccess = has_capability('moodle/site:accessallgroups', $context);

if (!$allgroupsaccess) {
    $mygroups = groups_get_my_groups();

    $allowedgroupids = array();
    if ($mygroups) {
        foreach ($mygroups as $g) {
            $allowedgroupids[] = $g->id;
        }
        if (empty($data->groupid) || !in_array($data->groupid, $allowedgroupids)) {
            $data->groupid = $allowedgroupids[0];
        }
    } else {
        echo $OUTPUT->notification(get_string('errornotingroups', 'report_trainingsessions'));
        echo $OUTPUT->footer($course);
        die;
    }
} else {
    if ($allowedgroups = groups_get_all_groups($COURSE->id, $USER->id, 0, 'g.id,g.name')) {
        $allowedgroupids = array_keys($allowedgroups);
    }
}

if ($data->groupid) {
    $targetusers = get_enrolled_users($context, '', $data->groupid);
} else {
    $targetusers = get_enrolled_users($context);
    if (count($targetusers) > 100) {
        if (!empty($allowedgroupids)) {
            $OUTPUT->notification(get_string('errorcoursetoolarge', 'report_trainingsessions'));
            $data->groupid = $allowedgroupids[0];
            // refetch again after eventual group correction
            $targetusers = get_enrolled_users($context, '', $data->groupid);
        } else {
            // DO NOT COMPILE 
            echo $OUTPUT->notification('Course is too large and no groups in. Cannot compile.');
            echo $OUTPUT->footer($course);
            die;
        }
    }
}

// filter out non compiling users
$compiledusers = array();
foreach ($targetusers as $u) {
    if (has_capability('report/trainingsessions:iscompiled', $context, $u->id)) {
        $compiledusers[$u->id] = $u;
    }
}

// get course structure

$coursestructure = reports_get_course_structure($course->id, $items);

// print result

if ($data->output == 'html'){

    require_once('htmlrenderers.php');

    echo "<link rel=\"stylesheet\" href=\"reports.css\" type=\"text/css\" />";

    if (!empty($compiledusers)) {
        foreach($compiledusers as $auser) {

            $logusers = $auser->id;
            $logs = use_stats_extract_logs($data->from, time(), $auser->id, $course->id);
            $aggregate = use_stats_aggregate_logs($logs, 'module');
            
            if (empty($aggregate['sessions'])) $aggregate['sessions'] = array();

            $data->items = $items;
            $data->done = 0;
            
            if (!empty($aggregate)){
                foreach(array_keys($aggregate) as $module){
                    // exclude from calculation some pseudo-modules that are not part of 
                    // a course structure.
                    if (preg_match('/course|user|upload|sessions/', $module)) continue;
                    $data->done += count($aggregate[$module]);
                }
                $data->sessions = 0 + count(@$aggregate['sessions']);
            } else {
                $data->sessions = 0;
            }
            if ($data->done > $items) $data->done = $items;

            $data->linktousersheet = 1;
            training_reports_print_header_html($auser->id, $course->id, $data, true);

        }
    }

    $options['id'] = $course->id;
    $options['groupid'] = $data->groupid;
    $options['from'] = $data->from; // alternate way
    $options['output'] = 'xls'; // ask for XLS
    $options['asxls'] = 'xls'; // force XLS for index.php
    $options['view'] = 'course'; // force course view

    $params = array('id' => $course->id, 'view' => 'course', 'groupid' => $data->groupid, 'from' => $data->from, 'output' => 'xls', 'asxls' => 1);
    $url = new moodle_url('/report/trainingsessions/index.php', $params);
    echo '<br/><center>';
    // echo count($targetusers).' found in this selection';
    echo $OUTPUT->single_button($url, get_string('generateXLS', 'report_trainingsessions'), 'get');
    echo '</center>';
    echo '<br/>';
    
} else {

    require_once($CFG->libdir.'/excellib.class.php');
    require_once('xlsrenderers.php');

    /// generate XLS

    if ($data->groupid) {
        $filename = 'training_group_'.$data->groupid.'_report_'.date('d-M-Y', time()).'.xls';
    } else {
        $filename = 'training_course_'.$id.'_report_'.date('d-M-Y', time()).'.xls';
    }

    $workbook = new MoodleExcelWorkbook("-");
    if (!$workbook) {
        die("Null workbook");
    }
    // Sending HTTP headers
    ob_end_clean();
    $workbook->send($filename);

    $xls_formats = training_reports_xls_formats($workbook);
    $startrow = 15;

    foreach ($compiledusers as $auser) {

        $row = $startrow;
        $worksheet = training_reports_init_worksheet($auser->id, $row, $xls_formats, $workbook);

        $logusers = $auser->id;
        $logs = use_stats_extract_logs($data->from, time(), $auser->id, $course->id);
        $aggregate = use_stats_aggregate_logs($logs, 'module');

        if (empty($aggregate['sessions'])) $aggregate['sessions'] = array();
        
        $overall = training_reports_print_xls($worksheet, $coursestructure, $aggregate, $done, $row, $xls_formats);
        $data->items = $items;
        $data->done = $done;
        $data->elapsed = $overall->elapsed;
        $data->events = $overall->events;
        training_reports_print_header_xls($worksheet, $auser->id, $course->id, $data, $xls_formats);    

        $worksheet = training_reports_init_worksheet($auser->id, $startrow, $xls_formats, $workbook, 'sessions');
        training_reports_print_sessions_xls($worksheet, 15, $aggregate['sessions'], $COURSE->id, $xls_formats);
        training_reports_print_header_xls($worksheet, $auser->id, $course->id, $data, $xls_formats);

    }
    $workbook->close();
}

