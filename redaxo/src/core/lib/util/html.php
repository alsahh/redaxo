<?php

/**
 * Html utility class
 *
 * @author thomas.blum
 * @package redaxo5
 */
class rex_html
{

  /**
   * attributes
   */
  private function attributes($attributes)
  {
    $attr = '';

    if (is_null($attributes)) {
      return;
    }

    if (is_string($attributes)) {
      $attributes = rex_string::split($attributes);
    }

    if (is_array($attributes)) {
      foreach ($attributes as $key => $value) {
        $attr .= ' ' . $key . '="' . $value . '"';
      }
    }

    return $attr;
  }


  /**
   * htmllist
   *
   * Example:
   * $list = array(0 => array('text' => 'Text',
   *                          'href' => '#',
   *                          'children' = array(0 => array('text' => 'Text'))
   *                         )
   *              )
   *
   * @param string       $list_tag
   * @param string       $item_tag
   * @param array        $list
   * @param array|string $attributes
   * @return string
   */
  private function htmllist($list_tag, $item_tag, $list, $attributes = null)
  {
    $return = '';

    if (is_array($list)) {

      $items = '';

      foreach ($list as $e) {

        $text = '';
        if (isset($e['text'])) {
          $text = $e['text'];
        }

        if (isset($e['href']) && !empty($text)) {
          $text = self::a($text, $e['href']);
        }

        $children = '';
        if (isset($e['children'])) {
          $children = self::htmllist($list_tag, $item_tag, $e['children']);
        }

        $items .= '<' . $item_tag . '>' . $text . $children . '</' . $item_tag . '>';

      }

      $attr = self::attributes($attributes);

      $return = $items != '' ? '<' . $list_tag . $attr . '>' . $items . '</' . $list_tag . '>' : '';
    }

    return $return;
  }


  /**
   * anchor
   *
   * @param string       $text
   * @param string       $href
   * @param array|string $attributes
   * @return string
   */
  static public function a($text, $href, $attributes = null)
  {
    $attr = self::attributes($attributes);

    return '<a href="' . $href . '"' . $attr . '>' . $text . '</a>';

  }


  /**
   * definition list
   *
   * Example:
   * $list = array( array('dt 1', 'dd 1'),
   *                array('dt 2', array('dd 2a', 'dd 2b'))
   *              );
   *
   * @param array        $list
   * @param array|string $attributes
   * @return string
   */
  static public function dl($list, $attributes = null)
  {
    $return = '';

    if (is_array($list)) {

      $items = '';

      foreach ($list as $pair) {

        if (!empty($pair[0]))
          $items .= '<dt>' . $pair[0] . '</dt>';

        if (isset($pair[1]) && is_array($pair[1]))
          $items .= '<dd>' . implode('</dd><dd>', $pair[1]) . '</dd>';

        if (isset($pair[1]) && is_string($pair[1]))
          $items .= '<dd>' . $pair[1] . '</dd>';
      }
    }

    $attr = self::attributes($attributes);

    $return = $items != '' ? '<dl' . $attr . '>' . $items . '</dl>' : '';

    return $return;
  }


  /**
   * ordered list
   *
   * @see htmllist
   *
   * @param array        $list
   * @param array|string $attributes
   * @return string
   */
  static public function ol($list, $attributes = null)
  {
    return self::htmllist('ol', 'li',  $list, $attributes);
  }


  /**
   * unordered list
   *
   * @see htmllist
   *
   * @param array        $list
   * @param array|string $attributes
   * @return string
   */
  static public function ul($list, $attributes = null)
  {
    return self::htmllist('ul', 'li',  $list, $attributes);
  }
}
