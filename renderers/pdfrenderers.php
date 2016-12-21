<?php
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

define('PDF_WIDTH_FACTOR', 1.85);

// Protects core cron from reloading here the actualized TCPDF class.
if (!class_exists('TCPDF')) {
    require_once($CFG->dirroot.'/local/vflibs/tcpdflib.php');
}

/**
 * A4_embedded delivery report
 *
 * @package    report_trainingsessions
 * @category   report
 * @copyright  Valery Fremaux (valery.fremaux@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.'); // It must be included from view.php
}

/**
 * Checks if vertical position needs to be recalculated because reaching the end of the page, and
 * generates the PDF page break
 * @param object $pdf the TCPDF document
 * @param int $y the present vertical position issued from last write
 * @param boolref $isnewpage tels the calling environment if a new page has been required
 * @param bool $isfront let the caller tell we need to add a new "front document header" and not a "inner header".
 * @param bool $islast if set to true for the last page, will print the nuber of total pages of the document anyway
 * @return the new y position
 */
function report_trainingsessions_check_page_break(&$pdf, $y, &$isnewpage, $isfront = false, $last = false) {
    static $pdfpage = 1;

    $config = get_config('report_trainingsessions');

    list($a,$b,$w,$h) = report_trainingsessions_get_object_coords($pdf->getDocOrientation(), $pdf->getDocformat(), 'pagenum');

    if ($y > $config->pdfpagecutoff) {
        if (!$last) {
            $pdf->writeHTMLCell($a,$b,$w,$h, get_string('pdfpage', 'report_trainingsessions', $pdfpage).' '.$pdfpage, 0, 0, 0, true, 'C');
            $pdfpage++;
            $pdf->addPage($pdf->getDocOrientation(), $pdf->getDocFormat(), true);
            $y = $config->pdfabsoluteverticaloffset;
            // Add header image.
            if ($isfront) {
                report_trainingsessions_print_header($pdf);
            } else {
                report_trainingsessions_print_header($pdf, 'inner');
            }
            // Add footer image.
            report_trainingsessions_print_footer($pdf);
            // Add images and lines.
            report_trainingsessions_draw_frame($pdf);
            $isnewpage = true;
        }
    }

    if ($last) {
        $pdf->writeHTMLCell($a,$b,$w,$h, get_string('pdfpage', 'report_trainingsessions', $pdfpage).' '.$pdfpage, 0, 0, 0, true, 'C');
    }

    return $y;
}

/**
 * Sends text to output given the following params.
 *
 * @param stdClass $pdf
 * @param int $x horizontal position
 * @param int $y vertical position
 * @param char $align L=left, C=center, R=right
 * @param string $font any available font in font directory
 * @param char $style ''=normal, B=bold, I=italic, U=underline
 * @param int $size font size in points
 * @param string $text the text to print
 */
function report_trainingsessions_print_text(&$pdf, $text, $x, $y, $l = '', $h = '', $align = 'L', $font='freeserif', $style = '', $size=10) {

    if (preg_match('/^<h>/', $text)) {
        $text = str_replace('<h>', '', $text);
        $size += 2;
        $style = 'B';
    }

    $pdf->setFont($font, $style, $size);
    $pdf->writeHTMLCell($l, $h, $x, $y, $text, 0, 1, 0, true, $align);

    return $pdf->getY();
}

/**
 * Sends text to output given the following params.
 *
 * @param stdClass $pdf
 * @param int $y vertical position
 * @param int $table the table with all data
 * @return the new Y pos after the log line has been written
 */
function report_trainingsessions_print_overheadline(&$pdf, $y, &$table) {

    $x = 10;
    $pdf->SetXY($x, $y);
    $pdf->SetFontSize(10);
    $border = array('LTBR' => array('width' => 2, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(255, 255, 255)));

    $i = 0;
    foreach ($table->pdfhead1 as $header) {
        if (!empty($table->pdfprintinfo[$i])) {
            list($r, $v, $b) = tcpdf_decode_html_color(@$table->pdfbgcolor1[$i]);
            $pdf->SetFillColor($r, $v, $b);
            list($r, $v, $b) = tcpdf_decode_html_color(@$table->pdfcolor1[$i], true);
            $pdf->SetTextColor($r, $v, $b);
            $cellsize = str_replace('%', '', $table->pdfsize1[$i]) * PDF_WIDTH_FACTOR;
            $pdf->writeHTMLCell($cellsize, 0, $x, $y, $header, $border, 0, 1, true, $table->pdfalign1[$i]);
            $x += $cellsize;
        }
        $i++;
    }

    $pdf->SetFillColor(255);
    $pdf->SetTextColor(0);

    return $pdf->getY();
}

/**
 * Sends text to output given the following params.
 *
 * @param stdClass $pdf
 * @param int $y vertical position
 * @param int $table the table with all data
 * @return the new Y pos after the log line has been written
 */
function report_trainingsessions_print_headline($pdf, $y, &$table) {

    $x = 10;
    $pdf->SetXY($x, $y);
    if (!empty($table->level) && $table->level > 1) {
        $pdf->SetFontSize(11 + $table->level - 1);
    }
    $border = array('LTBR' => array('width' => 2, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(255, 255, 255)));

    $outy = 0;
    $count = count($table->pdfhead2);
    for ($i = 0 ; $i < $count ; $i++) {
        $header = $table->pdfhead2[$i];
        $bgcolor = (empty($table->pdfbgcolor2[$i])) ? '#606060' : $table->pdfbgcolor2[$i];
        $color = (empty($table->pdfcolor2[$i])) ? '#ffffff' : $table->pdfcolor2[$i];
        if ($table->pdfprintinfo[$i]) {
            $cellsize = str_replace('%', '', $table->pdfsize2[$i]) * PDF_WIDTH_FACTOR;
            list($r, $v, $b) = tcpdf_decode_html_color($bgcolor);
            $pdf->SetFillColor($r, $v, $b);
            list($r, $v, $b) = tcpdf_decode_html_color($color, true);
            $pdf->SetTextColor($r, $v, $b);
            $pdf->writeHTMLCell($cellsize, 0, $x, $y, $header, $border, 2, 1, true, $table->pdfalign2[$i]);
            $outy = max($outy, $pdf->GetY());
            $x += $cellsize;
        }
    }
    $lasty = $pdf->GetY();
    if ($outy > $lasty) {
        debug_trace("in $y Out $outy / Last ".$lasty);
    }
    $pdf->SetFillColor(255);
    $pdf->SetTextColor(0);

    return $outy;
}

