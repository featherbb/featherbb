<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace FeatherBB;

class Url
{
    protected $url_replace = array(
                                    'À' => 'A',
                                    'Á' => 'A',
                                    'Â' => 'A',
                                    'Ã' => 'A',
                                    'Ä' => 'Ae',
                                    'Å' => 'A',
                                    'Æ' => 'A',
                                    '?' => 'A',
                                    '?' => 'A',
                                    '?' => 'A',
                                    'Ç' => 'C',
                                    '?' => 'C',
                                    '?' => 'C',
                                    '?' => 'C',
                                    '?' => 'C',
                                    '?' => 'D',
                                    '?' => 'D',
                                    'È' => 'E',
                                    'É' => 'E',
                                    'Ê' => 'E',
                                    'Ë' => 'E',
                                    '?' => 'E',
                                    '?' => 'E',
                                    '?' => 'E',
                                    '?' => 'E',
                                    '?' => 'E',
                                    '?' => 'G',
                                    '?' => 'G',
                                    '?' => 'G',
                                    '?' => 'G',
                                    '?' => 'H',
                                    '?' => 'H',
                                    'Ì' => 'I',
                                    'Í' => 'I',
                                    'Î' => 'I',
                                    'Ï' => 'I',
                                    '?' => 'I',
                                    '?' => 'I',
                                    '?' => 'I',
                                    '?' => 'I',
                                    '?' => 'I',
                                    '?' => 'IJ',
                                    '?' => 'J',
                                    '?' => 'K',
                                    '?' => 'K',
                                    '?' => 'K',
                                    '?' => 'K',
                                    '?' => 'K',
                                    '?' => 'L',
                                    'Ñ' => 'N',
                                    '?' => 'N',
                                    '?' => 'N',
                                    '?' => 'N',
                                    '?' => 'N',
                                    'Ò' => 'O',
                                    'Ó' => 'O',
                                    'Ô' => 'O',
                                    'Õ' => 'O',
                                    'Ö' => 'Oe',
                                    'Ø' => 'O',
                                    '?' => 'O',
                                    '?' => 'O',
                                    '?' => 'O',
                                    'Œ' => 'OE',
                                    '?' => 'R',
                                    '?' => 'R',
                                    '?' => 'R',
                                    '?' => 'S',
                                    '?' => 'S',
                                    '?' => 'S',
                                    '?' => 'S',
                                    'Š' => 'S',
                                    '?' => 'T',
                                    '?' => 'T',
                                    '?' => 'T',
                                    '?' => 'T',
                                    'Ù' => 'U',
                                    'Ú' => 'U',
                                    'Û' => 'U',
                                    'Ü' => 'Ue',
                                    '?' => 'U',
                                    '?' => 'U',
                                    '?' => 'U',
                                    '?' => 'U',
                                    '?' => 'U',
                                    '?' => 'U',
                                    '?' => 'W',
                                    '?' => 'Y',
                                    'Ÿ' => 'Y',
                                    'Ý' => 'Y',
                                    '?' => 'Z',
                                    '?' => 'Z',
                                    'Ž' => 'Z',
                                    'à' => 'a',
                                    'á' => 'a',
                                    'â' => 'a',
                                    'ã' => 'a',
                                    'ä' => 'ae',
                                    '?' => 'a',
                                    '?' => 'a',
                                    '?' => 'a',
                                    'å' => 'a',
                                    'æ' => 'ae',
                                    'ç' => 'c',
                                    '?' => 'c',
                                    '?' => 'c',
                                    '?' => 'c',
                                    '?' => 'c',
                                    '?' => 'd',
                                    '?' => 'd',
                                    'è' => 'e',
                                    'é' => 'e',
                                    'ê' => 'e',
                                    'ë' => 'e',
                                    '?' => 'e',
                                    '?' => 'e',
                                    '?' => 'e',
                                    '?' => 'e',
                                    '?' => 'e',
                                    'ƒ' => 'f',
                                    '?' => 'g',
                                    '?' => 'g',
                                    '?' => 'g',
                                    '?' => 'g',
                                    '?' => 'h',
                                    '?' => 'h',
                                    'ì' => 'i',
                                    'í' => 'i',
                                    'î' => 'i',
                                    'ï' => 'i',
                                    '?' => 'i',
                                    '?' => 'i',
                                    '?' => 'i',
                                    '?' => 'i',
                                    '?' => 'i',
                                    '?' => 'ij',
                                    '?' => 'j',
                                    '?' => 'k',
                                    '?' => 'k',
                                    '?' => 'l',
                                    '?' => 'l',
                                    '?' => 'l',
                                    '?' => 'l',
                                    '?' => 'l',
                                    'ñ' => 'n',
                                    '?' => 'n',
                                    '?' => 'n',
                                    '?' => 'n',
                                    '?' => 'n',
                                    '?' => 'n',
                                    'ò' => 'o',
                                    'ó' => 'o',
                                    'ô' => 'o',
                                    'õ' => 'o',
                                    'ö' => 'oe',
                                    'ø' => 'o',
                                    '?' => 'o',
                                    '?' => 'o',
                                    '?' => 'o',
                                    'œ' => 'oe',
                                    '?' => 'r',
                                    '?' => 'r',
                                    '?' => 'r',
                                    '?' => 's',
                                    'š' => 's',
                                    '?' => 't',
                                    'ù' => 'u',
                                    'ú' => 'u',
                                    'û' => 'u',
                                    'ü' => 'ue',
                                    '?' => 'u',
                                    '?' => 'u',
                                    '?' => 'u',
                                    '?' => 'u',
                                    '?' => 'u',
                                    '?' => 'u',
                                    '?' => 'w',
                                    'ÿ' => 'y',
                                    'ý' => 'y',
                                    '?' => 'y',
                                    '?' => 'z',
                                    '?' => 'z',
                                    'ž' => 'z',
                                    'ß' => 'ss',
                                    '?' => 'ss',
                                    '?' => 'A',
                                    '?' => 'A',
                                    '?' => 'A',
                                    '?' => 'A',
                                    '?' => 'A',
                                    '?' => 'A',
                                    '?' => 'A',
                                    '?' => 'A',
                                    '?' => 'A',
                                    '?' => 'A',
                                    '?' => 'A',
                                    '?' => 'A',
                                    '?' => 'A',
                                    '?' => 'A',
                                    '?' => 'A',
                                    '?' => 'A',
                                    '?' => 'A',
                                    '?' => 'A',
                                    '?' => 'A',
                                    '?' => 'A',
                                    '?' => 'A',
                                    '?' => 'A',
                                    '?' => 'A',
                                    '?' => 'B',
                                    '?' => 'G',
                                    '?' => 'D',
                                    '?' => 'E',
                                    '?' => 'E',
                                    '?' => 'E',
                                    '?' => 'E',
                                    '?' => 'E',
                                    '?' => 'E',
                                    '?' => 'E',
                                    '?' => 'E',
                                    '?' => 'E',
                                    '?' => 'E',
                                    '?' => 'Z',
                                    '?' => 'I',
                                    '?' => 'I',
                                    '?' => 'I',
                                    '?' => 'I',
                                    '?' => 'I',
                                    '?' => 'I',
                                    '?' => 'I',
                                    '?' => 'I',
                                    '?' => 'I',
                                    '?' => 'I',
                                    '?' => 'I',
                                    '?' => 'I',
                                    '?' => 'I',
                                    '?' => 'I',
                                    '?' => 'I',
                                    '?' => 'I',
                                    '?' => 'I',
                                    '?' => 'I',
                                    '?' => 'I',
                                    '?' => 'I',
                                    '?' => 'I',
                                    '?' => 'TH',
                                    '?' => 'I',
                                    '?' => 'I',
                                    '?' => 'I',
                                    '?' => 'I',
                                    '?' => 'I',
                                    '?' => 'I',
                                    '?' => 'I',
                                    '?' => 'I',
                                    '?' => 'I',
                                    '?' => 'I',
                                    '?' => 'I',
                                    '?' => 'I',
                                    '?' => 'I',
                                    '?' => 'I',
                                    '?' => 'I',
                                    '?' => 'K',
                                    '?' => 'L',
                                    '?' => 'M',
                                    '?' => 'N',
                                    '?' => 'KS',
                                    '?' => 'O',
                                    '?' => 'O',
                                    '?' => 'O',
                                    '?' => 'O',
                                    '?' => 'O',
                                    '?' => 'O',
                                    '?' => 'O',
                                    '?' => 'O',
                                    '?' => 'O',
                                    '?' => 'O',
                                    '?' => 'P',
                                    '?' => 'R',
                                    '?' => 'R',
                                    '?' => 'S',
                                    '?' => 'T',
                                    '?' => 'Y',
                                    '?' => 'Y',
                                    '?' => 'Y',
                                    '?' => 'Y',
                                    '?' => 'Y',
                                    '?' => 'Y',
                                    '?' => 'Y',
                                    '?' => 'Y',
                                    '?' => 'Y',
                                    '?' => 'Y',
                                    '?' => 'Y',
                                    '?' => 'F',
                                    '?' => 'X',
                                    '?' => 'PS',
                                    '?' => 'O',
                                    '?' => 'O',
                                    '?' => 'O',
                                    '?' => 'O',
                                    '?' => 'O',
                                    '?' => 'O',
                                    '?' => 'O',
                                    '?' => 'O',
                                    '?' => 'O',
                                    '?' => 'O',
                                    '?' => 'O',
                                    '?' => 'O',
                                    '?' => 'O',
                                    '?' => 'O',
                                    '?' => 'O',
                                    '?' => 'O',
                                    '?' => 'O',
                                    '?' => 'O',
                                    '?' => 'O',
                                    '?' => 'O',
                                    '?' => 'O',
                                    '?' => 'a',
                                    '?' => 'a',
                                    '?' => 'a',
                                    '?' => 'a',
                                    '?' => 'a',
                                    '?' => 'a',
                                    '?' => 'a',
                                    '?' => 'a',
                                    '?' => 'a',
                                    '?' => 'a',
                                    '?' => 'a',
                                    '?' => 'a',
                                    '?' => 'a',
                                    '?' => 'a',
                                    '?' => 'a',
                                    '?' => 'a',
                                    '?' => 'a',
                                    '?' => 'a',
                                    '?' => 'a',
                                    '?' => 'a',
                                    '?' => 'a',
                                    '?' => 'a',
                                    '?' => 'a',
                                    '?' => 'a',
                                    '?' => 'a',
                                    '?' => 'a',
                                    '?' => 'a',
                                    '?' => 'b',
                                    '?' => 'g',
                                    '?' => 'd',
                                    '?' => 'e',
                                    '?' => 'e',
                                    '?' => 'e',
                                    '?' => 'e',
                                    '?' => 'e',
                                    '?' => 'e',
                                    '?' => 'e',
                                    '?' => 'e',
                                    '?' => 'e',
                                    '?' => 'e',
                                    '?' => 'z',
                                    '?' => 'i',
                                    '?' => 'i',
                                    '?' => 'i',
                                    '?' => 'i',
                                    '?' => 'i',
                                    '?' => 'i',
                                    '?' => 'i',
                                    '?' => 'i',
                                    '?' => 'i',
                                    '?' => 'i',
                                    '?' => 'i',
                                    '?' => 'i',
                                    '?' => 'i',
                                    '?' => 'i',
                                    '?' => 'i',
                                    '?' => 'i',
                                    '?' => 'i',
                                    '?' => 'i',
                                    '?' => 'i',
                                    '?' => 'i',
                                    '?' => 'i',
                                    '?' => 'i',
                                    '?' => 'i',
                                    '?' => 'i',
                                    '?' => 'i',
                                    '?' => 'th',
                                    '?' => 'i',
                                    '?' => 'i',
                                    '?' => 'i',
                                    '?' => 'i',
                                    '?' => 'i',
                                    '?' => 'i',
                                    '?' => 'i',
                                    '?' => 'i',
                                    '?' => 'i',
                                    '?' => 'i',
                                    '?' => 'i',
                                    '?' => 'i',
                                    '?' => 'i',
                                    '?' => 'i',
                                    '?' => 'i',
                                    '?' => 'i',
                                    '?' => 'i',
                                    '?' => 'i',
                                    '?' => 'i',
                                    '?' => 'i',
                                    '?' => 'k',
                                    '?' => 'l',
                                    '?' => 'm',
                                    '?' => 'n',
                                    '?' => 'ks',
                                    '?' => 'o',
                                    '?' => 'o',
                                    '?' => 'o',
                                    '?' => 'o',
                                    '?' => 'o',
                                    '?' => 'o',
                                    '?' => 'o',
                                    '?' => 'o',
                                    '?' => 'o',
                                    '?' => 'o',
                                    '?' => 'p',
                                    '?' => 'r',
                                    '?' => 'r',
                                    '?' => 'r',
                                    '?' => 's',
                                    '?' => 's',
                                    '?' => 't',
                                    '?' => 'y',
                                    '?' => 'y',
                                    '?' => 'y',
                                    '?' => 'y',
                                    '?' => 'y',
                                    '?' => 'y',
                                    '?' => 'y',
                                    '?' => 'y',
                                    '?' => 'y',
                                    '?' => 'y',
                                    '?' => 'y',
                                    '?' => 'y',
                                    '?' => 'y',
                                    '?' => 'y',
                                    '?' => 'y',
                                    '?' => 'y',
                                    '?' => 'y',
                                    '?' => 'y',
                                    '?' => 'y',
                                    '?' => 'y',
                                    '?' => 'f',
                                    '?' => 'x',
                                    '?' => 'ps',
                                    '?' => 'o',
                                    '?' => 'o',
                                    '?' => 'o',
                                    '?' => 'o',
                                    '?' => 'o',
                                    '?' => 'o',
                                    '?' => 'o',
                                    '?' => 'o',
                                    '?' => 'o',
                                    '?' => 'o',
                                    '?' => 'o',
                                    '?' => 'o',
                                    '?' => 'o',
                                    '?' => 'o',
                                    '?' => 'o',
                                    '?' => 'o',
                                    '?' => 'o',
                                    '?' => 'o',
                                    '?' => 'o',
                                    '?' => 'o',
                                    '?' => 'o',
                                    '?' => 'o',
                                    '?' => 'o',
                                    '?' => 'o',
                                    '?' => 'o',
                                    '¨' => '',
                                    '?' => '',
                                    '?' => '',
                                    '?' => '',
                                    '?' => '',
                                    '?' => '',
                                    '?' => '',
                                    '?' => '',
                                    '?' => '',
                                    '?' => '',
                                    '?' => '',
                                    '?' => '',
                                    '?' => '',
                                    '?' => '',
                                    '?' => '',
                                    '?' => '',
                                    '?' => '',
                                    '?' => '',
                                    '?' => 'A',
                                    '?' => 'B',
                                    '?' => 'V',
                                    '?' => 'G',
                                    '?' => 'D',
                                    '?' => 'E',
                                    '?' => 'E',
                                    '?' => 'ZH',
                                    '?' => 'Z',
                                    '?' => 'I',
                                    '?' => 'I',
                                    '?' => 'K',
                                    '?' => 'L',
                                    '?' => 'M',
                                    '?' => 'N',
                                    '?' => 'O',
                                    '?' => 'P',
                                    '?' => 'R',
                                    '?' => 'S',
                                    '?' => 'T',
                                    '?' => 'U',
                                    '?' => 'F',
                                    '?' => 'KH',
                                    '?' => 'TS',
                                    '?' => 'CH',
                                    '?' => 'SH',
                                    '?' => 'SHCH',
                                    '?' => 'Y',
                                    '?' => 'E',
                                    '?' => 'YU',
                                    '?' => 'YA',
                                    '?' => 'A',
                                    '?' => 'B',
                                    '?' => 'V',
                                    '?' => 'G',
                                    '?' => 'D',
                                    '?' => 'E',
                                    '?' => 'E',
                                    '?' => 'ZH',
                                    '?' => 'Z',
                                    '?' => 'I',
                                    '?' => 'I',
                                    '?' => 'K',
                                    '?' => 'L',
                                    '?' => 'M',
                                    '?' => 'N',
                                    '?' => 'O',
                                    '?' => 'P',
                                    '?' => 'R',
                                    '?' => 'S',
                                    '?' => 'T',
                                    '?' => 'U',
                                    '?' => 'F',
                                    '?' => 'KH',
                                    '?' => 'TS',
                                    '?' => 'CH',
                                    '?' => 'SH',
                                    '?' => 'SHCH',
                                    '?' => 'Y',
                                    '?' => 'E',
                                    '?' => 'YU',
                                    '?' => 'YA',
                                    '?' => '',
                                    '?' => '',
                                    '?' => '',
                                    '?' => '',
                                    '?' => 'YE',
                                    '?' => 'YE',
                                    '?' => 'YI',
                                    '?' => 'YI',
                                    '?' => 'KG',
                                    '?' => 'KG',
                                    'ð' => 'd',
                                    'Ð' => 'D',
                                    'þ' => 'th',
                                    'Þ' => 'TH',
                                    '&'    =>    '-',
                                    '/'    =>    '-',
                                    '('    =>    '-',
                                    '"'    =>    '-',
                                    "'"    =>    '-',
                            );

