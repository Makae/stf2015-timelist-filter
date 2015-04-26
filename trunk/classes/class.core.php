<?php

class STF2015_Filter {
  private static $instance;

  private function __construct() {
    add_action('wp_enqueue_scripts', array($this, 'wp_enqueue_scripts'), 100);
    add_action('admin_enqueue_scripts', array($this, 'wp_enqueue_scripts'), 100);
    add_shortcode('stf2015_filter_list', array($this, 'sc_filter_list'));
  }

  public static function instance() {
    if(is_null(static::$instance))
      static::$instance = new STF2015_Filter();

    return static::$instance;
  }

  public function wp_enqueue_scripts() {
    wp_enqueue_style('stf2015_style', STF_FILTER_URL .'css/stf2015_filter_list.css');
    wp_enqueue_script('stf2015_script', STF_FILTER_URL .'js/stf2015_filter_list.js', array('jquery'), '0.1', true);

  }

  public function sc_filter_list() {
    $query = array_key_exists('filter_query', $_REQUEST) ? $_REQUEST['filter_query'] : '';
    $list = $this->draw_filter_list(strip_tags($query));
    $html =
      '<div class="stf2015_filter_list_wrapper">'.
        '<form action="' . get_the_permalink() .'" method="post">'.
          '<input type="text" name="filter_query" placeholder="Name oder Verein" value="' . $query . '" />' .
          '<input type="submit" value="Suchen" />' .
        '</form>' .
        $list .
      '</div>';

    return $html;
  }

  public function draw_filter_list($query) {
    $table_list = '';
    $col_config = array(
      'headings' => array('KatNr', 'GrpNr', 'Verein', 'Ident.', 'Ktn', 'Zeit',  '1. Wettkampfteil',  'Zeit',  '2. Wettkampfteil',  'Zeit',  '3. Wettkampfteil'),
      'names' => array('cat_no', 'group_no', 'association', 'identification', 'ktn', 'time1', 'part1', 'time2', 'part2', 'time3', 'part3')
    );

    $col_config_responsive = array(
      'headings' => array('GrpNr', 'Verein', 'Ktn'),
      'names' => array('group_no', 'association', 'ktn')
    );
    $rootset = $this->subSets($this->get_timetable($query), array('category', 'date'));
    foreach($rootset as $cat_subset) {
      $table_list .= '<h5>' . $cat_subset['split_value'] . '</h5>';
      foreach($cat_subset['subsets'] as $date_subset) {
        $subheading = $this->translate_date($date_subset['split_value']);
        $overlay_config = rawurlencode(json_encode(array(
          'heading' => $cat_subset['split_value'],
          'subheading' => $subheading,
          'data' => $date_subset['subsets'],
          'col_config' => $col_config,
          'content_type' =>  $date_subset['subsets'][0]['cat_no']
        )));

        $table_list .= '<h6>' . $subheading . '</h6>';
        $table_list .= '<div class="stf_resp_wrapper" data-overlay="' . $overlay_config . '">';
        $table_list .= $this->filter_subset_table($date_subset['subsets'], $col_config, $query, 'stf_non_resp_elem');
        $table_list .= $this->filter_subset_table($date_subset['subsets'], $col_config_responsive, $query, 'stf_resp_elem', true);
        $table_list .= '</div>';
      }
    }
    return $table_list;
  }

  private function translate_date($date) {
    $trans = array(
        'Monday'    => 'Montag',
        'Tuesday'   => 'Dienstag',
        'Wednesday' => 'Mittwoch',
        'Thursday'  => 'Donnerstag',
        'Friday'    => 'Freitag',
        'Saturday'  => 'Samstag',
        'Sunday'    => 'Sonntag',
        'Mon'       => 'Mo',
        'Tue'       => 'Di',
        'Wed'       => 'Mi',
        'Thu'       => 'Do',
        'Fri'       => 'Fr',
        'Sat'       => 'Sa',
        'Sun'       => 'So',
        'January'   => 'Januar',
        'February'  => 'Februar',
        'March'     => 'März',
        'May'       => 'Mai',
        'June'      => 'Juni',
        'July'      => 'Juli',
        'October'   => 'Oktober',
        'December'  => 'Dezember'
    );
    $strdate = date('l, \d\e\n d. F Y', strtotime($date));
    return strtr($strdate, $trans);
  }