/**
 * Sends text to output given the following params.
 *
 * @param stdClass $pdf
 * @param int $y vertical position
 * @param int $table the table with all data
 * @return the new Y pos after the log line has been written
 */
function report_trainingsessions_print_studentline($pdf, $y, $username) {

    $x = 10;
    $pdf->SetXY($x, $y);
    $pdf->SetFontSize(12);

    $pdf->SetFillColor(230);
    $pdf->SetTextColor(0);

    $pdf->writeHTMLCell($cellsize, 0, $x, $y, $username, null, 0, 1, true);

    $pdf->SetFillColor(255);
    $pdf->SetTextColor(0);

    return $pdf->getY();
}

/**
 * Sends text to output given the following params.
 *
 * @param stdClass $pdf
 * @param int $y vertical position
 * @param array $dataline the data to print
 * @param objectref $table the table with all data
 * @return the new Y pos after the log line has been written
 */
function report_trainingsessions_print_dataline(&$pdf, $y, $dataline, &$table) {

    $x = 10;
    $pdf->SetXY($x, $y);

    $i = 0;
    $outy = $y;
    foreach ($dataline as $datum) {
        // debug_trace("Data Cell $i: ".$table->pdfprintinfo[$i]."<br/>\n");
        if ($table->pdfprintinfo[$i]) {
            // debug_trace("Printing Data Cell\n");
            $cellsize = str_replace('%', '', @$table->pdfsize2[$i]) * PDF_WIDTH_FACTOR;

            $bgcolor = (empty($table->pdfbgcolor[$i])) ? '#ffffff' : $table->pdfbgcolor[$i];
            $color = (empty($table->pdfcolor[$i])) ? '#000000' : $table->pdfcolor[$i];
            list($r, $v, $b) = tcpdf_decode_html_color($bgcolor);
            $pdf->SetFillColor($r, $v, $b);
            list($r, $v, $b) = tcpdf_decode_html_color($color, true);
            $pdf->SetTextColor($r, $v, $b);

            if (is_object($datum) || isset($span)) {
                // debug_trace("Data $i) Print start<br/>\n");
                if (!empty($datum->colspan)) {
                    // debug_trace("Data $i) init $spantoreach <br/>\n");
                    // This is a span start, save content and span to reach
                    $content = ''.@$datum->text;
                    $spantoreach = $datum->colspan;
                    $span = 1;
                    $align = $table->pdfalign2[$i];
                    $size = $cellsize;
                    $i++;
                    continue;

                } elseif (!isset($span)) {
                    // Non spanning single cell
                    // debug_trace("Data $i) normal out ($x, $y, $cellsize) with ".htmlentities($datum->text)."<br/>\n");
                    $pdf->writeHTMLCell($cellsize, 0, $x, $y, $datum->text, 0, 2, true, true, @$table->pdfalign2[$i]);
                    $outy = max($outy, $pdf->GetY());
                    $x += $cellsize;
                    $i++;
                    continue;
                }

                if ($span < $spantoreach) {
                    $span++;
                    // debug_trace("Data $i) Up span to $span<br/>\n");
                    $size += str_replace('%', '', $table->pdfsize2[$i]) * PDF_WIDTH_FACTOR;
                    $i++;
                    continue;
                }

                if ($span == $spantoreach) {
                    unset($spantoreach);
                    unset($span);
                    // debug_trace("Data $i) resolve at ($x,$y, $cellsize) with ".htmlentities($content)."<br/>\n");
                    $pdf->writeHTMLCell($size, 0, $x, $y, $content, 0, 2, true, true, $align);
                    $outy = max($outy, $pdf->GetY());
                    $x += $size;
                    $i++;
                    continue;
                }

                debug_trace("Data $i) Weird case<br/>\n");
            } else {
                debug_trace("Data $i) scalar out ($x, $y, $cellsize) with ".htmlentities($datum)."\n");
                // $datum = ''.@$table->pdfdata[$line][$i];
                $pdf->writeHTMLCell($cellsize, 0, $x, $y, $datum, 0, 2, true, true, $table->pdfalign2[$i]);
                $outy = max($outy, $pdf->GetY());
                $x += $cellsize;
            }
        }
        $i++;
    }

    return $outy;
}

/**
 * Sends text to output given the following params with a special summarizer styling (highlighted).
 *
 * @param stdClass $pdf
 * @param int $y vertical position
 * @param array $dataline the data to print
 * @param objectref $table the table with all column definitions and attributes
 * @return the new Y pos after the log line has been written
 */
function report_trainingsessions_print_sumline($pdf, $y, $dataline, &$table) {

    $x = 10;
    $lineincr = 8;
    $pdf->SetXY($x, $y);
    $border = array('LTBR' => array('width' => 2, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(255, 255, 255)));

    $sumbgcolor = '#ffffff';
    $sumcolor = '#000000';

    $i = 0;
    foreach ($dataline as $datum) {
        // debug_trace("Cell $i: ".$table->pdfprintinfo[$i]."<br/>\n");
        $cellsize = str_replace('%', '', @$table->pdfsize2[$i]) * PDF_WIDTH_FACTOR;
        if (is_object($datum) || isset($span)) {
            if ($table->pdfprintinfo[$i]) {
                if (!empty($datum->colspan)) {
                    // This is a span start, save content and span to reach
                    $content = $datum->text;
                    $spantoreach = $datum->colspan;
                    $span = 1;
                    $align = $table->pdfalign3[$i];
                    $size = $cellsize;
                    $sumbgcolor = @$table->pdfbgcolor3[$i];
                    $sumcolor = @$table->pdfcolor3[$i];
                    // debug_trace("$i) init $spantoreach <br/>\n");
                    $i++;
                    continue;
                } elseif(!isset($span)) {
                    // Non spanning single cell
                    // debug_trace("$i) normal out ($x, $y, $cellsize) with ".htmlentities($datum->text)."<br/>\n");
                    list($r, $v, $b) = tcpdf_decode_html_color(@$table->pdfbgcolor3[$i]);
                    $pdf->SetFillColor($r, $v, $b);
                    list($r, $v, $b) = tcpdf_decode_html_color(@$table->pdfcolor3[$i], true);
                    $pdf->SetTextColor($r, $v, $b);
                    $pdf->writeHTMLCell($cellsize, $lineincr, $x, $y, $datum->text, $border, 0, 1, true, @$table->pdfalign3[$i], true);
                    $x += $cellsize;
                    $i++;
                    continue;
                }
                if ($span < $spantoreach) {
                    $span++;
                    debug_trace("$i) up $span<br/>\n");
                    $size += str_replace('%', '', $table->pdfsize2[$i]) * PDF_WIDTH_FACTOR;
                }
                if ($span == $spantoreach) {
                    unset($spantoreach);
                    unset($span);
                    // debug_trace("$i) resolve at ($x,$y, $cellsize) with ".htmlentities($content)."<br/>\n");
                    list($r, $v, $b) = tcpdf_decode_html_color($sumbgcolor);
                    $pdf->SetFillColor($r, $v, $b);
                    list($r, $v, $b) = tcpdf_decode_html_color($sumcolor, true);
                    $pdf->SetTextColor($r, $v, $b);
                    $pdf->writeHTMLCell($size, $lineincr, $x, $y, $content, $border, 0, 1, true, $align, true);
                    $x += $size;
                }
            }
        } else {
            list($r, $v, $b) = tcpdf_decode_html_color(@$table->pdfbgcolor3[$i]);
            $pdf->SetFillColor($r, $v, $b);
            list($r, $v, $b) = tcpdf_decode_html_color(@$table->pdfcolor3[$i], true);
            $pdf->SetTextColor($r, $v, $b);
            $pdf->writeHTMLCell($cellsize, $lineincr, $x, $y, $datum, $border, 0, 1, true, $table->pdfalign3[$i], true);
            // debug_trace("$i) scalar out ($x, $y, $cellsize) with ".htmlentities($datum)."<br/>\n");
            $x += $cellsize;
        }
        $i++;
    }

    $pdf->SetFillColor(255);
    $pdf->SetTextColor(0);

    return $pdf->getY() + $lineincr;
}

