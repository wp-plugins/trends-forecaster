<?php
/*
Plugin Name: Trends Forecaster
Plugin URI: http://trendsforecaster.appspot.com/
Description: This widget shows current Google Trends, Trends Forecasts and Top Trends Forecasters.
Author: SAKURAI Kenichi
Version: 1.0.3
Author URI: http://something.cool.coocan.jp/kenichi/
*/

/*
    Trends Forecaster Widget for WordPress
    Copyright (C) 2009 SAKURAI Kenichi

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

function tf_get_hot_trends_atom_url ($country){
  $result = 'http://www.google.com/trends/hottrends/atom/hourly';//default US

  if ($country == 'jp'){
    $result = 'http://www.google.co.jp/trends/hottrends/atom/hourly';
  }

  return $result;
}

function tf_show_ranking ($arg, $num){
  if (count ($arg) > 0){
    $items = array_slice ($arg, 0, $num);
    echo '<ol>';
    foreach ((array)$items as $tmp){
      echo '<li><a href="' . $tmp['link'] . '" target="_blank">' . $tmp['title'] . '</a></li>';
    }
    echo '</ol>';
  }
}

class TrendsForecasterWidget extends WP_Widget{
  var $trends_forecaster_str;
  var $base_url = 'http://trendsforecaster.appspot.com/';

  function TrendsForecasterWidget(){
    if (function_exists ('load_plugin_textdomain')){
      if (!defined ('WP_PLUGIN_DIR')){
        load_plugin_textdomain ('trends-forecaster', str_replace (ABSPATH, '', dirname (__FILE__)));
      } else{
        load_plugin_textdomain ('trends-forecaster', false, dirname (plugin_basename (__FILE__)));
      }
    }

    $this->trends_forecaster_str = __('Trends Forecaster', 'trends-forecaster');

    $widget_ops = array ('classname' => 'widget_trends_forecaster',
                         'description' =>
                         __('This widget shows current Google Trends, Trends Forecasts and Top Trends Forecasters', 'trends-forecaster'));
    $control_ops = array ();
    $this->WP_Widget ('trends-forecaster', $this->trends_forecaster_str, $widget_ops, $control_ops);
  }

  function widget ($args, $instance){
    $disp_num = 3;

    $stamp = time();
    $today = date ('Ymd', $stamp);
    $tomorrow = date ('Ymd', $stamp + 86400);

    include_once (ABSPATH . WPINC . '/rss.php');

    extract ($args);
    $title = apply_filters ('widget_title', __('Hot Trends Forecast', 'trends-forecaster'));

    echo $before_widget . $before_title . $title . $after_title;

    $country = empty($instance['country']) ? 'us' : $instance['country'];
    $local_url = $this->base_url . $country . '/';

    $forecaster = $instance['forecaster'];

    if ($forecaster != ''){
      $rss = fetch_rss ($local_url . 'feeds/person/' . $forecaster . '/' . $today . '/');

      if ($rss){
        list ($score, $rank, $extra) = split (',', $rss->channel['description'], 3);
        list ($tmp, $score, $extra) = split (':', $score, 2);
        list ($tmp, $rank, $extra) = split (':', $rank, 2);
        echo __('My Score (Ranking)', 'trends-forecaster') . ': ' . $score . ' (' . $rank . ')';
      }
    }

    echo '<ul>';

    echo '<li>' . __('Today', 'trends-forecaster') . '</li><ul>';
    echo '<li>' . __('Current Google Hot Trends', 'trends-forecaster') . '</li>';
    $rss2 = fetch_rss (tf_get_hot_trends_atom_url ($country));
    preg_match_all ('/<a [^>]*>([^<>]*)<\/a>/is', $rss2->items[0]['content']['encoded'], $out, PREG_PATTERN_ORDER);

    $out[1] = array_slice ($out[1], 0, $disp_num);
    echo '<ol>';
    foreach ((array)$out[1] as $tmp){
      echo '<li><a href="' . $local_url . $today . '/' . str_replace ('.', '~', $tmp) . '/' . '" target="_blank">' . $tmp . '</a></li>';
    }
    echo '</ol>';

    if ($forecaster != '' && $rss){
      echo '<li><a href="' .
           $local_url .
           'person/' .
           $forecaster .
           '/" target="_blank">' .
           __('My Forecasts', 'trends-forecaster') .
           '</a></li>';

      tf_show_ranking ($rss->items, $disp_num);
    }

    echo '<li><a href="' . $local_url . $today . '/" target="_blank">';
    if ($forecaster != '' && $rss){
      echo __('Public Forecasts', 'trends-forecaster');
    } else{
      echo __('Forecasts', 'trends-forecaster');
    }
    echo '</a></li>';
    $rss = fetch_rss ($local_url . 'feeds/' . $today . '/');

    tf_show_ranking ($rss->items, $disp_num);

    echo '</ul><li>' . __('Tomorrow', 'trends-forecaster') . '</li><ul>';

    if ($forecaster != ''){
      $rss = fetch_rss ($local_url . 'feeds/person/' . $forecaster . '/' . $tomorrow . '/');

      if ($rss){
        echo '<li><a href="' .
             $local_url .
             'person/' .
             $forecaster .
             '/" target="_blank">' .
             __('My Forecasts', 'trends-forecaster') .
             '</a></li>';

        tf_show_ranking ($rss->items, $disp_num);
      }
    }

    echo '<li><a href="' . $local_url . $tomorrow . '/" target="_blank">';
    if ($forecaster != '' && $rss){
      echo __('Public Forecasts', 'trends-forecaster');
    } else{
      echo __('Forecasts', 'trends-forecaster');
    }
    echo '</a></li>';
    $rss = fetch_rss ($local_url . 'feeds/' . $tomorrow . '/');

    tf_show_ranking ($rss->items, $disp_num);

    echo '</ul>';

    echo '<li><a href="' . $local_url . 'ranking/" target="_blank">' . __('Top Forecasters', 'trends-forecaster') . '</a></li>';
    $rss = fetch_rss ($local_url . 'feeds/ranking/direct/');

    tf_show_ranking ($rss->items, $disp_num);

    echo '</ul>';

    echo '<div style="text-align:right;"><a href="' . $local_url . '" target = "_blank">' . $this->trends_forecaster_str . '</a></div>';

    echo $after_widget;
  }

  function update ($new_instance, $old_instance){
    $instance = $old_instance;

    $instance['country'] = strip_tags (stripslashes ($new_instance['country']));
    $instance['forecaster'] = strip_tags (stripslashes ($new_instance['forecaster']));

    return $instance;
  }

  function form ($instance){
    $instance = wp_parse_args ((array)$instance, array ('country' => __('us', 'trends-forecaster'), 'forecaster' => ''));

    $country = htmlspecialchars ($instance['country']);
    $forecaster = htmlspecialchars ($instance['forecaster']);

    $local_url = $this->base_url . $country . '/';

    echo '<p><label for="' .
         $this->get_field_name ('country') .
         '">' .
         __('Country:', 'trends-forecaster') .
         '</label><br /><select class="widefat" id="' .
         $this->get_field_id ('country') .
         '" name="' .
         $this->get_field_name ('country') .
         '"><option value="us"';
    if ($country == 'us'){
      echo ' selected="selected"';
    }
    echo '>' . __('US', 'trends-forecaster') . '</option><option value="jp"';
    if ($country == 'jp'){
      echo ' selected="selected"';
    }
    echo '>' . __('Japan', 'trends-forecaster') . '</option></select></p>';
    echo '<p><label for="' .
         $this->get_field_name ('forecaster') .
         '">' .
         __('Forecaster ID:', 'trends-forecaster') .
         '</label><br /><input class="widefat" id= "' .
         $this->get_field_id ('forecaster') .
         '" name="' .
         $this->get_field_name ('forecaster') .
         '" type="text" value="' .
         $forecaster .
         '" /><br /><small>';
    printf (__("If you have Forecaster ID (not 'Google Account' !), you can see your ID at <a href=\"%saccount/\" target=\"_blank\">%saccount/.</a>",
            'trends-forecaster'),
            $local_url,
            $local_url);
    echo '</small></p>';
  }
}

function TrendsForecasterInit(){
  register_widget ('TrendsForecasterWidget');
}

add_action ('widgets_init', 'TrendsForecasterInit');
?>
