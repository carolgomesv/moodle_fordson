<?php
// This file is part of Moodle - http://moodle.org/
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

/**
 * Renderer for use with the badges output
 *
 * @package    core
 * @subpackage badges
 * @copyright  2012 onwards Totara Learning Solutions Ltd {@link http://www.totaralms.com/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Yuliya Bozhko <yuliya.bozhko@totaralms.com>
 */

require_once($CFG->libdir . '/badgeslib.php');
require_once($CFG->libdir . '/tablelib.php');

/**
 * Standard HTML output renderer for badges
 */
class theme_fordson_core_badges_renderer extends core_badges_renderer {

    /**
     * Render an issued badge.
     *
     * @param \core_badges\output\issued_badge $ibadge
     * @return string
     */
    protected function render_issued_badge(\core_badges\output\issued_badge $ibadge) {
        global $USER, $CFG, $DB, $SITE;
        $issued = $ibadge->issued;
        $userinfo = $ibadge->recipient;
        $badgeclass = $ibadge->badgeclass;
        $badge = new badge($ibadge->badgeid);
        $now = time();
        $expiration = isset($issued['expires']) ? $issued['expires'] : $now + 86400;
        $badgeimage = is_array($badgeclass['image']) ? $badgeclass['image']['id'] : $badgeclass['image'];
        $languages = get_string_manager()->get_list_of_languages();

        $output = '';
        $output .= html_writer::start_tag('div', array('id' => 'badge'));
        $output .= html_writer::start_tag('div', array('id' => 'badge-image', 'class'=>'text-center'));
        $output .= html_writer::empty_tag('img', array('src' => $badgeimage, 'alt' => $badge->name, 'width' => '200'));
        if ($expiration < $now) {
            $output .= $this->output->pix_icon('i/expired',
            get_string('expireddate', 'badges', userdate($issued['expires'])),
                'moodle',
                array('class' => 'expireimage'));
        }

        if ($USER->id == $userinfo->id && !empty($CFG->enablebadges)) {
            $output .= $this->output->single_button(
                        new moodle_url('/badges/badge.php', array('hash' => $issued['uid'], 'bake' => true)),
                        get_string('download'),
                        'POST');
            if (!empty($CFG->badges_allowexternalbackpack) && ($expiration > $now) && badges_user_has_backpack($USER->id)) {

                if (badges_open_badges_backpack_api() == OPEN_BADGES_V1) {
                    $assertion = new moodle_url('/badges/assertion.php', array('b' => $issued['uid']));
                    $action = new component_action('click', 'addtobackpack', array('assertion' => $assertion->out(false)));
                    $attributes = array(
                            'type'  => 'button',
                            'class' => 'btn btn-secondary m-1',
                            'id'    => 'addbutton',
                            'value' => get_string('addtobackpack', 'badges'));
                    $tobackpack = html_writer::tag('input', '', $attributes);
                    $this->output->add_action_handler($action, 'addbutton');
                    $output .= $tobackpack;
                } else {
                    $assertion = new moodle_url('/badges/backpack-add.php', array('hash' => $issued['uid']));
                    $attributes = ['class' => 'btn btn-secondary m-1', 'role' => 'button'];
                    $tobackpack = html_writer::link($assertion, get_string('addtobackpack', 'badges'), $attributes);
                    $output .= $tobackpack;
                }
            }
        }
        $output .= html_writer::end_tag('div');

        $output .= html_writer::start_tag('div', array('id' => 'badge-details'));
        // Recipient information.
        $output .= $this->output->heading(get_string('recipientdetails', 'badges'), 3);
        $dl = array();
        if ($userinfo->deleted) {
            $strdata = new stdClass();
            $strdata->user = fullname($userinfo);
            $strdata->site = format_string($SITE->fullname, true, array('context' => context_system::instance()));

            $dl[get_string('name')] = get_string('error:userdeleted', 'badges', $strdata);
        } else {
            $dl[get_string('name')] = fullname($userinfo);
        }
        $output .= $this->definition_list($dl);

        /*$output .= $this->output->heading(get_string('issuerdetails', 'badges'), 3);
        $dl = array();
        $dl[get_string('issuername', 'badges')] = $badge->issuername;
        if (isset($badge->issuercontact) && !empty($badge->issuercontact)) {
            $dl[get_string('contact', 'badges')] = obfuscate_mailto($badge->issuercontact);
        }
        $output .= $this->definition_list($dl);*/


        $output .= $this->output->heading(get_string('badgedetails', 'badges'), 3);
        $dl = array();
        $dl[get_string('name')] = $badge->name;
        if (!empty($badge->version)) {
            $dl[get_string('version', 'badges')] = $badge->version;
        }
        /*if (!empty($badge->language)) {
            $dl[get_string('language')] = $languages[$badge->language];
        }*/
        $dl[get_string('description', 'badges')] = $badge->description;
        if (!empty($badge->imageauthorname)) {
            $dl[get_string('imageauthorname', 'badges')] = $badge->imageauthorname;
        }
        if (!empty($badge->imageauthoremail)) {
            $dl[get_string('imageauthoremail', 'badges')] =
                    html_writer::tag('a', $badge->imageauthoremail, array('href' => 'mailto:' . $badge->imageauthoremail));
        }
        if (!empty($badge->imageauthorurl)) {
            $dl[get_string('imageauthorurl', 'badges')] =
                    html_writer::link($badge->imageauthorurl, $badge->imageauthorurl, array('target' => '_blank'));
        }
        if (!empty($badge->imagecaption)) {
            $dl[get_string('imagecaption', 'badges')] = $badge->imagecaption;
        }

        if ($badge->type == BADGE_TYPE_COURSE && isset($badge->courseid)) {
            $coursename = $DB->get_field('course', 'fullname', array('id' => $badge->courseid));
            $dl[get_string('course')] = $coursename;
        }
        $dl[get_string('bcriteria', 'badges')] = self::print_badge_criteria($badge);
        $output .= $this->definition_list($dl);

        $output .= $this->output->heading(get_string('issuancedetails', 'badges'), 3);
        $dl = array();
        if (!is_numeric($issued['issuedOn'])) {
            $issued['issuedOn'] = strtotime($issued['issuedOn']);
        }
        $dl[get_string('dateawarded', 'badges')] = userdate($issued['issuedOn']);
        if (isset($issued['expires'])) {
            if (!is_numeric($issued['expires'])) {
                $issued['expires'] = strtotime($issued['expires']);
            }
            if ($issued['expires'] < $now) {
                $dl[get_string('expirydate', 'badges')] = userdate($issued['expires']) . get_string('warnexpired', 'badges');

            } else {
                $dl[get_string('expirydate', 'badges')] = userdate($issued['expires']);
            }
        }

        // Print evidence.
        $agg = $badge->get_aggregation_methods();
        $evidence = $badge->get_criteria_completions($userinfo->id);
        $eids = array_map(function($o) {
            return $o->critid;
        }, $evidence);
        unset($badge->criteria[BADGE_CRITERIA_TYPE_OVERALL]);

        $items = array();
        foreach ($badge->criteria as $type => $c) {
            if (in_array($c->id, $eids)) {
                if (count($c->params) == 1) {
                    $items[] = get_string('criteria_descr_single_' . $type , 'badges') . $c->get_details();
                } else {
                    $items[] = get_string('criteria_descr_' . $type , 'badges',
                            core_text::strtoupper($agg[$badge->get_aggregation_method($type)])) . $c->get_details();
                }
            }
        }

        $dl[get_string('evidence', 'badges')] = get_string('completioninfo', 'badges') . html_writer::alist($items, array(), 'ul');
        $output .= $this->definition_list($dl);
        $endorsement = $badge->get_endorsement();
        if (!empty($endorsement)) {
            $output .= self::print_badge_endorsement($badge);
        }

        $relatedbadges = $badge->get_related_badges(true);
        $items = array();
        foreach ($relatedbadges as $related) {
            $relatedurl = new moodle_url('/badges/overview.php', array('id' => $related->id));
            $items[] = html_writer::link($relatedurl->out(), $related->name, array('target' => '_blank'));
        }
        if (!empty($items)) {
            $output .= $this->heading(get_string('relatedbages', 'badges'), 3);
            $output .= html_writer::alist($items, array(), 'ul');
        }

        $alignments = $badge->get_alignments();
        if (!empty($alignments)) {
            $output .= $this->heading(get_string('alignment', 'badges'), 3);
            $items = array();
            foreach ($alignments as $alignment) {
                $items[] = html_writer::link($alignment->targeturl, $alignment->targetname, array('target' => '_blank'));
            }
            $output .= html_writer::alist($items, array(), 'ul');
        }
        $output .= html_writer::end_tag('div');

        return $output;
    }