/**
 * Creates rectangles for line border for A4 size paper.
 *
 * @param stdClass $pdf
 */
function report_trainingsessions_draw_frame(&$pdf) {

    // Create outer line border in selected color.
    $pdf->SetLineWidth(0.5);
    $pdf->SetDrawColor(200);
    list($x,$y,$w,$h) = report_trainingsessions_get_object_coords($pdf->getDocOrientation(), $pdf->getDocFormat(), 'frame');
    $pdf->Rect($x,$y,$w,$h);
}

/**
 * Creates rectangles for line border for A4 size paper.
 *
 * @param stdClass $pdf
 */
function report_trainingsessions_draw_box(&$pdf, $x, $y, $dx, $dy) {

    // Create outer line border in selected color.
    $pdf->SetLineWidth(0.5);
    $pdf->SetDrawColor(200);
    $pdf->Rect($x, $y, $dx, $dy);
}

/**
 * Prints logo image from the borders folder in PNG or JPG formats.
 *
 * @param stdClass $pdf;
 */
function report_trainingsessions_print_header(&$pdf, $alternateheader = false) {
    global $CFG;

    $fs = get_file_storage();
    $systemcontext = context_system::instance();

    if ($alternateheader) {
        // $files = $fs->get_area_files($systemcontext->id, 'report_trainingsessions', 'pdfreportinnerheader', 0);
        $files = $fs->get_area_files($systemcontext->id, 'core', 'pdfreportinnerheader', 0);

        if (!empty($files)) {
            $headerfile = array_pop($files);
        } else {
            // Take cover header as default if exists.
            // $files = $fs->get_area_files($systemcontext->id, 'report_trainingsessions', 'pdfreportheader', 0);
            $files = $fs->get_area_files($systemcontext->id, 'core', 'pdfreportheader', 0);

            if (!empty($files)) {
                $headerfile = array_pop($files);
            } else {
                return;
            }
        }
    } else {
        $files = $fs->get_area_files($systemcontext->id, 'core', 'pdfreportheader', 0);
        // $files = $fs->get_area_files($systemcontext->id, 'report_trainingsessions', 'pdfreportheader', 0);

        if (!empty($files)) {
            $headerfile = array_pop($files);
        } else {
            return;
        }
    }

    $contenthash = $headerfile->get_contenthash();
    $pathhash = tcpdf_get_path_from_hash($contenthash);
    $realpath = $CFG->dataroot.'/filedir/'.$pathhash.'/'.$contenthash;

    $size = getimagesize($realpath);

    // Converts 72 dpi images into mm.
    $pdf->Image($realpath, 20, 20, $size[0] / 2.84 / PDF_WIDTH_FACTOR, $size[1] / 2.84 / PDF_WIDTH_FACTOR);
}

/**
 * Prints logo image from the borders folder in PNG or JPG formats.
 *
 * @param stdClass $pdf;
 */
function report_trainingsessions_print_footer(&$pdf) {
    global $CFG;

    $fs = get_file_storage();
    $systemcontext = context_system::instance();

    $files = $fs->get_area_files($systemcontext->id, 'core', 'pdfreportfooter', 0);
    // $files = $fs->get_area_files($systemcontext->id, 'report_trainingsessions', 'pdfreportfooter', 0);

    if (!empty($files)) {
        $footerfile = array_pop($files);
    } else {
        return;
    }

    $contenthash = $footerfile->get_contenthash();
    $pathhash = tcpdf_get_path_from_hash($contenthash);
    $realpath = $CFG->dataroot.'/filedir/'.$pathhash.'/'.$contenthash;

    $size = getimagesize($realpath);

    // Converts 72 dpi images into mm.
    $pdf->Image($realpath, 20, 260, $size[0] / 2.84 / PDF_WIDTH_FACTOR, $size[1] / 2.84 / PDF_WIDTH_FACTOR);
}

