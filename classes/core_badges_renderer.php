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
     * Render a collection of badges.
     *
     * @param \core_badges\output\badge_collection $badges
     * @return string
     
    protected function render_badge_collection(\core_badges\output\badge_collection $badges) {
        $paging = new paging_bar($badges->totalcount, $badges->page, $badges->perpage, $this->page->url, 'page');
        $htmlpagingbar = $this->render($paging);
        $table = new html_table();
        $table->attributes['class'] = 'collection';

        $sortbyname = $this->helper_sortable_heading(get_string('name'),
                'name', $badges->sort, $badges->dir);
        $sortbyawarded = $this->helper_sortable_heading(get_string('awardedtoyou', 'badges'),
                'dateissued', $badges->sort, $badges->dir);
        $table->head = array(
                    get_string('badgeimage', 'badges'),
                    $sortbyname,
                    get_string('description', 'badges'),
                    get_string('bcriteria', 'badges'),
                    $sortbyawarded
                );
        $table->colclasses = array('badgeimage', 'name', 'description', 'criteria', 'awards');

        foreach ($badges->badges as $badge) {
            $badgeimage = print_badge_image($badge, $this->page->context, 'large');
            $name = $badge->name;
            $description = $badge->description;
            $criteria = self::print_badge_criteria($badge);
            if ($badge->dateissued) {
                $icon = new pix_icon('i/valid',
                            get_string('dateearned', 'badges',
                                userdate($badge->dateissued, get_string('strftimedatefullshort', 'core_langconfig'))));
                $badgeurl = new moodle_url('/badges/badge.php', array('hash' => $badge->uniquehash));
                $awarded = $this->output->action_icon($badgeurl, $icon, null, null, true);
            } else {
                $awarded = "";
            }
            $row = array($badgeimage, $name, $description, $criteria, $awarded);
            $table->data[] = $row;
        }

        $htmltable = html_writer::table($table);

        //return $htmlpagingbar . $htmltable . $htmlpagingbar;
        return print_r($badges->badges);
    }
    */

    /**
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
           
            if ($badge->dateissued) {
                $awarded = '<span class="badge badge-success">Obtido</span>';
            } else {
                $imageurl= 'http://10.10.2.24/egg/moodle/theme/image.php/fordson/theme/1578593667/badge_oculto';
                $awarded = '<span class="badge badge-dark">Não obtido</span>';
                $badgeurl= "#";
            }
            
            $novo_html.='<div class="card  dashboard-card"> <div class="card-body"><center>';
            $novo_html.='<a href="'.$badgeurl.'">';
            $novo_html.=html_writer::empty_tag('img', array('src' => $imageurl, 'class' => 'badge-image mb-2'));
            $novo_html.='</a>';     
            $novo_html.='<h5 class="card-title">'. $badge->name. '</h5>';
           
            $novo_html.= $badge->description . '<br/>';
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