  private function filter_subset_table($table, $column_config, $highlight=false, $css_cls='', $info_button=false) {
    $headings = $column_config['headings'];
    $col_names = $column_config['names'];
    $col_fns = array('time1' => 'STF2015_Filter::short_time',
                     'time2' => 'STF2015_Filter::short_time',
                     'time3' => 'STF2015_Filter::short_time',
                     'part1' => 'STF2015_Filter::part_value',
                     'part2' => 'STF2015_Filter::part_value',
                     'part3' => 'STF2015_Filter::part_value');
    $thead = '<thead><tr>' . "\n";
    foreach($headings as $heading)
      $thead .= '<th>' . $heading . '</th>'. "\n";

    if($info_button === true)
      $thead .= '<th>Mehr Infos</th>'. "\n";


    $thead .= '</tr></thead>' . "\n";
    $tbody = '<tbody>' . "\n";

    foreach($table as $idx => $row) {
      $tbody .= '<tr data-idx="' . $idx . '">' . "\n";
      foreach($col_names as $col) {
        $val = $row[$col];
        if(array_key_exists($col, $col_fns) && is_callable($col_fns[$col]))
          $val = call_user_func_array($col_fns[$col], array($val));
        $tbody .= '<td>' . $this->highlight($val, $highlight) . '</td>' . "\n";
      }

      if($info_button === true)
        $tbody .= '<td class="stf_info_cell"><span></span></td>' . "\n";

      $tbody .= '</tr>' . "\n";
    }
    $tbody .= '</tbody>' . "\n";
    return '<table class="stf2015_filter_list ' . $css_cls . '" >' . $thead . $tbody . '</table>';
  }

  public function highlight($str, $search, $replace="<span class='highlight'>$0</span>") {
      if(!$search)
        return $str;
      return preg_replace('/' . $search .'/i', $replace, $str);
  }

  public static function short_time($long_time) {
    $val = substr($long_time, 0, 5);
    return $val == '00:00' ? '' : $val;
  }

  public static function part_value($val) {
    return trim($val) == '' ? '' : $val;
  }

  public function get_timetable($query='') {
    if($count) {
      $select = 'SELECT COUNT(*) ';
      $limit = '';
      $where = ' WHERE `association` LIKE "%' . esc_sql($query) . '%" ';
    } else {
      $select = 'SELECT * ';
      //$limit = 'LIMIT {%from%}, {%num%}';
      $where = ' WHERE `association` LIKE "%' . esc_sql($query) . '%" ';
    }
    $where = $query == '' ? '' : $where;
    $order = 'ORDER BY' .
                '`cat_no` ASC, ' .
                '`date` ASC, ' .
                '`association` ASC';


    $sql = '{%SELECT%} FROM `{%table%}`' .
           ' {%WHERE%} ' .
           ' {%ORDER%}' .
           ' {%LIMIT%}';

    $top_data = array(
      'SELECT' => $select,
      'table' => STF_FILTER_TABLE,
      'ORDER' => $order,
      'LIMIT' => $limit);
    $sql = $this->sql_template($sql, $top_data);
    $sql = $this->sql_template($sql, array('WHERE' => $where), false);

    $result =  $this->query($sql);
    return $result;
  }

  private function subSets($table, $subsets=array()) {
    $sets = array();
    $split_key = array_shift($subsets);
    if(!$split_key)
      return $table;

    $previous = null;
    foreach($table as $idx => $row) {
      if($previous == null || $previous[$split_key] != $row[$split_key])
        $set = array();
      $set[] = $row;

      if(count($table) <= $idx + 1 || $row[$split_key] != $table[$idx + 1][$split_key])
        $sets[] = array('split_key' => $split_key, 'split_value' => $row[$split_key], 'subsets' => $this->subSets($set, $subsets));

      $previous = $row;
    }
    return $sets;
  }

  private function sql_template($string, $replace, $escape=true, $prefix = '{%', $suffix = '%}') {
    foreach($replace as $search => $replace) {
      $replace = $escape ? esc_sql($replace) : $replace;
      $string = str_replace($prefix . $search . $suffix, $replace, $string);
    }
    return $string;
  }

  private function query($sql) {
    global $wpdb;
    $result = $wpdb->get_results($sql, ARRAY_A);

    return $result;
  }

}