function report_trainingsessions_print_usersessions(&$pdf, $userid, $y, $from, $to, $course) {
    global $DB, $CFG;

    $config = get_config('report_trainingsessions');
    if (!empty($config->enablelearningtimecheckcoupling)) {
        require_once($CFG->dirroot.'/report/learningtimecheck/lib.php');
        $ltcconfig = get_config('report_learningtimecheck');
    }

    $x = 10;
    $lineincr = 8;
    $dblelineincr = 16;

    // Get user.
    $user = $DB->get_record('user', array('id' => $userid));

    // Get data
    $logs = use_stats_extract_logs($from, $to, $userid, $course);
    $aggregate = use_stats_aggregate_logs($logs, 'module', 0, $from, $to);

    // Make report.
    $title = get_config('sessionreportdoctitle', 'report_trainingsessions');
    if ($title) {
        $pdf->SetTextColor(0, 0, 120);
        $y = report_trainingsessions_print_text($pdf, $title, $x + 40, $y, '', '', 'C', 'freesans', '', 20);
    }

    $pdf->SetTextColor(0);

    // $y += $dblelineincr;
    $label = get_string('recipient', 'report_trainingsessions').':';
    report_trainingsessions_print_text($pdf, $label, $x, $y, '', '', 'L', 'freesans', '', 13);
    $recipient = get_config('report_trainingsessions', 'recipient');
    $y = report_trainingsessions_print_text($pdf, $recipient, $x + 50, $y, '', '', 'L', 'freesans', '', 13);

    $label = get_string('reportdate', 'report_trainingsessions').':';
    report_trainingsessions_print_text($pdf, $label, $x, $y, '', '', 'L', 'freesans', '', 13);
    $y = report_trainingsessions_print_text($pdf, userdate(time()), $x + 50, $y, '', '', 'L', 'freesans', '', 13);

    $label = get_string('from', 'report_trainingsessions').':';
    report_trainingsessions_print_text($pdf, $label, $x, $y, '', '', 'L', 'freesans', '', 13);
    $y = report_trainingsessions_print_text($pdf, date('d/m/Y H:i', $from), $x + 50, $y, '', '', 'L', 'freesans', '', 13);

    $label = get_string('to', 'report_trainingsessions').':';
    report_trainingsessions_print_text($pdf, $label, $x, $y, '', '', 'L', 'freesans', '', 13);
    $y = report_trainingsessions_print_text($pdf, date('d/m/Y H:i', $to), $x + 50, $y, '', '', 'L', 'freesans', '', 13);

    $y += $lineincr;

    $label = get_string('reportforuser', 'report_trainingsessions').':';
    report_trainingsessions_print_text($pdf, $label, $x, $y, '', '', 'L', 'freesans', '', 13);
    $y = report_trainingsessions_print_text($pdf, fullname($user), $x + 50, $y, '', '', 'L', 'freesans', '', 13);

    if (!empty($config->printidnumber)) {
        if (!empty($user->idnumber)) {
            // $y += $dblelineincr;
            $label = get_string('idnumber').':';
            report_trainingsessions_print_text($pdf, $label, $x, $y, '', '', 'L', 'freesans', '', 13);
            $y = report_trainingsessions_print_text($pdf, $user->idnumber, $x + 50, $y, '', '', 'L', 'freesans', '', 13);
        }
    }

    if (!empty($course)) {
        $label = get_string('shortname').':';
        report_trainingsessions_print_text($pdf, $label, $x, $y, '', '', 'L', 'freesans', '', 13);
        $y = report_trainingsessions_print_text($pdf, $course->shortname, $x + 50, $y, '', '', 'L', 'freesans', '', 13);
        $label = get_string('course').':';
        report_trainingsessions_print_text($pdf, $label, $x, $y, '', '', 'L', 'freesans', '', 13);
        $y = report_trainingsessions_print_text($pdf, format_string($course->fullname), $x + 50, $y, '', '', 'L', 'freesans', '', 13);
    }

    $table = new html_table();
    $sessionstartstr = get_string('sessionstart', 'report_trainingsessions');
    $sessionendstr = get_string('sessionend', 'report_trainingsessions');
    $sessiondurationstr = get_string('sessionduration', 'report_trainingsessions');
    $table->pdfhead2 = array($sessionstartstr, $sessionendstr, $sessiondurationstr);
    $table->pdfsize2 = array('30%', '30%', '30%');
    $table->pdfalign2 = array('L', 'L', 'L');
    $table->pdfalign3 = array('L', 'L', 'L');
    $table->pdfprintinfo = array(1,1,1);
    $y = report_trainingsessions_print_headline($pdf, $y, $table);

    if (empty($aggregate['sessions'])) {
        $dataline = array(get_string('nosessions', 'report_trainingsessions'), '', '');
        $y = report_trainingsessions_print_dataline($pdf, $y, $dataline, $table);
        return $y;
    }

    $duration = 0;
    $outduration = 0;
    $induration = 0;
    foreach ($aggregate['sessions'] as $sessionid => $session) {
        // theses are real tracked sessions

        if ($course->id && !array_key_exists($course->id, $session->courses)) {
            // Omit all sessions not visiting this course.
            continue;
        }

        // Fix eventual missing session end.
        if (!isset($session->sessionend) && empty($session->elapsed)) {
            // This is a "not true" session reliquate. Ignore it.
            continue;
        }

        // Fix all incoming sessions. possibly cropped by threshold effect.
        $session->sessionend = $session->sessionstart + $session->elapsed;

        $daysessions = report_trainingsessions_splice_session($session);

        foreach($daysessions as $daysession) {

            // If coupling to learning timecheck valid times, check and mark times with colors.
            // or remove and truncate sessions
            if (!empty($config->enablelearningtimecheckcoupling)) {

                if (!empty($ltcconfig->checkworkingdays) || !empty($ltcconfig->checkworkinghours)) {

                    // Start check :
                    $fakecheck = new StdClass();
                    $fakecheck->usertimestamp = $daysession->sessionstart;
                    $fakecheck->userid = $userid;

                    if ($config->learningtimesessioncrop == 'mark') {
    
                        $outtime = false;
                        if (!empty($ltcconfig->checkworkingdays) && report_learningtimecheck_is_valid($fakecheck)) {
                            $table->color1[0] = '#A0A0A0';
                            $table->color1[1] = '#A0A0A0';
                            $table->color1[2] = '#A0A0A0';
                            $outtime = true;
                            if ($outtime) $outduration += $daysession->elapsed;
                            if (!$outtime) $induration += $daysession->elapsed;
                        } else {
                            if (!empty($ltcconfig->checkworkinghours)) {
                                if (!$startcheck = report_learningtimecheck_check_time($fakecheck, $ltcconfig)) {
                                    $table->color1[0] = '#A00000';
                                }
        
                                // End check :
                                $fakecheck = new StdClass();
                                $fakecheck->usertimestamp = $daysession->sessionend;
                                $fakecheck->userid = $userid;
                                if (!$endcheck = report_learningtimecheck_check_time($fakecheck, $ltcconfig)) {
                                    $table->color1[1] = '#A00000';
                                }
        
                                if (!$startcheck && !$endcheck) {
                                    $table->color[2] = '#A00000';
                                    $outtime = true;
                                }
                                if ($outtime) $outduration += $daysession->elapsed;
                                if (!$outtime) $induration += $daysession->elapsed;
                            }
                        }
                    } else {
                        if (!empty($ltcconfig->checkworkingdays)) {
                            if (!report_learningtimecheck_is_valid($fakecheck)) {
                                continue;
                            }
                        }

                        if (!empty($ltcconfig->checkworkinghours)) {
                            if (!report_learningtimecheck_check_day($fakecheck, $ltcconfig)) {
                                continue;
                            }
        
                            report_learningtimecheck_crop_session($daysession, $ltcconfig);
                            if ($daysession->sessionstart && $daysession->sessionend) {
                                // Segment was not invalidated, possibly shorter than original.
                                $daysession->elapsed = $daysession->sessionend - $daysession->sessionstart;
                            } else {
                                // Croping results concluded into an invalid segment.
                                continue;
                            }
                        }
                    }
                }
            }

            $duration += $daysession->elapsed;
            $sessiondata = array(date('d/m/Y H:i', $daysession->sessionstart), date('d/m/Y H:i', $daysession->sessionend), report_trainingsessions_format_time($daysession->elapsed, 'xlsd'));
            $y = report_trainingsessions_print_dataline($pdf, $y, $sessiondata, $table);
            $y += $lineincr;

            $isnewpage = false;
            $y = report_trainingsessions_check_page_break($pdf, $y, $isnewpage, true, false);
        }
    }

    $summators = array('', '', report_trainingsessions_format_time($duration, 'xlsd'));
    $y = report_trainingsessions_print_sumline($pdf, $y, $summators, $table);
    $y = report_trainingsessions_check_page_break($pdf, $y, $isnewpage, true, false);

    if (!empty($config->enablelearningtimecheckcoupling) && 
            ($config->learningtimesessioncrop == 'mark') && 
                    (!empty($ltcconfig->checkworkingdays) || 
                            !empty($ltcconfig->checkworkinghours))) {
        $summators = array('', get_string('in', 'report_trainingsessions'), report_trainingsessions_format_time($induration, 'xlsd'));
        $y = report_trainingsessions_print_sumline($pdf, $y, $summators, $table);
        $y = report_trainingsessions_check_page_break($pdf, $y, $isnewpage, true, false);

        $summators = array('', get_string('out', 'report_trainingsessions'), report_trainingsessions_format_time($outduration, 'xlsd'));
        $y = report_trainingsessions_print_sumline($pdf, $y, $summators, $table);
        $y = report_trainingsessions_check_page_break($pdf, $y, $isnewpage, true, false);
    }

    return $y;
}

