<?php

/**
 * Copyright (C) 2015-2016 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace FeatherBB\Core;

class Url
{
    protected static $url_replace = [
                                        'À' => 'A',
                                        'Á' => 'A',
                                        'Â' => 'A',
                                        'Ã' => 'A',
                                        'Ä' => 'Ae',
                                        'Å' => 'A',
                                        'Æ' => 'A',
                                        'Ā' => 'A',
                                        'Ą' => 'A',
                                        'Ă' => 'A',
                                        'Ç' => 'C',
                                        'Ć' => 'C',
                                        'Č' => 'C',
                                        'Ĉ' => 'C',
                                        'Ċ' => 'C',
                                        'Ď' => 'D',
                                        'Đ' => 'D',
                                        'È' => 'E',
                                        'É' => 'E',
                                        'Ê' => 'E',
                                        'Ë' => 'E',
                                        'Ē' => 'E',
                                        'Ę' => 'E',
                                        'Ě' => 'E',
                                        'Ĕ' => 'E',
                                        'Ė' => 'E',
                                        'Ĝ' => 'G',
                                        'Ğ' => 'G',
                                        'Ġ' => 'G',
                                        'Ģ' => 'G',
                                        'Ĥ' => 'H',
                                        'Ħ' => 'H',
                                        'Ì' => 'I',
                                        'Í' => 'I',
                                        'Î' => 'I',
                                        'Ï' => 'I',
                                        'Ī' => 'I',
                                        'Ĩ' => 'I',
                                        'Ĭ' => 'I',
                                        'Į' => 'I',
                                        'İ' => 'I',
                                        'Ĳ' => 'IJ',
                                        'Ĵ' => 'J',
                                        'Ķ' => 'K',
                                        'Ľ' => 'K',
                                        'Ĺ' => 'K',
                                        'Ļ' => 'K',
                                        'Ŀ' => 'K',
                                        'Ł' => 'L',
                                        'Ñ' => 'N',
                                        'Ń' => 'N',
                                        'Ň' => 'N',
                                        'Ņ' => 'N',
                                        'Ŋ' => 'N',
                                        'Ò' => 'O',
                                        'Ó' => 'O',
                                        'Ô' => 'O',
                                        'Õ' => 'O',
                                        'Ö' => 'Oe',
                                        'Ø' => 'O',
                                        'Ō' => 'O',
                                        'Ő' => 'O',
                                        'Ŏ' => 'O',
                                        'Œ' => 'OE',
                                        'Ŕ' => 'R',
                                        'Ř' => 'R',
                                        'Ŗ' => 'R',
                                        'Ś' => 'S',
                                        'Ş' => 'S',
                                        'Ŝ' => 'S',
                                        'Ș' => 'S',
                                        'Š' => 'S',
                                        'Ť' => 'T',
                                        'Ţ' => 'T',
                                        'Ŧ' => 'T',
                                        'Ț' => 'T',
                                        'Ù' => 'U',
                                        'Ú' => 'U',
                                        'Û' => 'U',
                                        'Ü' => 'Ue',
                                        'Ū' => 'U',
                                        'Ů' => 'U',
                                        'Ű' => 'U',
                                        'Ŭ' => 'U',
                                        'Ũ' => 'U',
                                        'Ų' => 'U',
                                        'Ŵ' => 'W',
                                        'Ŷ' => 'Y',
                                        'Ÿ' => 'Y',
                                        'Ý' => 'Y',
                                        'Ź' => 'Z',
                                        'Ż' => 'Z',
                                        'Ž' => 'Z',
                                        'à' => 'a',
                                        'á' => 'a',
                                        'â' => 'a',
                                        'ã' => 'a',
                                        'ä' => 'ae',
                                        'ā' => 'a',
                                        'ą' => 'a',
                                        'ă' => 'a',
                                        'å' => 'a',
                                        'æ' => 'ae',
                                        'ç' => 'c',
                                        'ć' => 'c',
                                        'č' => 'c',
                                        'ĉ' => 'c',
                                        'ċ' => 'c',
                                        'ď' => 'd',
                                        'đ' => 'd',
                                        'è' => 'e',
                                        'é' => 'e',
                                        'ê' => 'e',
                                        'ë' => 'e',
                                        'ē' => 'e',
                                        'ę' => 'e',
                                        'ě' => 'e',
                                        'ĕ' => 'e',
                                        'ė' => 'e',
                                        'ƒ' => 'f',
                                        'ĝ' => 'g',
                                        'ğ' => 'g',
                                        'ġ' => 'g',
                                        'ģ' => 'g',
                                        'ĥ' => 'h',
                                        'ħ' => 'h',
                                        'ì' => 'i',
                                        'í' => 'i',
                                        'î' => 'i',
                                        'ï' => 'i',
                                        'ī' => 'i',
                                        'ĩ' => 'i',
                                        'ĭ' => 'i',
                                        'į' => 'i',
                                        'ı' => 'i',
                                        'ĳ' => 'ij',
                                        'ĵ' => 'j',
                                        'ķ' => 'k',
                                        'ĸ' => 'k',
                                        'ł' => 'l',
                                        'ľ' => 'l',
                                        'ĺ' => 'l',
                                        'ļ' => 'l',
                                        'ŀ' => 'l',
                                        'ñ' => 'n',
                                        'ń' => 'n',
                                        'ň' => 'n',
                                        'ņ' => 'n',
                                        'ŉ' => 'n',
                                        'ŋ' => 'n',
                                        'ò' => 'o',
                                        'ó' => 'o',
                                        'ô' => 'o',
                                        'õ' => 'o',
                                        'ö' => 'oe',
                                        'ø' => 'o',
                                        'ō' => 'o',
                                        'ő' => 'o',
                                        'ŏ' => 'o',
                                        'œ' => 'oe',
                                        'ŕ' => 'r',
                                        'ř' => 'r',
                                        'ŗ' => 'r',
                                        'ś' => 's',
                                        'š' => 's',
                                        'ť' => 't',
                                        'ù' => 'u',
                                        'ú' => 'u',
                                        'û' => 'u',
                                        'ü' => 'ue',
                                        'ū' => 'u',
                                        'ů' => 'u',
                                        'ű' => 'u',
                                        'ŭ' => 'u',
                                        'ũ' => 'u',
                                        'ų' => 'u',
                                        'ŵ' => 'w',
                                        'ÿ' => 'y',
                                        'ý' => 'y',
                                        'ŷ' => 'y',
                                        'ż' => 'z',
                                        'ź' => 'z',
                                        'ž' => 'z',
                                        'ß' => 'ss',
                                        'ſ' => 'ss',
                                        'Α' => 'A',
                                        'Ά' => 'A',
                                        'Ἀ' => 'A',
                                        'Ἁ' => 'A',
                                        'Ἂ' => 'A',
                                        'Ἃ' => 'A',
                                        'Ἄ' => 'A',
                                        'Ἅ' => 'A',
                                        'Ἆ' => 'A',
                                        'Ἇ' => 'A',
                                        'ᾈ' => 'A',
                                        'ᾉ' => 'A',
                                        'ᾊ' => 'A',
                                        'ᾋ' => 'A',
                                        'ᾌ' => 'A',
                                        'ᾍ' => 'A',
                                        'ᾎ' => 'A',
                                        'ᾏ' => 'A',
                                        'Ᾰ' => 'A',
                                        'Ᾱ' => 'A',
                                        'Ὰ' => 'A',
                                        'Ά' => 'A',
                                        'ᾼ' => 'A',
                                        'Β' => 'B',
                                        'Γ' => 'G',
                                        'Δ' => 'D',
                                        'Ε' => 'E',
                                        'Έ' => 'E',
                                        'Ἐ' => 'E',
                                        'Ἑ' => 'E',
                                        'Ἒ' => 'E',
                                        'Ἓ' => 'E',
                                        'Ἔ' => 'E',
                                        'Ἕ' => 'E',
                                        'Έ' => 'E',
                                        'Ὲ' => 'E',
                                        'Ζ' => 'Z',
                                        'Η' => 'I',
                                        'Ή' => 'I',
                                        'Ἠ' => 'I',
                                        'Ἡ' => 'I',
                                        'Ἢ' => 'I',
                                        'Ἣ' => 'I',
                                        'Ἤ' => 'I',
                                        'Ἥ' => 'I',
                                        'Ἦ' => 'I',
                                        'Ἧ' => 'I',
                                        'ᾘ' => 'I',
                                        'ᾙ' => 'I',
                                        'ᾚ' => 'I',
                                        'ᾛ' => 'I',
                                        'ᾜ' => 'I',
                                        'ᾝ' => 'I',
                                        'ᾞ' => 'I',
                                        'ᾟ' => 'I',
                                        'Ὴ' => 'I',
                                        'Ή' => 'I',
                                        'ῌ' => 'I',
                                        'Θ' => 'TH',
                                        'Ι' => 'I',
                                        'Ί' => 'I',
                                        'Ϊ' => 'I',
                                        'Ἰ' => 'I',
                                        'Ἱ' => 'I',
                                        'Ἲ' => 'I',
                                        'Ἳ' => 'I',
                                        'Ἴ' => 'I',
                                        'Ἵ' => 'I',
                                        'Ἶ' => 'I',
                                        'Ἷ' => 'I',
                                        'Ῐ' => 'I',
                                        'Ῑ' => 'I',
                                        'Ὶ' => 'I',
                                        'Ί' => 'I',
                                        'Κ' => 'K',
                                        'Λ' => 'L',
                                        'Μ' => 'M',
                                        'Ν' => 'N',
                                        'Ξ' => 'KS',
                                        'Ο' => 'O',
                                        'Ό' => 'O',
                                        'Ὀ' => 'O',
                                        'Ὁ' => 'O',
                                        'Ὂ' => 'O',
                                        'Ὃ' => 'O',
                                        'Ὄ' => 'O',
                                        'Ὅ' => 'O',
                                        'Ὸ' => 'O',
                                        'Ό' => 'O',
                                        'Π' => 'P',
                                        'Ρ' => 'R',
                                        'Ῥ' => 'R',
                                        'Σ' => 'S',
                                        'Τ' => 'T',
                                        'Υ' => 'Y',
                                        'Ύ' => 'Y',
                                        'Ϋ' => 'Y',
                                        'Ὑ' => 'Y',
                                        'Ὓ' => 'Y',
                                        'Ὕ' => 'Y',
                                        'Ὗ' => 'Y',
                                        'Ῠ' => 'Y',
                                        'Ῡ' => 'Y',
                                        'Ὺ' => 'Y',
                                        'Ύ' => 'Y',
                                        'Φ' => 'F',
                                        'Χ' => 'X',
                                        'Ψ' => 'PS',
                                        'Ω' => 'O',
                                        'Ώ' => 'O',
                                        'Ὠ' => 'O',
                                        'Ὡ' => 'O',
                                        'Ὢ' => 'O',
                                        'Ὣ' => 'O',
                                        'Ὤ' => 'O',
                                        'Ὥ' => 'O',
                                        'Ὦ' => 'O',
                                        'Ὧ' => 'O',
                                        'ᾨ' => 'O',
                                        'ᾩ' => 'O',
                                        'ᾪ' => 'O',
                                        'ᾫ' => 'O',
                                        'ᾬ' => 'O',
                                        'ᾭ' => 'O',
                                        'ᾮ' => 'O',
                                        'ᾯ' => 'O',
                                        'Ὼ' => 'O',
                                        'Ώ' => 'O',
                                        'ῼ' => 'O',
                                        'α' => 'a',
                                        'ά' => 'a',
                                        'ἀ' => 'a',
                                        'ἁ' => 'a',
                                        'ἂ' => 'a',
                                        'ἃ' => 'a',
                                        'ἄ' => 'a',
                                        'ἅ' => 'a',
                                        'ἆ' => 'a',
                                        'ἇ' => 'a',
                                        'ᾀ' => 'a',
                                        'ᾁ' => 'a',
                                        'ᾂ' => 'a',
                                        'ᾃ' => 'a',
                                        'ᾄ' => 'a',
                                        'ᾅ' => 'a',
                                        'ᾆ' => 'a',
                                        'ᾇ' => 'a',
                                        'ὰ' => 'a',
                                        'ά' => 'a',
                                        'ᾰ' => 'a',
                                        'ᾱ' => 'a',
                                        'ᾲ' => 'a',
                                        'ᾳ' => 'a',
                                        'ᾴ' => 'a',
                                        'ᾶ' => 'a',
                                        'ᾷ' => 'a',
                                        'β' => 'b',
                                        'γ' => 'g',
                                        'δ' => 'd',
                                        'ε' => 'e',
                                        'έ' => 'e',
                                        'ἐ' => 'e',
                                        'ἑ' => 'e',
                                        'ἒ' => 'e',
                                        'ἓ' => 'e',
                                        'ἔ' => 'e',
                                        'ἕ' => 'e',
                                        'ὲ' => 'e',
                                        'έ' => 'e',
                                        'ζ' => 'z',
                                        'η' => 'i',
                                        'ή' => 'i',
                                        'ἠ' => 'i',
                                        'ἡ' => 'i',
                                        'ἢ' => 'i',
                                        'ἣ' => 'i',
                                        'ἤ' => 'i',
                                        'ἥ' => 'i',
                                        'ἦ' => 'i',
                                        'ἧ' => 'i',
                                        'ᾐ' => 'i',
                                        'ᾑ' => 'i',
                                        'ᾒ' => 'i',
                                        'ᾓ' => 'i',
                                        'ᾔ' => 'i',
                                        'ᾕ' => 'i',
                                        'ᾖ' => 'i',
                                        'ᾗ' => 'i',
                                        'ὴ' => 'i',
                                        'ή' => 'i',
                                        'ῂ' => 'i',
                                        'ῃ' => 'i',
                                        'ῄ' => 'i',
                                        'ῆ' => 'i',
                                        'ῇ' => 'i',
                                        'θ' => 'th',
                                        'ι' => 'i',
                                        'ί' => 'i',
                                        'ϊ' => 'i',
                                        'ΐ' => 'i',
                                        'ἰ' => 'i',
                                        'ἱ' => 'i',
                                        'ἲ' => 'i',
                                        'ἳ' => 'i',
                                        'ἴ' => 'i',
                                        'ἵ' => 'i',
                                        'ἶ' => 'i',
                                        'ἷ' => 'i',
                                        'ὶ' => 'i',
                                        'ί' => 'i',
                                        'ῐ' => 'i',
                                        'ῑ' => 'i',
                                        'ῒ' => 'i',
                                        'ΐ' => 'i',
                                        'ῖ' => 'i',
                                        'ῗ' => 'i',
                                        'κ' => 'k',
                                        'λ' => 'l',
                                        'μ' => 'm',
                                        'ν' => 'n',
                                        'ξ' => 'ks',
                                        'ο' => 'o',
                                        'ό' => 'o',
                                        'ὀ' => 'o',
                                        'ὁ' => 'o',
                                        'ὂ' => 'o',
                                        'ὃ' => 'o',
                                        'ὄ' => 'o',
                                        'ὅ' => 'o',
                                        'ὸ' => 'o',
                                        'ό' => 'o',
                                        'π' => 'p',
                                        'ρ' => 'r',
                                        'ῤ' => 'r',
                                        'ῥ' => 'r',
                                        'σ' => 's',
                                        'ς' => 's',
                                        'τ' => 't',
                                        'υ' => 'y',
                                        'ύ' => 'y',
                                        'ϋ' => 'y',
                                        'ΰ' => 'y',
                                        'ὐ' => 'y',
                                        'ὑ' => 'y',
                                        'ὒ' => 'y',
                                        'ὓ' => 'y',
                                        'ὔ' => 'y',
                                        'ὕ' => 'y',
                                        'ὖ' => 'y',
                                        'ὗ' => 'y',
                                        'ὺ' => 'y',
                                        'ύ' => 'y',
                                        'ῠ' => 'y',
                                        'ῡ' => 'y',
                                        'ῢ' => 'y',
                                        'ΰ' => 'y',
                                        'ῦ' => 'y',
                                        'ῧ' => 'y',
                                        'φ' => 'f',
                                        'χ' => 'x',
                                        'ψ' => 'ps',
                                        'ω' => 'o',
                                        'ώ' => 'o',
                                        'ὠ' => 'o',
                                        'ὡ' => 'o',
                                        'ὢ' => 'o',
                                        'ὣ' => 'o',
                                        'ὤ' => 'o',
                                        'ὥ' => 'o',
                                        'ὦ' => 'o',
                                        'ὧ' => 'o',
                                        'ᾠ' => 'o',
                                        'ᾡ' => 'o',
                                        'ᾢ' => 'o',
                                        'ᾣ' => 'o',
                                        'ᾤ' => 'o',
                                        'ᾥ' => 'o',
                                        'ᾦ' => 'o',
                                        'ᾧ' => 'o',
                                        'ὼ' => 'o',
                                        'ώ' => 'o',
                                        'ῲ' => 'o',
                                        'ῳ' => 'o',
                                        'ῴ' => 'o',
                                        'ῶ' => 'o',
                                        'ῷ' => 'o',
                                        '¨' => '',
                                        '΅' => '',
                                        '᾿' => '',
                                        '῾' => '',
                                        '῍' => '',
                                        '῝' => '',
                                        '῎' => '',
                                        '῞' => '',
                                        '῏' => '',
                                        '῟' => '',
                                        '῀' => '',
                                        '῁' => '',
                                        '΄' => '',
                                        '΅' => '',
                                        '`' => '',
                                        '῭' => '',
                                        'ͺ' => '',
                                        '᾽' => '',
                                        'А' => 'A',
                                        'Б' => 'B',
                                        'В' => 'V',
                                        'Г' => 'G',
                                        'Д' => 'D',
                                        'Е' => 'E',
                                        'Ё' => 'E',
                                        'Ж' => 'ZH',
                                        'З' => 'Z',
                                        'И' => 'I',
                                        'Й' => 'I',
                                        'К' => 'K',
                                        'Л' => 'L',
                                        'М' => 'M',
                                        'Н' => 'N',
                                        'О' => 'O',
                                        'П' => 'P',
                                        'Р' => 'R',
                                        'С' => 'S',
                                        'Т' => 'T',
                                        'У' => 'U',
                                        'Ф' => 'F',
                                        'Х' => 'KH',
                                        'Ц' => 'TS',
                                        'Ч' => 'CH',
                                        'Ш' => 'SH',
                                        'Щ' => 'SHCH',
                                        'Ы' => 'Y',
                                        'Э' => 'E',
                                        'Ю' => 'YU',
                                        'Я' => 'YA',
                                        'а' => 'A',
                                        'б' => 'B',
                                        'в' => 'V',
                                        'г' => 'G',
                                        'д' => 'D',
                                        'е' => 'E',
                                        'ё' => 'E',
                                        'ж' => 'ZH',
                                        'з' => 'Z',
                                        'и' => 'I',
                                        'й' => 'I',
                                        'к' => 'K',
                                        'л' => 'L',
                                        'м' => 'M',
                                        'н' => 'N',
                                        'о' => 'O',
                                        'п' => 'P',
                                        'р' => 'R',
                                        'с' => 'S',
                                        'т' => 'T',
                                        'у' => 'U',
                                        'ф' => 'F',
                                        'х' => 'KH',
                                        'ц' => 'TS',
                                        'ч' => 'CH',
                                        'ш' => 'SH',
                                        'щ' => 'SHCH',
                                        'ы' => 'Y',
                                        'э' => 'E',
                                        'ю' => 'YU',
                                        'я' => 'YA',
                                        'Ъ' => '',
                                        'ъ' => '',
                                        'Ь' => '',
                                        'ь' => '',

                                        'Є' => 'YE',
                                        'є' => 'YE',
                                        'Ї' => 'YI',
                                        'ї' => 'YI',
                                        'Ґ' => 'KG',
                                        'ґ' => 'KG',

                                        'ð' => 'd',
                                        'Ð' => 'D',
                                        'þ' => 'th',
                                        'Þ' => 'TH',

                                        '&'    =>    '-',
                                        '/'    =>    '-',
                                        '('    =>    '-',
                                        '"'    =>    '-',
                                        "'"    =>    '-',
    ];

    //
    // Generate a string with numbered links (for multipage scripts)
    //
    public static function paginate($num_pages, $cur_page, $link, $args = null)
    {
        $pages = [];
        $link_to_all = false;

        // If $cur_page == -1, we link to all pages (used in Forum.php)
        if ($cur_page == -1) {
            $cur_page = 1;
            $link_to_all = true;
        }

        if ($num_pages <= 1) {
            $pages = ['<strong class="item1">1</strong>'];
        } else {
            // Add a previous page link
            if ($num_pages > 1 && $cur_page > 1) {
                $pages[] = '<a rel="prev"'.(empty($pages) ? ' class="item1"' : '').' href="'.self::get_sublink($link, 'page/$1', ($cur_page - 1), $args).'">'.__('Previous').'</a>';
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
                    $pages[] = '<a'.(empty($pages) ? ' class="item1"' : '').' href="'.str_replace('#', '', self::get_sublink($link, 'page/$1', $current, $args)).'">'.Utils::forum_number_format($current).'</a>';
                } else {
                    $pages[] = '<strong'.(empty($pages) ? ' class="item1"' : '').'>'.Utils::forum_number_format($current).'</strong>';
                }
            }

            if ($cur_page <= ($num_pages-3)) {
                if ($cur_page != ($num_pages-3) && $cur_page != ($num_pages-4)) {
                    $pages[] = '<span class="spacer">'.__('Spacer').'</span>';
                }

                $pages[] = '<a'.(empty($pages) ? ' class="item1"' : '').' href="'.self::get_sublink($link, 'page/$1', $num_pages, $args).'">'.Utils::forum_number_format($num_pages).'</a>';
            }

            // Add a next page link
            if ($num_pages > 1 && !$link_to_all && $cur_page < $num_pages) {
                $pages[] = '<a rel="next"'.(empty($pages) ? ' class="item1"' : '').' href="'.self::get_sublink($link, 'page/$1', ($cur_page + 1), $args).'">'.__('Next').'</a>';
            }
        }

        return implode(' ', $pages);
    }

    //
    // Generate a string with numbered links (for multipage scripts)
    // Old FluxBB-style function for search page
    //
    public static function paginate_old($num_pages, $cur_page, $link)
    {
        $pages = [];
        $link_to_all = false;

        // If $cur_page == -1, we link to all pages (used in Forum.php)
        if ($cur_page == -1) {
            $cur_page = 1;
            $link_to_all = true;
        }

        if ($num_pages <= 1) {
            $pages = ['<strong class="item1">1</strong>'];
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
                    $pages[] = '<a'.(empty($pages) ? ' class="item1"' : '').' href="'.$link.($current == 1 ? '' : '&amp;p='.$current).'">'.Utils::forum_number_format($current).'</a>';
                } else {
                    $pages[] = '<strong'.(empty($pages) ? ' class="item1"' : '').'>'.Utils::forum_number_format($current).'</strong>';
                }
            }

            if ($cur_page <= ($num_pages-3)) {
                if ($cur_page != ($num_pages-3) && $cur_page != ($num_pages-4)) {
                    $pages[] = '<span class="spacer">'.__('Spacer').'</span>';
                }

                $pages[] = '<a'.(empty($pages) ? ' class="item1"' : '').' href="'.$link.'&amp;p='.$num_pages.'">'.Utils::forum_number_format($num_pages).'</a>';
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
    public static function url_friendly($str)
    {
        $str = strtr($str, self::$url_replace);
        $str = strtolower(utf8_decode($str));
        $str = Utils::trim(preg_replace(['/[^a-z0-9\s]/', '/[\s]+/'], ['', '-'], $str), '-');

        if (empty($str)) {
            $str = 'view';
        }

        return $str;
    }

    //
    // Generate link to another page on the forum
    // Inspired by (c) Panther <http://www.pantherforum.org/>
    //
    public static function get($link, $args = null)
    {
        $base_url = self::base();

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
    private static function get_sublink($link, $sublink, $subarg, $args = null)
    {
        $base_url = self::base();

        if ($sublink == 'p$1' && $subarg == 1) {
            return self::get($link, $args);
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
    // Fetch the base_url, optionally support HTTPS and HTTP
    //
    public static function base()
    {
        return Request::getUri()->getScheme().'://'.Request::getUri()->getHost().Request::getUri()->getBasePath();
    }

    //
    // Fetch the base_url for static files, optionally support HTTPS and HTTP
    //
    public static function base_static()
    {
        return Request::getUri()->getScheme().'://'.Request::getUri()->getHost();
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
    //      [scheme] => http
    //      [authority] => www.jmrware.com:80
    //      [userinfo] =>
    //      [host] => www.jmrware.com
    //      [IP_literal] =>
    //      [IPV6address] =>
    //      [ls32] =>
    //      [IPvFuture] =>
    //      [IPv4address] =>
    //      [regname] => www.jmrware.com
    //      [port] => 80
    //      [path_abempty] => /articles
    //      [query] => height=10&width=75
    //      [fragment] => fragone
    //      [url] => http://www.jmrware.com:80/articles?height=10&width=75#fragone
    // )
    public static function is_valid($url)
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
                  (?:                                                 (?:[0-9A-Fa-f]{1,4}:){6}
                  |                                                   ::(?:[0-9A-Fa-f]{1,4}:){5}
                  | (?:                             [0-9A-Fa-f]{1,4})?::(?:[0-9A-Fa-f]{1,4}:){4}
                  | (?:(?:[0-9A-Fa-f]{1,4}:){0,1}[0-9A-Fa-f]{1,4})?::(?:[0-9A-Fa-f]{1,4}:){3}
                  | (?:(?:[0-9A-Fa-f]{1,4}:){0,2}[0-9A-Fa-f]{1,4})?::(?:[0-9A-Fa-f]{1,4}:){2}
                  | (?:(?:[0-9A-Fa-f]{1,4}:){0,3}[0-9A-Fa-f]{1,4})?::    [0-9A-Fa-f]{1,4}:
                  | (?:(?:[0-9A-Fa-f]{1,4}:){0,4}[0-9A-Fa-f]{1,4})?::
                  )
                  (?P<ls32>[0-9A-Fa-f]{1,4}:[0-9A-Fa-f]{1,4}
                  | (?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}
                       (?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)
                  )
                |    (?:(?:[0-9A-Fa-f]{1,4}:){0,5}[0-9A-Fa-f]{1,4})?::    [0-9A-Fa-f]{1,4}
                |    (?:(?:[0-9A-Fa-f]{1,4}:){0,6}[0-9A-Fa-f]{1,4})?::
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
        (?:\?(?P<query>          (?:[A-Za-z0-9\-._~!$&\'()*+,;=:@\\/?]|%[0-9A-Fa-f]{2})*))?
        (?:\#(?P<fragment>      (?:[A-Za-z0-9\-._~!$&\'()*+,;=:@\\/?]|%[0-9A-Fa-f]{2})*))?
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
            ^                       # Anchor to beginning of string.
            (?!.{256})               # Overall host length is less than 256 chars.
            (?:                       # Group dot separated host part alternatives.
              [0-9A-Za-z]\.           # Either a single alphanum followed by dot
            |                       # or... part has more than one char (63 chars max).
              [0-9A-Za-z]           # Part first char is alphanum (no dash).
              [\-0-9A-Za-z]{0,61}  # Internal chars are alphanum plus dash.
              [0-9A-Za-z]           # Part last char is alphanum (no dash).
              \.                   # Each part followed by literal dot.
            )*                       # One or more parts before top level domain.
            (?:                       # Top level domains
              [A-Za-z]{2,63}|       # Country codes are exactly two alpha chars.
              xn--[0-9A-Za-z]{4,59})           # Internationalized Domain Name (IDN)
            $                       # Anchor to end of string.
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

}