    /**
     *Muda formatação da página minhas conquistas
     *
     * Render a collection of user badges.
     *
     * @param \core_badges\output\badge_user_collection $badges
     * @return string
     */
    protected function render_badge_user_collection(\core_badges\output\badge_user_collection $badges) {
        global $CFG, $USER, $SITE, $OUTPUT;

        $all_badges=badges_get_badges(1, 0, '', '', 0, 0, $USER->id) ;

        $novo_html= '<h2>Minhas Conquistas</h2>';

        $novo_html.='<div class="block-myoverview block-cards"><div class="card-deck dashboard-card-deck">';

        foreach ($all_badges as $badge) {
            $badgeurl = new moodle_url('/badges/badge.php', array('hash' => $badge->uniquehash));
            $context = ($badge->type == BADGE_TYPE_SITE) ? context_system::instance() : context_course::instance($badge->courseid);
            $imageurl = moodle_url::make_pluginfile_url($context->id, 'badges', 'badgeimage', $badge->id, '/', 'f1', false);
            $description=$badge->description;

            if ($badge->dateissued) {
                $awarded = '<span class="badge badge-success">Obtido</span>';
            } else {
                
                $imageurl= $this->image_url('badge-oculta', 'theme');
                //$imageurl= 'http://10.10.2.24/egg/moodle/theme/image.php/fordson/theme/1578593667/badge_oculto';
                $awarded = '<span class="badge badge-dark">Não obtido</span>';
                $badgeurl= "#";
                $description = "";
            }
            
            $novo_html.='<div class="card  dashboard-card"> <div class="card-body"><center>';
            $novo_html.='<a href="'.$badgeurl.'">';
            $novo_html.=html_writer::empty_tag('img', array('src' => $imageurl, 'class' => 'badge-image mb-2', 'width' => '100px'));
            $novo_html.='</a>';     
            $novo_html.='<h5 class="card-title">'. $badge->name. '</h5>';


            $novo_html.= $description. '<br/>';
             //$criteria = self::print_badge_criteria($badge);   

            $novo_html.='</center></div>';
            $novo_html.='<div class="card-footer text-muted"><small>'.$awarded.'</small> <button type="button" class="btn btn-primary btn-sm float-right" data-toggle="modal" data-target="#modal'.$badge->id.'">  Critério</button></div>';
            $novo_html.='</div>';

            $novo_html.='<div class="modal" id="modal'.$badge->id.'"  tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
            <div class="modal-content">
            <div class="modal-header">
            <h5 class="modal-title">'.$badge->name.' - Critérios </h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
            <span aria-hidden="true">&times;</span>
            </button>
            </div>
            <div class="modal-body">
            <p>'.self::print_badge_criteria($badge).'</p>
            </div>
            </div>
            </div>
            </div>';

        }

        $novo_html.='</div></div>';

        return $novo_html;
    }
}