function report_trainingsessions_print_userinfo(&$pdf, $y, &$user, &$course, $from = 0, $to = 0, $data = null, $recipient = null) {
    global $DB;

    $config = get_config('report_trainingsessions');

    $x = 20;
    $lineincr = 8;
    $dblelineincr = 16;
    $dataxoffset = 80;

    // Make report.
    $pdf->SetTextColor(0, 0, 120);
    $title = get_config('sessionreportdoctitle', 'report_trainingsessions');
    $y = report_trainingsessions_print_text($pdf, $title, $x + 40, $y, '', '', 'C', 'freesans', '', 20);

    $pdf->SetTextColor(0);

    // $y += $dblelineincr;
    if ($recipient) {
        $label = get_string('recipient', 'report_trainingsessions').':';
        report_trainingsessions_print_text($pdf, $label, $x, $y, '', '', 'L', 'freesans', 'B', 13);
        $recipient = get_config('report_trainingsessions', 'recipient');
        $y = report_trainingsessions_print_text($pdf, $recipient, $x + $dataxoffset, $y, '', '', 'L', 'freesans', '', 13);
    }

    $y += $lineincr;
    $label = get_string('reportdate', 'report_trainingsessions').':';
    report_trainingsessions_print_text($pdf, $label, $x, $y, '', '', 'L', 'freesans', 'B', 13);
    $y = report_trainingsessions_print_text($pdf, userdate(time()), $x + $dataxoffset, $y, '', '', 'L', 'freesans', '', 13);

    if ($from) {
        $label = get_string('from', 'report_trainingsessions').':';
        report_trainingsessions_print_text($pdf, $label, $x, $y, '', '', 'L', 'freesans', 'B', 13);
        $y = report_trainingsessions_print_text($pdf, userdate($from), $x + $dataxoffset, $y, '', '', 'L', 'freesans', '', 13);
    }

    if ($to) {
        $label = get_string('to', 'report_trainingsessions').':';
        report_trainingsessions_print_text($pdf, $label, $x, $y, '', '', 'L', 'freesans', 'B', 13);
        $y = report_trainingsessions_print_text($pdf, userdate($to), $x + $dataxoffset, $y, '', '', 'L', 'freesans', '', 13);
    }

    $y += $lineincr;
    $label = get_string('reportforuser', 'report_trainingsessions').':';
    report_trainingsessions_print_text($pdf, $label, $x, $y, '', '', 'L', 'freesans', 'B', 13);
    $y = report_trainingsessions_print_text($pdf, fullname($user), $x + $dataxoffset, $y, '', '', 'L', 'freesans', '', 13);

    if (!empty($config->printidnumber)) {
        // $y += $dblelineincr;
        $label = get_string('idnumber').':';
        report_trainingsessions_print_text($pdf, $label, $x, $y, '', '', 'L', 'freesans', 'B', 13);
        $y = report_trainingsessions_print_text($pdf, $user->idnumber, $x + $dataxoffset, $y, '', '', 'L', 'freesans', '', 13);
    }

    // Add some custom info from profile
    if (!empty($config->extrauserinfo1)) {
        $fieldname = $DB->get_field('user_info_field', 'name', array('id' => $config->extrauserinfo1)).':';
        $info = $DB->get_field('user_info_data', 'data', array('userid' => $user->id, 'fieldid' => $config->extrauserinfo1));
        report_trainingsessions_print_text($pdf, $fieldname, $x, $y, '', '', 'L', 'freesans', 'B', 13);
        $y = report_trainingsessions_print_text($pdf, $info, $x + $dataxoffset, $y, '', '', 'L', 'freesans', '', 13);
    } 

    if (!empty($config->extrauserinfo2)) {
        $fieldname = $DB->get_field('user_info_field', 'name', array('id' => $config->extrauserinfo2)).':';
        $info = $DB->get_field('user_info_data', 'data', array('userid' => $user->id, 'fieldid' => $config->extrauserinfo2));
        report_trainingsessions_print_text($pdf, $fieldname, $x, $y, '', '', 'L', 'freesans', 'B', 13);
        $y = report_trainingsessions_print_text($pdf, $info, $x + $dataxoffset, $y, '', '', 'L', 'freesans', '', 13);
    } 

    if ($data) {
        $fieldname = get_string('equlearningtime', 'report_trainingsessions');
        $info = report_trainingsessions_format_time(0 + @$data->elapsed, 'html');
        report_trainingsessions_print_text($pdf, $fieldname, $x, $y, '', '', 'L', 'freesans', 'B', 13);
        $y = report_trainingsessions_print_text($pdf, $info, $x + $dataxoffset, $y, '', '', 'L', 'freesans', '', 13);
    
        $fieldname = get_string('activitytime', 'report_trainingsessions');
        $info = report_trainingsessions_format_time(0 + @$data->activityelapsed, 'html');
        report_trainingsessions_print_text($pdf, $fieldname, $x, $y, '', '', 'L', 'freesans', 'B', 13);
        $y = report_trainingsessions_print_text($pdf, $info, $x + $dataxoffset, $y, '', '', 'L', 'freesans', '', 13);

        $fieldname = get_string('othertime', 'report_trainingsessions');
        $info = report_trainingsessions_format_time((0 + @$data->otherelapsed + @$data->courseelapsed), 'html');
        report_trainingsessions_print_text($pdf, $fieldname, $x, $y, '', '', 'L', 'freesans', 'B', 13);
        $y = report_trainingsessions_print_text($pdf, $info, $x + $dataxoffset, $y, '', '', 'L', 'freesans', '', 13);
    }

    $usergroups = groups_get_all_groups($course->id, $user->id, 0, 'g.id, g.name');

    if (!empty($usergroups)) {
        foreach ($usergroups as $group) {
            $str = $group->name;
            if ($group->id == groups_get_course_group($course)) {
                $str = "[$str]";
            }
            $groupnames[] = $str;
        }
        $str = implode(', ', $groupnames);
        // print group status
        $label = get_string('groups').':';
        report_trainingsessions_print_text($pdf, $label, $x, $y, '', '', 'L', 'freesans', 'B', 13);
        $y = report_trainingsessions_print_text($pdf, $str, $x + $dataxoffset, $y, '', '', 'L', 'freesans', '', 13);
    }

    $context = context_course::instance($course->id);
    $label = get_string('roles').':';
    report_trainingsessions_print_text($pdf, $label, $x, $y, '', '', 'L', 'freesans', 'B', 13);
    $roles = get_user_roles($context, $user->id);
    $rolenames = array();
    $rolenames = role_fix_names(get_all_roles(), context_system::instance(), ROLENAME_ORIGINAL);
    foreach ($roles as $r) {
        $rolenamesarr[] = $rolenames[$r->roleid]->localname;
    }
    $y = report_trainingsessions_print_text($pdf, strip_tags(implode(",", $rolenamesarr)), $x + $dataxoffset, $y, '', '', 'L', 'freesans', '', 13);

    return $pdf->getY();
} 