    public function __construct()
    {
        $this->feather = \Slim\Slim::getInstance();
    }

    //
    // Generate a string with numbered links (for multipage scripts)
    //
    public function paginate($num_pages, $cur_page, $link, $args = null)
    {
        $pages = array();
        $link_to_all = false;

        // If $cur_page == -1, we link to all pages (used in viewforum.php)
        if ($cur_page == -1) {
            $cur_page = 1;
            $link_to_all = true;
        }

        if ($num_pages <= 1) {
            $pages = array('<strong class="item1">1</strong>');
        } else {
            // Add a previous page link
            if ($num_pages > 1 && $cur_page > 1) {
                $pages[] = '<a rel="prev"'.(empty($pages) ? ' class="item1"' : '').' href="'.$this->get_sublink($link, 'page/$1', ($cur_page - 1), $args).'">'.__('Previous').'</a>';
            }

            if ($cur_page > 3) {
                $pages[] = '<a'.(empty($pages) ? ' class="item1"' : '').' href="'.$link.'">1</a>';

                if ($cur_page > 5) {
                    $pages[] = '<span class="spacer">'.__('Spacer').'</span>';
                }
            }

            // Don't ask me how the following works. It just does, OK? :-)
            for ($current = ($cur_page == 5) ? $cur_page - 3 : $cur_page - 2, $stop = ($cur_page + 4 == $num_pages) ? $cur_page + 4 : $cur_page + 3; $current < $stop; ++$current) {
                if ($current < 1 || $current > $num_pages) {
                    continue;
                } elseif ($current != $cur_page || $link_to_all) {
                    $pages[] = '<a'.(empty($pages) ? ' class="item1"' : '').' href="'.str_replace('#', '', $this->get_sublink($link, 'page/$1', $current, $args)).'">'.$this->feather->utils->forum_number_format($current).'</a>';
                } else {
                    $pages[] = '<strong'.(empty($pages) ? ' class="item1"' : '').'>'.$this->feather->utils->forum_number_format($current).'</strong>';
                }
            }

            if ($cur_page <= ($num_pages-3)) {
                if ($cur_page != ($num_pages-3) && $cur_page != ($num_pages-4)) {
                    $pages[] = '<span class="spacer">'.__('Spacer').'</span>';
                }

                $pages[] = '<a'.(empty($pages) ? ' class="item1"' : '').' href="'.$this->get_sublink($link, 'page/$1', $num_pages, $args).'">'.$this->feather->utils->forum_number_format($num_pages).'</a>';
            }

            // Add a next page link
            if ($num_pages > 1 && !$link_to_all && $cur_page < $num_pages) {
                $pages[] = '<a rel="next"'.(empty($pages) ? ' class="item1"' : '').' href="'.$this->get_sublink($link, 'page/$1', ($cur_page + 1), $args).'">'.__('Next').'</a>';
            }
        }

        return implode(' ', $pages);
    }

