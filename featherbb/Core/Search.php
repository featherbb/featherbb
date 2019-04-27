<?php

/**
 * Copyright (C) 2015-2019 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

// The contents of this file are very much inspired by the file functions_search.php
// from the phpBB Group forum software phpBB2 (http://www.phpbb.com)

namespace FeatherBB\Core;

use FeatherBB\Core\Database as DB;
use FeatherBB\Core\Interfaces\Cache;
use FeatherBB\Core\Interfaces\ForumEnv;
use FeatherBB\Core\Interfaces\ForumSettings;

class Search
{
    //
    // "Cleans up" a text string and returns an array of unique words
    // This function depends on the current locale setting
    //
    public static function splitWords($text, $idx)
    {
        // Remove BBCode
        $text = preg_replace('%\[/?(b|u|s|ins|del|em|i|h|colou?r|quote|code|img|url|email|list|topic|post|forum|user)(?:\=[^\]]*)?\]%', ' ', $text);

        // Remove any apostrophes or dashes which aren't part of words
        $text = substr(Utils::ucpPregReplace('%((?<=[^\p{L}\p{N}])[\'\-]|[\'\-](?=[^\p{L}\p{N}]))%u', '', ' ' . $text . ' '), 1, -1);

        // Remove punctuation and symbols (actually anything that isn't a letter or number), allow apostrophes and dashes (and % * if we aren't indexing)
        $text = Utils::ucpPregReplace('%(?![\'\-' . ($idx ? '' : '\%\*') . '])[^\p{L}\p{N}]+%u', ' ', $text);

        // Replace multiple whitespace or dashes
        $text = preg_replace('%(\s){2,}%u', '\1', $text);

        // Fill an array with all the words
        $words = array_unique(explode(' ', $text));

        // Remove any words that should not be indexed
        foreach ($words as $key => $value) {
            // If the word shouldn't be indexed, remove it
            if (!self::validateSearchWord($value, $idx)) {
                unset($words[$key]);
            }
        }

        return $words;
    }


    //
    // Checks if a word is a valid searchable word
    //
    public static function validateSearchWord($word, $idx)
    {
        static $stopwords;

        // If the word is a keyword we don't want to index it, but we do want to be allowed to search it
        if (self::isKeyword($word)) {
            return !$idx;
        }

        if (!isset($stopwords)) {
            if (!Cache::isCached('stopwords')) {
                Cache::store('stopwords', \FeatherBB\Model\Cache::getStopwords(), '+1 week');
            }
            $stopwords = Cache::retrieve('stopwords');
        }

        // If it is a stopword it isn't valid
        if (in_array($word, $stopwords)) {
            return false;
        }

        // If the word is CJK we don't want to index it, but we do want to be allowed to search it
        if (self::isCjk($word)) {
            return !$idx;
        }

        // Exclude % and * when checking whether current word is valid
        $word = str_replace(['%', '*'], '', $word);

        // Check the word is within the min/max length
        $numChars = Utils::strlen($word);
        return $numChars >= ForumEnv::get('FEATHER_SEARCH_MIN_WORD') && $numChars <= ForumEnv::get('FEATHER_SEARCH_MAX_WORD');
    }


    //
    // Check a given word is a search keyword.
    //
    public static function isKeyword($word)
    {
        return $word == 'and' || $word == 'or' || $word == 'not';
    }


    //
    // Check if a given word is CJK or Hangul.
    //
    public static function isCjk($word)
    {
        // Make a regex that will match CJK or Hangul characters
        return preg_match('%^' . '[' .
            '\x{1100}-\x{11FF}' .        // Hangul Jamo                            1100-11FF        (http://www.fileformat.info/info/unicode/block/hangul_jamo/index.htm)
            '\x{3130}-\x{318F}' .        // Hangul Compatibility Jamo            3130-318F        (http://www.fileformat.info/info/unicode/block/hangul_compatibility_jamo/index.htm)
            '\x{AC00}-\x{D7AF}' .        // Hangul Syllables                        AC00-D7AF        (http://www.fileformat.info/info/unicode/block/hangul_syllables/index.htm)

            // Hiragana
            '\x{3040}-\x{309F}' .        // Hiragana                                3040-309F        (http://www.fileformat.info/info/unicode/block/hiragana/index.htm)

            // Katakana
            '\x{30A0}-\x{30FF}' .        // Katakana                                30A0-30FF        (http://www.fileformat.info/info/unicode/block/katakana/index.htm)
            '\x{31F0}-\x{31FF}' .        // Katakana Phonetic Extensions            31F0-31FF        (http://www.fileformat.info/info/unicode/block/katakana_phonetic_extensions/index.htm)

            // CJK Unified Ideographs    (http://en.wikipedia.org/wiki/CJK_Unified_Ideographs)
            '\x{2E80}-\x{2EFF}' .        // CJK Radicals Supplement                2E80-2EFF        (http://www.fileformat.info/info/unicode/block/cjk_radicals_supplement/index.htm)
            '\x{2F00}-\x{2FDF}' .        // Kangxi Radicals                        2F00-2FDF        (http://www.fileformat.info/info/unicode/block/kangxi_radicals/index.htm)
            '\x{2FF0}-\x{2FFF}' .        // Ideographic Description Characters    2FF0-2FFF        (http://www.fileformat.info/info/unicode/block/ideographic_description_characters/index.htm)
            '\x{3000}-\x{303F}' .        // CJK Symbols and Punctuation            3000-303F        (http://www.fileformat.info/info/unicode/block/cjk_symbols_and_punctuation/index.htm)
            '\x{31C0}-\x{31EF}' .        // CJK Strokes                            31C0-31EF        (http://www.fileformat.info/info/unicode/block/cjk_strokes/index.htm)
            '\x{3200}-\x{32FF}' .        // Enclosed CJK Letters and Months        3200-32FF        (http://www.fileformat.info/info/unicode/block/enclosed_cjk_letters_and_months/index.htm)
            '\x{3400}-\x{4DBF}' .        // CJK Unified Ideographs Extension A    3400-4DBF        (http://www.fileformat.info/info/unicode/block/cjk_unified_ideographs_extension_a/index.htm)
            '\x{4E00}-\x{9FFF}' .        // CJK Unified Ideographs                4E00-9FFF        (http://www.fileformat.info/info/unicode/block/cjk_unified_ideographs/index.htm)
            '\x{20000}-\x{2A6DF}' .        // CJK Unified Ideographs Extension B    20000-2A6DF        (http://www.fileformat.info/info/unicode/block/cjk_unified_ideographs_extension_b/index.htm)
            ']' . '+$%u', $word) ? true : false;
    }


    //
    // Strip [img] [url] and [email] out of the message so we don't index their contents
    //
    public static function stripBbcode($text)
    {
        static $patterns;

        if (!isset($patterns)) {
            $patterns = [
                '%\[img=([^\]]*+)\]([^[]*+)\[/img\]%' => '$2 $1',    // Keep the url and description
                '%\[(url|email)=([^\]]*+)\]([^[]*+(?:(?!\[/\1\])\[[^[]*+)*)\[/\1\]%' => '$2 $3',    // Keep the url and text
                '%\[(img|url|email)\]([^[]*+(?:(?!\[/\1\])\[[^[]*+)*)\[/\1\]%' => '$2',        // Keep the url
                '%\[(topic|post|forum|user)\][1-9]\d*\[/\1\]%' => ' ',        // Do not index topic/post/forum/user ID
            ];
        }

        return preg_replace(array_keys($patterns), array_values($patterns), $text);
    }


    //
    // Updates the search index with the contents of $postId (and $subject)
    //
    public static function updateSearchIndex($mode, $postId, $message, $subject = null)
    {
        $message = \utf8\to_lower($message);
        $subject = \utf8\to_lower($subject);

        // Remove any bbcode that we shouldn't index
        $message = self::stripBbcode($message);

        // Split old and new post/subject to obtain array of 'words'
        $wordsMessage = self::splitWords($message, true);
        $wordsSubject = ($subject) ? self::splitWords($subject, true) : [];

        if ($mode == 'edit') {
            $selectUpdateSearchIndex = ['w.id', 'w.word', 'm.subject_match'];
            $result = DB::table('search_words')->tableAlias('w')
                ->selectMany($selectUpdateSearchIndex)
                ->innerJoin('search_matches', ['w.id', '=', 'm.word_id'], 'm')
                ->where('m.post_id', $postId)
                ->findMany();

            // Declare here to stop array_keys() and array_diff() from complaining if not set
            $curWords['post'] = [];
            $curWords['subject'] = [];

            foreach ($result as $row) {
                $matchIn = ($row['subject_match']) ? 'subject' : 'post';
                $curWords[$matchIn][$row['word']] = $row['id'];
            }

            $pdo = DB::getDb();
            $pdo = null;

            $words['add']['post'] = array_diff($wordsMessage, array_keys($curWords['post']));
            $words['add']['subject'] = array_diff($wordsSubject, array_keys($curWords['subject']));
            $words['del']['post'] = array_diff(array_keys($curWords['post']), $wordsMessage);
            $words['del']['subject'] = array_diff(array_keys($curWords['subject']), $wordsSubject);
        } else {
            $words['add']['post'] = $wordsMessage;
            $words['add']['subject'] = $wordsSubject;
            $words['del']['post'] = [];
            $words['del']['subject'] = [];
        }

        unset($wordsMessage);
        unset($wordsSubject);

        // Get unique words from the above arrays
        $uniqueWords = array_unique(array_merge($words['add']['post'], $words['add']['subject']));

        if (!empty($uniqueWords)) {
            $selectUniqueWords = ['id', 'word'];
            $result = DB::table('search_words')->selectMany($selectUniqueWords)
                ->whereIn('word', $uniqueWords)
                ->findMany();

            $wordIds = [];
            foreach ($result as $row) {
                $wordIds[$row['word']] = $row['id'];
            }

            $pdo = DB::getDb();
            $pdo = null;

            $newWords = array_values(array_diff($uniqueWords, array_keys($wordIds)));

            unset($uniqueWords);

            if (!empty($newWords)) {
                switch (ForumSettings::get('db_type')) {
                    case 'mysql':
                    case 'mysqli':
                    case 'mysql_innodb':
                    case 'mysqli_innodb':
                        // Quite dirty, right? :-)
                        $placeholders = rtrim(str_repeat('(?), ', count($newWords)), ', ');
                        DB::table('search_words')
                            ->rawExecute('INSERT INTO ' . ForumSettings::get('db_prefix') . 'search_words (word) VALUES ' . $placeholders, $newWords);
                        break;

                    default:
                        foreach ($newWords as $word) {
                            $wordInsert['word'] = $word;
                            DB::table('search_words')
                                ->create()
                                ->set($wordInsert)
                                ->save();
                        }
                        break;
                }
            }

            unset($newWords);
        }

        // Delete matches (only if editing a post)
        foreach ($words['del'] as $matchIn => $wordlist) {
            $subjectMatch = ($matchIn == 'subject') ? 1 : 0;

            if (!empty($wordlist)) {
                $sql = [];
                foreach ($wordlist as $word) {
                    $sql[] = $curWords[$matchIn][$word];
                }

                DB::table('search_matches')
                    ->whereIn('word_id', $sql)
                    ->where('post_id', $postId)
                    ->where('subject_match', $subjectMatch)
                    ->deleteMany();
            }
        }

        // Add new matches
        foreach ($words['add'] as $matchIn => $wordlist) {
            $subjectMatch = ($matchIn == 'subject') ? 1 : 0;

            if (!empty($wordlist)) {
                $wordlist = array_values($wordlist);
                $placeholders = rtrim(str_repeat('?, ', count($wordlist)), ', ');
                DB::table('search_words')
                    ->rawExecute('INSERT INTO ' . ForumSettings::get('db_prefix') . 'search_matches (post_id, word_id, subject_match) SELECT ' . $postId . ', id, ' . $subjectMatch . ' FROM ' . ForumSettings::get('db_prefix') . 'search_words WHERE word IN (' . $placeholders . ')', $wordlist);
            }
        }

        unset($words);
    }


    //
    // Strip search index of indexed words in $postIds
    //
    public function stripSearchIndex($postIds)
    {
        if (!is_array($postIds)) {
            $postIdsSql = explode(',', $postIds);
        } else {
            $postIdsSql = $postIds;
        }

        switch (ForumSettings::get('db_type')) {
            case 'mysql':
            case 'mysqli':
            case 'mysql_innodb':
            case 'mysqli_innodb': {
                $result = DB::table('search_matches')->select('word_id')
                    ->whereIn('post_id', $postIdsSql)
                    ->groupBy('word_id')
                    ->findMany();

                if ($result) {
                    $wordIds = [];
                    foreach ($result as $row) {
                        $wordIds[] = $row['word_id'];
                    }

                    $result = DB::table('search_matches')->select('word_id')
                        ->whereIn('word_id', $wordIds)
                        ->groupBy('word_id')
                        ->havingRaw('COUNT(word_id)=1')
                        ->findMany();

                    if ($result) {
                        $wordIds = [];
                        foreach ($result as $row) {
                            $wordIds[] = $row['word_id'];
                        }

                        DB::table('search_words')
                            ->whereIn('id', $wordIds)
                            ->deleteMany();
                    }
                }
                break;
            }

            default:
                DB::table('search_matches')
                    ->whereRaw('id IN(SELECT word_id FROM ' . ForumSettings::get('db_prefix') . 'search_matches WHERE word_id IN(SELECT word_id FROM ' . ForumSettings::get('db_prefix') . 'search_matches WHERE post_id IN(' . $postIds . ') GROUP BY word_id) GROUP BY word_id HAVING COUNT(word_id)=1)')
                    ->deleteMany();
                break;
        }

        DB::table('search_matches')
            ->whereIn('post_id', $postIdsSql)
            ->deleteMany();
    }
}