/**
 * a raster for xls printing of a course structure in report. Scans recusrively the course structure
 * object, and prints a result line on leaf or title items, collects that "done", "elapsed" and "events"
 * globalizers in the way.
 * @param objectref &$pdf the pdf document
 * @param int $y the current vertical position in page
 * @param objectref &$structure the course structure subtree
 * @param objectref &$aggregate the log aggregation
 * @param intref &$done the "done items" counter
 * @param int $level the current recursion level in structure
 */
function report_trainingsessions_print_course_structure(&$pdf, &$y, &$structure, &$aggregate, &$table, $level = 1) {
    static $indent = array();

    $config = get_config('report_trainingsessions');

    $x = 20;
    $lineincr = 8;
    $dblelineincr = 16;
    $y += $lineincr; // Leave some room under user info.

    if (empty($structure)) {
        $label = get_string('nostructure', 'report_trainingsessions');
        $y = report_trainingsessions_print_text($pdf, $label, $x, $y, '', '', 'L', 'freesans', '', 13);
        return;
    }

    if (is_array($structure)) {
        // Recurse in sub structures.
        foreach ($structure as $element) {
            if (isset($element->instance) && empty($element->instance->visible)) {
                // Non visible items should not be displayed.
                debug_trace('Discard non visible');
                continue;
            }
            if (!empty($config->hideemptymodules) && empty($element->elapsed) && empty($element->events)) {
                // Discard empty items.
                debug_trace('Discard empty');
                continue;
            }
            report_trainingsessions_print_course_structure($pdf, $y, $element, $aggregate, $table, $level);
        } 
    } else {
        // Prints a single row.
        debug_trace('Struct element');

        if (!isset($structure->instance) || !empty($structure->instance->visible)) {
            // Non visible items should not be displayed.
            if (!empty($structure->name)) {
                // Write element title.
                // TODO : Check how to force spanning on title.
                $dataline = array();
                $indentstr = implode('', $indent);
                $table->level = $level;
                if (($structure->plugintype == 'page') || ($structure->plugintype == 'section')) {
                    $dataline[0] = $indentstr.$structure->name;
                    $table->pdfhead2 = $dataline;
                    $memsize = $table->pdfsize2;
                    $memcolor = $table->pdfbgcolor2;
                    $table->pdfsize2 = array('100%');
                    $table->pdfbgcolor2 = array('#d0d0d0');
                    $table->pdfcolor2 = array('#000000');
                    $fontsize = $pdf->getFontSizePt();
                    $pdf->SetFontSize($fontsize + 3);
                    $y = report_trainingsessions_print_headline($pdf, $y, $table);
                    $pdf->SetFontSize($fontsize);
                    $table->pdfsize2 = $memsize;
                    $table->pdfbgcolor2 = $memcolor;

                    if (!empty($config->showhits)) {
                        $dataline[0] = get_string('structuretotal', 'report_trainingsessions', $structure->name);
                        $dataline[1] = report_trainingsessions_format_time($structure->elapsed, 'html');
                        $dataline[2] = $dataobject->events;
                        $table->pdfsize2 = array('70%', '20%', '10%');
                        $y = report_trainingsessions_print_sumline($pdf, $y, $dataline, $table);
                    } else {
                        $dataline[0] = get_string('structuretotal', 'report_trainingsessions', $structure->name);
                        $dataline[1] = report_trainingsessions_format_time($structure->elapsed, 'html');
                        $table->pdfsize2 = array('75%', '25%');
                        $y = report_trainingsessions_print_sumline($pdf, $y, $dataline, $table);
                    }
                } else {
                    $dataline = array();
                    $dataline[0] = shorten_text(get_string('pluginname', $structure->type), 40);
                    $table->pdfhead2 = $dataline;
                    $table->pdfcolor2 = array('#ffffff');
                    if (!empty($config->showhits)) {
                        $dataline[1] = report_trainingsessions_format_time(@$aggregate[$structure->type][$structure->id]->firstaccess, 'xls');
                        $dataline[2] = report_trainingsessions_format_time($structure->elapsed, 'html');
                        $dataline[3] = $structure->events;
                        $table->pdfsize2 = array('50%', '20%', '20%', '10%');
                        $table->pdfalign2 = array('L', 'L', 'R', 'R');
                        $y = report_trainingsessions_print_dataline($pdf, $y, $dataline, $table);
                    } else {
                        $dataline[1] = report_trainingsessions_format_time(@$aggregate[$structure->type][$structure->id]->firstaccess, 'xls');
                        $dataline[2] = report_trainingsessions_format_time($structure->elapsed, 'html');
                        $table->pdfsize2 = array('50%', '25%', '25%');
                        $y = report_trainingsessions_print_dataline($pdf, $y, $dataline, $table);
                    }

                    $dataline = array();
                    $dataline[0] = $indentstr.shorten_text($structure->name, 85);
                    $pdf->setFontSize(8);
                    $table->pdfsize2 = array('100%');
                    $bgcolormem = $table->pdfbgcolor;
                    $colormem = $table->pdfcolor;
                    $table->pdfbgcolor = array('#ffffff');
                    $table->pdfcolor = array('#000000');
                    $y = report_trainingsessions_print_dataline($pdf, $y, $dataline, $table);
                    $table->pdfbgcolor = $bgcolormem;
                    $table->pdfcolor = $colormem;
                    $pdf->setFontSize(9);
                }

                $false = false;
                $y = report_trainingsessions_check_page_break($pdf, $y, $false, false, false);

                if (!empty($structure->subs)) {
                    array_push($indent, ' ');
                    report_trainingsessions_print_course_structure($pdf, $y, $structure->subs, $aggregate, $table, $level + 1);
                    array_pop($indent);
                }

            } else {
                // It is only a structural module that should not impact on level
                if (isset($structure->id) && !empty($aggregate[$structure->type][$structure->id])) {
                    $dataobject = $aggregate[$structure->type][$structure->id];
                }
                if (!empty($structure->subs)) {
                    report_trainingsessions_print_course_structure($pdf, $y, $structure->subs, $aggregate, $table, $level);
                }
            }
        }
    }
}