    //
    // Generate a string with numbered links (for multipage scripts)
    // Old FluxBB-style function for search page
    //
    public function paginate_old($num_pages, $cur_page, $link)
    {
        $pages = array();
        $link_to_all = false;

        // If $cur_page == -1, we link to all pages (used in viewforum.php)
        if ($cur_page == -1) {
            $cur_page = 1;
            $link_to_all = true;
        }

        if ($num_pages <= 1) {
            $pages = array('<strong class="item1">1</strong>');
        } else {
            // Add a previous page link
            if ($num_pages > 1 && $cur_page > 1) {
                $pages[] = '<a rel="prev"'.(empty($pages) ? ' class="item1"' : '').' href="'.$link.($cur_page == 2 ? '' : '&amp;p='.($cur_page - 1)).'">'.__('Previous').'</a>';
            }

            if ($cur_page > 3) {
                $pages[] = '<a'.(empty($pages) ? ' class="item1"' : '').' href="'.$link.'">1</a>';

                if ($cur_page > 5) {
                    $pages[] = '<span class="spacer">'.__('Spacer').'</span>';
                }
            }

            // Don't ask me how the following works. It just does, OK? :-)
            for ($current = ($cur_page == 5) ? $cur_page - 3 : $cur_page - 2, $stop = ($cur_page + 4 == $num_pages) ? $cur_page + 4 : $cur_page + 3; $current < $stop; ++$current) {
                if ($current < 1 || $current > $num_pages) {
                    continue;
                } elseif ($current != $cur_page || $link_to_all) {
                    $pages[] = '<a'.(empty($pages) ? ' class="item1"' : '').' href="'.$link.($current == 1 ? '' : '&amp;p='.$current).'">'.$this->feather->utils->forum_number_format($current).'</a>';
                } else {
                    $pages[] = '<strong'.(empty($pages) ? ' class="item1"' : '').'>'.$this->feather->utils->forum_number_format($current).'</strong>';
                }
            }

            if ($cur_page <= ($num_pages-3)) {
                if ($cur_page != ($num_pages-3) && $cur_page != ($num_pages-4)) {
                    $pages[] = '<span class="spacer">'.__('Spacer').'</span>';
                }

                $pages[] = '<a'.(empty($pages) ? ' class="item1"' : '').' href="'.$link.'&amp;p='.$num_pages.'">'.$this->feather->utils->forum_number_format($num_pages).'</a>';
            }

            // Add a next page link
            if ($num_pages > 1 && !$link_to_all && $cur_page < $num_pages) {
                $pages[] = '<a rel="next"'.(empty($pages) ? ' class="item1"' : '').' href="'.$link.'&amp;p='.($cur_page +1).'">'.__('Next').'</a>';
            }
        }

        return implode(' ', $pages);
    }

