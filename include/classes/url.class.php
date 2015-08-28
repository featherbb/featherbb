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
                                    'İ' => 'Y',
                                    '?' => 'Z',
                                    '?' => 'Z',
                                    '' => 'Z',
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
                                    'ı' => 'y',
                                    '?' => 'y',
                                    '?' => 'z',
                                    '?' => 'z',
                                    '' => 'z',
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

                                    'ğ' => 'd',
                                    'Ğ' => 'D',
                                    'ş' => 'th',
                                    'Ş' => 'TH',

                                    '&'    =>    '-',
                                    '/'    =>    '-',
                                    '('    =>    '-',
                                    '"'    =>    '-',
                                    "'"    =>    '-',

                                );

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
                    $pages[] = '<a'.(empty($pages) ? ' class="item1"' : '').' href="'.str_replace('#', '', $this->get_sublink($link, 'page/$1', $current, $args)).'">'.forum_number_format($current).'</a>';
                } else {
                    $pages[] = '<strong'.(empty($pages) ? ' class="item1"' : '').'>'.forum_number_format($current).'</strong>';
                }
            }

            if ($cur_page <= ($num_pages-3)) {
                if ($cur_page != ($num_pages-3) && $cur_page != ($num_pages-4)) {
                    $pages[] = '<span class="spacer">'.__('Spacer').'</span>';
                }

                $pages[] = '<a'.(empty($pages) ? ' class="item1"' : '').' href="'.$this->get_sublink($link, 'page/$1', $num_pages, $args).'">'.forum_number_format($num_pages).'</a>';
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
                    $pages[] = '<a'.(empty($pages) ? ' class="item1"' : '').' href="'.$link.($current == 1 ? '' : '&amp;p='.$current).'">'.forum_number_format($current).'</a>';
                } else {
                    $pages[] = '<strong'.(empty($pages) ? ' class="item1"' : '').'>'.forum_number_format($current).'</strong>';
                }
            }

            if ($cur_page <= ($num_pages-3)) {
                if ($cur_page != ($num_pages-3) && $cur_page != ($num_pages-4)) {
                    $pages[] = '<span class="spacer">'.__('Spacer').'</span>';
                }

                $pages[] = '<a'.(empty($pages) ? ' class="item1"' : '').' href="'.$link.'&amp;p='.$num_pages.'">'.forum_number_format($num_pages).'</a>';
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
        $str = feather_trim(preg_replace(array('/[^a-z0-9\s]/', '/[\s]+/'), array('', '-'), $str), '-');

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
        if (function_exists('apache_get_modules') && in_array('mod_rewrite', apache_get_modules())) { // If we have Apache's mod_rewrite enabled
            $base_url = get_base_url();
        } else {
            $base_url = get_base_url().'/index.php';
        }

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
        $base_url = get_base_url();

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
}