/**
 * Precalculates subtree aggregates without printing anything
 * @param objectref &$pdf the pdf document
 * @param int $y the current vertical position in page
 * @param objectref &$structure the course structure subtree
 * @param objectref &$aggregate the log aggregation
 * @param intref &$done the "done items" counter
 * @param int $level the current recursion level in structure
 */
function report_trainingsessions_calculate_course_structure(&$structure, &$aggregate, &$done, &$items) {

    if (empty($structure)) {
        return;
    }

    // makes a blank dataobject.
    $dataobject = new StdClass;
    $dataobject->elapsed = 0;
    $dataobject->events = 0;

    if (is_array($structure)) {
        // recurse in sub structures
        foreach ($structure as &$element) {
            if (isset($element->instance) && empty($element->instance->visible)) {
                // non visible items should not be displayed.
                continue;
            }
            $res = report_trainingsessions_calculate_course_structure($element, $aggregate, $done, $items);
            $dataobject->elapsed += $res->elapsed;
            $dataobject->events += $res->events;
        } 
    } else {
        debug_trace('Item>');
        if (!empty($structure->visible) || !isset($structure->instance) || !empty($structure->instance->visible)) {
            // Non visible items should not be displayed.
            if (!empty($structure->name)) {
                $items++;
                if (isset($structure->id) && !empty($aggregate[$structure->type][$structure->id])) {
                    debug_trace('Done.');
                    $done++;
                    $dataobject->elapsed = $aggregate[$structure->type][$structure->id]->elapsed;
                    $dataobject->events = $aggregate[$structure->type][$structure->id]->events;
                } else {
                    debug_trace('No track.');
                    $dataobject->elapsed = 0;
                    $dataobject->events = 0;
                }

                if (!empty($structure->subs)) {
                    $res = report_trainingsessions_calculate_course_structure($structure->subs, $aggregate, $done, $items);
                    $dataobject->elapsed += $res->elapsed;
                    $dataobject->events += $res->events;
                }
            } else {
                // It is only a structural module that should not impact on level
                if (isset($structure->id) && !empty($aggregate[$structure->type][$structure->id])) {
                    debug_trace('Not structural.');
                    $dataobject->elapsed = $aggregate[$structure->type][$structure->id]->elapsed;
                    $dataobject->events = $aggregate[$structure->type][$structure->id]->events;
                }
                if (!empty($structure->subs)) {
                    $res = report_trainingsessions_calculate_course_structure($structure->subs, $aggregate, $done, $items);
                    $dataobject->elapsed += $res->elapsed;
                    $dataobject->events += $res->events;
                }
            }

            // Report in element.
            $structure->elapsed = $dataobject->elapsed;
            $structure->events = $dataobject->events;
        }
    }

    // Returns acumulated aggregates.
    return $dataobject;
}