    //
    // Make a string safe to use in a URL
    // Inspired by (c) Panther <http://www.pantherforum.org/>
    //
    public function url_friendly($str)
    {
        $str = strtr($str, $this->url_replace);
        $str = strtolower(utf8_decode($str));
        $str = $this->feather->utils->trim(preg_replace(array('/[^a-z0-9\s]/', '/[\s]+/'), array('', '-'), $str), '-');

        if (empty($str)) {
            $str = 'view';
        }

        return $str;
    }

    //
    // Generate link to another page on the forum
    // Inspired by (c) Panther <http://www.pantherforum.org/>
    //
    public function get($link, $args = null)
    {
        $base_url = $this->base();

        $gen_link = $link;
        if ($args == null) {
            $gen_link = $base_url.'/'.$link;
        } elseif (!is_array($args)) {
            $gen_link = $base_url.'/'.str_replace('$1', $args, $link);
        } else {
            for ($i = 0; isset($args[$i]); ++$i) {
                $gen_link = str_replace('$'.($i + 1), $args[$i], $gen_link);
            }
            $gen_link = $base_url.'/'.$gen_link;
        }

        return $gen_link;
    }

    //
    // Generate a hyperlink with parameters and anchor and a subsection such as a subpage
    // Inspired by (c) Panther <http://www.pantherforum.org/>
    //
    private function get_sublink($link, $sublink, $subarg, $args = null)
    {
        $base_url = $this->base();

        if ($sublink == 'p$1' && $subarg == 1) {
            return $this->get($link, $args);
        }

        $gen_link = $link;
        if (!is_array($args) && $args != null) {
            $gen_link = str_replace('$1', $args, $link);
        } else {
            for ($i = 0; isset($args[$i]); ++$i) {
                $gen_link = str_replace('$'.($i + 1), $args[$i], $gen_link);
            }
        }

        $gen_link = $base_url.'/'.str_replace('#', str_replace('$1', str_replace('$1', $subarg, $sublink), '$1/'), $gen_link);

        return $gen_link;
    }