function report_trainingsessions_print_signboxes(&$pdf, $y, $options = null) {

    $x = 30;

    $boxnum = 1;
    $false = false;

    if (!empty($options['student'])) {
        $label = get_string('studentsign', 'report_trainingsessions');
        report_trainingsessions_draw_box($pdf, $x - 2, $y - 2, 74, 54);
        report_trainingsessions_print_text($pdf, $label, $x, $y, '', '', 'L', 'freesans', '', 9);
        if ($boxnum % 2 != 0) {
            $x = 120;
        } else {
            $y += 80;
            report_trainingsessions_check_page_break($pdf, $y, $false, false);
            $x = 10;
        }
        $boxnum++;
    }

    if (!empty($options['teacher'])) {
        $label = get_string('teachersign', 'report_trainingsessions');
        report_trainingsessions_draw_box($pdf, $x - 2, $y - 2, 74, 54);
        report_trainingsessions_print_text($pdf, $label, $x, $y, '', '', 'L', 'freesans', '', 9);
        if ($boxnum % 2 != 0) {
            $x = 120;
        } else {
            $y += 80;
            report_trainingsessions_check_page_break($pdf, $y, $false, false);
            $x = 10;
        }
        $boxnum++;
    }

    if (!empty($options['enterprise'])) {
        $label = get_string('enterprisesign', 'report_trainingsessions');
        report_trainingsessions_draw_box($pdf, $x - 2, $y - 2, 74, 54);
        report_trainingsessions_print_text($pdf, $label, $x, $y, '', '', 'L', 'freesans', '', 9);
        if ($boxnum % 2 != 0) {
            $x = 120;
        } else {
            $y += 80;
            report_trainingsessions_check_page_break($pdf, $y, $false, false);
            $x = 10;
        }
        $boxnum++;
    }

    if (!empty($options['authority'])) {
        $label = get_string('authoritysign', 'report_trainingsessions');
        report_trainingsessions_draw_box($pdf, $x - 2, $y - 2, 74, 54);
        report_trainingsessions_print_text($pdf, $label, $x, $y, '', '', 'L', 'freesans', '', 9);
        if ($boxnum % 2 != 0) {
            $x = 120;
        } else {
            $y += 80;
            report_trainingsessions_check_page_break($pdf, $y, $false, false);
            $x = 10;
        }
        $boxnum++;
    }

    return $y;
}

/**
 * a raster for printing a header line in a PDF for course report.
 * this will build the second level title header line in the pdf metadata array.
 * @param stringref &$str the output buffer
 */
function report_trainingsessions_print_courses_line_header(&$pdf, $y, &$table) {

    $table->pdfhead2 = array();
    $table->pdfhead2[] = 'ix';
    $table->pdfhead2[] = 'uid';
    $table->pdfhead2[] = get_string('idnumber');
    $table->pdfhead2[] = get_string('username');
    $table->pdfhead2[] = get_string('lastname');
    $table->pdfhead2[] = get_string('firstname');

    $table->pdfhead2[] = 'cid';
    $table->pdfhead2[] = get_string('shortname');
    $table->pdfhead2[] = get_string('fullname');

    $table->pdfhead2[] = get_string('elapsed', 'report_trainingsessions');
    $table->pdfhead2[] = get_string('hits', 'report_trainingsessions');

    report_trainingsessions_add_graded_columns($table->pdfhead2);

    $y = report_trainingsessions_print_headline($pdf, $y, $table);
    return $y;
}

/**
 * a raster for printing of a course report csv line for a single user.
 *
 * @param stringref &$str the output buffer
 * @param arrayref $aggregate aggregated logs to explore.
 * @param objectref &$user 
 */
function report_trainingsessions_print_courses_line(&$pdf, &$aggregate, &$user, &$table) {
    global $CFG, $COURSE, $DB;
    static $lineix = 1;
    static $COURSES = array();
    static $COURSEIDS = array();

    $output = array();
    if (!empty($aggregate['coursetotal'])) {
        foreach ($aggregate['coursetotal'] as $cid => $cdata) {
            if ($cid == 0) {
                $cid = SITEID;
            }

            // Some caching.
            if (!in_array($cid, $COURSEIDS)) {
                if (!$course = $DB->get_record('course', array('id' => $cid), 'id,idnumber,shortname,fullname,category')) {
                    continue;
                }
                $COURSES[$cid] = $course;
                $COURSEIDS[] = $course->id;
            }

            $dataline = array();
            $dataline[] = $lineix;
            $dataline[] = $user->id;
            $dataline[] = $user->idnumber;
            $dataline[] = $user->username;
            $dataline[] = $user->lastname;
            $dataline[] = $user->firstname;

            $dataline[] = $COURSES[$cid]->id;
            $dataline[] = $COURSES[$cid]->shortname;
            $dataline[] = $COURSES[$cid]->fullname;

            $events = $cdata->elapsed;
            $hours = floor($cdata->elapsed / HOURSECS);
            $remmins = $cdata->elapsed % HOURSECS;
            $mins = floor($remmins / 60);
            $secs = $remmins % 60;
            $dataline[] = sprintf('%02d', $hours).':'.sprintf('%02d', $mins).':'.sprintf('%02d', $secs);
            $dataline[] = $cdata->events;
            report_trainingsessions_add_graded_data($dataline, $user->id);
            report_trainingsessions_print_dataline($pdf, $y, $dataline, $table);
        }
    }
}

/**
 * Provides page absolute coords for some objects depending on format
 */
function report_trainingsessions_get_object_coords($orientation, $format = 'A4', $objectname = '') {
    switch($orientation.'_'.$format.'_'.$objectname) {
        case "P_A4_pagenum":
            return array(30, 0, 95, 280);
        case "L_A4_pagenum":
            return array(30, 0, 130, 200);
        case "P_A4_frame":
            return array(10, 10, 190, 277);
        case "L_A4_frame":
            return array(10, 10, 277, 190);
    }
    return array(0,0,0,0);
}

function report_trainingsessions_print_courseline_head(&$pdf, $y, &$table) {

    $config = get_config('report_trainingsessions');

    $table->pdfhead2 = array();
    $table->pdfhead2[] = get_string('idnumber');
    $table->pdfhead2[] = get_string('shortname');
    $table->pdfhead2[] = get_string('elapsed', 'report_trainingsessions');
    if (!empty($config->showhits)) {
        $table->pdfhead2[] = get_string('hits', 'report_trainingsessions');
    }

    $y = report_trainingsessions_print_headline($pdf, $y, $table);
    return $y;
}

/**
 * @param object $pdf the TCPDF object
 * @param $dataline, the data flat array to print 
 * @param $table the pdf attributes for layout and display settings
 */
function report_trainingsessions_print_courseline(&$pdf, $y, &$courseline, &$table) {

    $lineincr = 5;

    $dataline = array();
    $dataline[] = $courseline->idnumber;
    $dataline[] = $courseline->shortname;
    $dataline[] = report_trainingsessions_format_time($courseline->elapsed, 'html');
    if (!empty($config->showhits)) {
        $dataline[] = $courseline->events;
    }

    $y = report_trainingsessions_print_dataline($pdf, $y, $dataline, $table);
    $y += $lineincr;
    $y = report_trainingsessions_check_page_break($pdf, $y, $isnewpageunused, true, false);

    return $y;
}