    //
    // Fetch the current protocol in use - http or https
    //
    public function protocol()
    {
        return $this->feather->request->getScheme();
    }

    //
    // Fetch the base_url, optionally support HTTPS and HTTP
    //
    public function base()
    {
        return $this->feather->request->getScriptName();
    }

    //
    // function is_valid($url) {
    //
    // Return associative array of valid URI components, or FALSE if $url is not
    // RFC-3986 compliant. If the passed URL begins with: "www." or "ftp.", then
    // "http://" or "ftp://" is prepended and the corrected full-url is stored in
    // the return array with a key name "url". This value should be used by the caller.
    //
    // Return value: FALSE if $url is not valid, otherwise array of URI components:
    // e.g.
    // Given: "http://www.jmrware.com:80/articles?height=10&width=75#fragone"
    // Array(
    //	  [scheme] => http
    //	  [authority] => www.jmrware.com:80
    //	  [userinfo] =>
    //	  [host] => www.jmrware.com
    //	  [IP_literal] =>
    //	  [IPV6address] =>
    //	  [ls32] =>
    //	  [IPvFuture] =>
    //	  [IPv4address] =>
    //	  [regname] => www.jmrware.com
    //	  [port] => 80
    //	  [path_abempty] => /articles
    //	  [query] => height=10&width=75
    //	  [fragment] => fragone
    //	  [url] => http://www.jmrware.com:80/articles?height=10&width=75#fragone
    // )
    public function is_valid($url)
    {
        if (strpos($url, 'www.') === 0) {
            $url = 'http://'. $url;
        }
        if (strpos($url, 'ftp.') === 0) {
            $url = 'ftp://'. $url;
        }
        if (!preg_match('/# Valid absolute URI having a non-empty, valid DNS host.
		^
		(?P<scheme>[A-Za-z][A-Za-z0-9+\-.]*):\/\/
		(?P<authority>
		  (?:(?P<userinfo>(?:[A-Za-z0-9\-._~!$&\'()*+,;=:]|%[0-9A-Fa-f]{2})*)@)?
		  (?P<host>
			(?P<IP_literal>
			  \[
			  (?:
				(?P<IPV6address>
				  (?:												 (?:[0-9A-Fa-f]{1,4}:){6}
				  |												   ::(?:[0-9A-Fa-f]{1,4}:){5}
				  | (?:							 [0-9A-Fa-f]{1,4})?::(?:[0-9A-Fa-f]{1,4}:){4}
				  | (?:(?:[0-9A-Fa-f]{1,4}:){0,1}[0-9A-Fa-f]{1,4})?::(?:[0-9A-Fa-f]{1,4}:){3}
				  | (?:(?:[0-9A-Fa-f]{1,4}:){0,2}[0-9A-Fa-f]{1,4})?::(?:[0-9A-Fa-f]{1,4}:){2}
				  | (?:(?:[0-9A-Fa-f]{1,4}:){0,3}[0-9A-Fa-f]{1,4})?::	[0-9A-Fa-f]{1,4}:
				  | (?:(?:[0-9A-Fa-f]{1,4}:){0,4}[0-9A-Fa-f]{1,4})?::
				  )
				  (?P<ls32>[0-9A-Fa-f]{1,4}:[0-9A-Fa-f]{1,4}
				  | (?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}
					   (?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)
				  )
				|	(?:(?:[0-9A-Fa-f]{1,4}:){0,5}[0-9A-Fa-f]{1,4})?::	[0-9A-Fa-f]{1,4}
				|	(?:(?:[0-9A-Fa-f]{1,4}:){0,6}[0-9A-Fa-f]{1,4})?::
				)
			  | (?P<IPvFuture>[Vv][0-9A-Fa-f]+\.[A-Za-z0-9\-._~!$&\'()*+,;=:]+)
			  )
			  \]
			)
		  | (?P<IPv4address>(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}
							   (?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?))
		  | (?P<regname>(?:[A-Za-z0-9\-._~!$&\'()*+,;=]|%[0-9A-Fa-f]{2})+)
		  )
		  (?::(?P<port>[0-9]*))?
		)
		(?P<path_abempty>(?:\/(?:[A-Za-z0-9\-._~!$&\'()*+,;=:@]|%[0-9A-Fa-f]{2})*)*)
		(?:\?(?P<query>		  (?:[A-Za-z0-9\-._~!$&\'()*+,;=:@\\/?]|%[0-9A-Fa-f]{2})*))?
		(?:\#(?P<fragment>	  (?:[A-Za-z0-9\-._~!$&\'()*+,;=:@\\/?]|%[0-9A-Fa-f]{2})*))?
		$
		/mx', $url, $m)) {
            return false;
        }
        switch ($m['scheme']) {
            case 'https':
            case 'http':
                if ($m['userinfo']) {
                    return false;
                } // HTTP scheme does not allow userinfo.
                break;
            case 'ftps':
            case 'ftp':
                break;
            default:
                return false;    // Unrecognised URI scheme. Default to FALSE.
        }
        // Validate host name conforms to DNS "dot-separated-parts".
        if ($m{'regname'}) {
            // If host regname specified, check for DNS conformance.

            if (!preg_match('/# HTTP DNS host name.
			^					   # Anchor to beginning of string.
			(?!.{256})			   # Overall host length is less than 256 chars.
			(?:					   # Group dot separated host part alternatives.
			  [0-9A-Za-z]\.		   # Either a single alphanum followed by dot
			|					   # or... part has more than one char (63 chars max).
			  [0-9A-Za-z]		   # Part first char is alphanum (no dash).
			  [\-0-9A-Za-z]{0,61}  # Internal chars are alphanum plus dash.
			  [0-9A-Za-z]		   # Part last char is alphanum (no dash).
			  \.				   # Each part followed by literal dot.
			)*					   # One or more parts before top level domain.
			(?:					   # Top level domains
			  [A-Za-z]{2,63}|	   # Country codes are exactly two alpha chars.
			  xn--[0-9A-Za-z]{4,59})		   # Internationalized Domain Name (IDN)
			$					   # Anchor to end of string.
			/ix', $m['host'])) {
                return false;
            }
        }
        $m['url'] = $url;
        for ($i = 0; isset($m[$i]); ++$i) {
            unset($m[$i]);
        }
        return $m; // return TRUE == array of useful named $matches plus the valid $url.
    }


    //
    // Display $message and redirect user to $destination_url
    //
    public function redirect($destination_url, $message = null, $status = 302)
    {
        // Set default type to info if not provided
        if (!is_array($message))
            $message = array('info', $message);
        // Add a flash message
        $this->feather->flash($message[0], $message[1]);

        $this->feather->redirect($destination_url);
    }
}
