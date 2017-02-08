<?php
/**
 * Copyright (C) 2015-2017 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace FeatherBB\Core;

class Parser
{
    private $parser;
    private $renderer;
    private $cacheDir;

    protected $smilies = array(
        ':)' => 'smile.png',
        '=)' => 'smile.png',
        ':|' => 'neutral.png',
        '=|' => 'neutral.png',
        ':(' => 'sad.png',
        '=(' => 'sad.png',
        ':D' => 'big_smile.png',
        '=D' => 'big_smile.png',
        ':o' => 'yikes.png',
        ':O' => 'yikes.png',
        ';)' => 'wink.png',
        ':/' => 'hmm.png',
        ':P' => 'tongue.png',
        ':p' => 'tongue.png',
        ':lol:' => 'lol.png',
        ':mad:' => 'mad.png',
        ':rolleyes:' => 'roll.png',
        ':cool:' => 'cool.png');

    public function __construct()
    {
        $this->cacheDir = ForumEnv::get('FORUM_CACHE_DIR').'/parser';

        if (Container::get('cache')->isCached('s9eparser') && Container::get('cache')->isCached('s9erenderer')) {
            $this->parser = unserialize(Container::get('cache')->retrieve('s9eparser'));
            $this->renderer = unserialize(Container::get('cache')->retrieve('s9erenderer'));
        } else {
            $this->configureParser();
        }
    }

    /**
     * TODO Build bundles depend forum config and user group rights
     */
    private function configureParser()
    {
        $renderer = $parser = null;
        $configurator = new \s9e\TextFormatter\Configurator;
        $configurator->plugins->load('Autoemail');//Fatdown & Forum default
        $configurator->plugins->load('Autolink');//Fatdown & Forum default
        $configurator->plugins->load('Escaper');//Fatdown default
        $configurator->plugins->load('FancyPants');//Fatdown default
        $configurator->plugins->load('HTMLComments');//Fatdown default
        $configurator->plugins->load('HTMLElements');//Fatdown default
        $configurator->plugins->load('HTMLEntities');//Fatdown default
        $configurator->plugins->load('Litedown');//Fatdown default
        $configurator->plugins->load('MediaEmbed');//Fatdown & Forum default
        $configurator->plugins->load('PipeTables');//Fatdown default
//        $configurator->plugins->load('BBCodes');//Forum default
//        $configurator->plugins->load('Emoji');//Forum default
//        $configurator->plugins->load('Emoticons');//Forum default

        $configurator->registeredVars['cacheDir'] = ForumEnv::get('FORUM_CACHE_DIR');

        // Get an instance of the parser and the renderer
        extract($configurator->finalize());

        // We save the parser and the renderer to the disk for easy reuse
        Container::get('cache')->store('s9eparser', serialize($parser));
        Container::get('cache')->store('s9erenderer', serialize($renderer));

        $this->parser = $parser;
        $this->renderer = $renderer;
    }

    /**
     * Parse post or signature message text.
     *
     * @param string &$text
     * @param integer $hideSmilies
     * @return string
     */
    public function parseBbcode(&$text, $hideSmilies = 0)
    {
        if ($hideSmilies) {
            $this->parser->disablePlugin('Emoticons');
        }

        $xml  = $this->parser->parse($text);
        $html = $this->renderer->render($xml);
        return $html;
        // FIXME
//        if (ForumSettings::get('o_censoring') === '1')
//        {
//            $text = Utils::censor($text);
//        }
//        // Convert [&<>] characters to HTML entities (but preserve [""''] quotes).
//        $text = htmlspecialchars($text, ENT_NOQUOTES);
//
//        // Parse BBCode if globally enabled.
//        if (ForumSettings::get('p_message_bbcode'))
//        {
//            $text = preg_replace_callback($this->pd['re_bbcode'], array($this, '_parse_bbcode_callback'), $text);
//        }
//        // Set $smileOn flag depending on global flags and whether or not this is a signature.
//        if ($this->pd['in_signature'])
//        {
//            $smileOn = (ForumSettings::get('o_smilies_sig') &&
// User::get()['show_smilies'] && !$hideSmilies) ? 1 : 0;
//        }
//        else
//        {
//            $smileOn = (ForumSettings::get('o_smilies') && User::get()['show_smilies'] && !$hideSmilies) ? 1 : 0;
//        }
    }

    /**
     * Parse message text
     *
     * @param string $text
     * @param integer $hideSmilies
     * @return string
     */
    public function parseMessage($text, $hideSmilies)
    {
        if ($hideSmilies) {
            $this->parser->disablePlugin('Emoticons');
        }
        if (ForumSettings::get('p_message_img_tag') !== '1' || User::get()['show_img'] !== '1')
        {
            $this->parser->disablePlugin('Autoimage');// enable after parsing?
            $this->parser->disablePlugin('Autovideo');
        }

        $xml  = $this->parser->parse($text);
        $html = $this->renderer->render($xml);

        if (User::getPref('show.smilies') == '1' && ForumSettings::get('o_smilies') == '1' && $hideSmilies == 0) {
            return $this->doSmilies($html);
        }

        return $this->doSmilies($html);
        // FIXME

//        $this->pd['in_signature'] = false;
//        // Disable images via the $bbcd['in_post'] flag if globally disabled.
//        if (ForumSettings::get('p_message_img_tag') !== '1' || User::get()['show_img'] !== '1')
//            if (isset($this->pd['bbcd']['img']))
//                $this->pd['bbcd']['img']['in_post'] = false;
//        return $this->parseBbcode($text, $hideSmilies);
    }

    /**
     * Parse signature text
     *
     * @param string $text
     * @return string
     */
    public function parseSignature($text)
    {
        // FIXME check length
        if (ForumSettings::get('p_sig_img_tag') !== '1' || User::get()['show_img_sig'] !== '1')
        {
            $this->parser->disablePlugin('Autoimage');// enable after parsing?
            $this->parser->disablePlugin('Autovideo');
        }

        $xml  = $this->parser->parse($text);
        $html = $this->renderer->render($xml);

        return $html;

//        $this->pd['in_signature'] = true;
//        // Disable images via the $bbcd['in_sig'] flag if globally disabled.
//        if (ForumSettings::get('p_sig_img_tag') !== '1' || User::get()['show_img_sig'] !== '1')
//            if (isset($this->pd['bbcd']['img']))
//                $this->pd['bbcd']['img']['in_sig'] = false;
//        return $this->parseBbcode($text);
    }

    public function parseForSave($text, &$errors)
    {
        $xml  = $this->parser->parse($text);
        $html = $this->renderer->render($xml);

        // TODO check nestingLimit
//        $parserErrors = $this->parser->getLogger()->get();
//        if (!empty($parserErrors)) {
//tdie($parserErrors);
//        }
        return \s9e\TextFormatter\Unparser::unparse($xml);
    }
    /**
     * Pre-process text containing BBCodes. Check for integrity,
     * well-formedness, nesting, etc. Flag errors by wrapping offending
     * tags in a special [err] tag.
     *
     * @param string $text
     * @param array &$errors
     * @param integer $isSignature
     * @return string
     */
    public function preparseBbcode($text, &$errors, $isSignature = false)
    {
        // FIXME some as parseForSave ???

        // TODO check $isSignature limits
        $xml  = $this->parser->parse($text);
        $html = $this->renderer->render($xml);
        // TODO check nestingLimit
//        $parserErrors = $this->parser->getLogger()->get();
//        if (!empty($parserErrors)) {
//tdie($parserErrors);
//        }
        return \s9e\TextFormatter\Unparser::unparse($xml);
        /*
                $this->pd['new_errors'] = []; // Reset the parser error message stack.
                $this->pd['in_signature'] = ($isSignature) ? true : false;
                $this->pd['ipass'] = 1;
                $newtext = preg_replace_callback($this->pd['re_bbcode'], array($this, '_preparse_bbcode_callback'), $text);
                if ($newtext === null)
                { // On error, preg_replace_callback returns NULL.
                    // Error #1: '(%s) Message is too long or too complex. Please shorten.'
                    $errors[] = sprintf(__('BBerr pcre'), $this->pregError());
                    return $text;
                }
                $newtext = str_replace("\3", '[', $newtext); // Fixup CODE sections.
                $parts = explode("\1", $newtext); // Hidden chunks pre-marked like so: "\1\2<code.../code>\1"
                for ($i = 0, $len = count($parts); $i < $len; ++$i)
                { // Loop through hidden and non-hidden text chunks.
                    $part = &$parts[$i]; // Use shortcut alias
                    if (empty($part))
                        continue; // Skip empty string chunks.
                    if ($part[0] !== "\2")
                    { // If not hidden, process this normal text content.
                        // Mark erroneous orphan tags.
                        $part = preg_replace_callback($this->pd['re_bbtag'], array($this, '_orphan_callback'), $part);
                        // Process do-clickeys if enabled.
                        if (ForumSettings::get('o_make_links'))
                            $part = $this->linkify($part);

                        // Process textile syntax tag shortcuts.
                        if ($this->pd['config']['textile'])
                        {
                            // Do phrase replacements.
                            $part = preg_replace_callback($this->pd['re_textile'],
        array($this, '_textile_phrase_callback'), $part);
                            // Do lists.
                            $part = preg_replace_callback('/^([*#]) .*+(?:\n\1 .*+)++$/Sm',
        array($this, '_textile_list_callback'), $part);
                        }
                        $part = preg_replace('/^[ \t]++$/m', '', $part); // Clear "white" lines of spaces and tabs.
                    }
                    else
                        $part = substr($part, 1); // For hidden chunks, strip \2 marker byte.
                }
                $text = implode("", $parts); // Put hidden and non-hidden chunks back together.
                $this->pd['ipass'] = 2; // Run a second pass through parser to clean changed content.
                $text = preg_replace_callback($this->pd['re_bbcode'], array($this, '_preparse_bbcode_callback'), $text);
                $text = str_replace("\3", '[', $text); // Fixup CODE sections.
                if (!empty($this->pd['new_errors']))
                {
                    foreach ($this->pd['new_errors'] as $errmsg)
                    {
                        $errors[] = $errmsg; // Push all new errors on global array.
                    }
                }
                return $text;
        */
    }

    /**
     * Display smilies
     * Credits: FluxBB
     * @param $text string containing smilies to parse
     * @return string text "smilied" :-)
     */
    function doSmilies($text)
    {
        $text = ' '.$text.' ';
        foreach ($this->smilies as $smileyText => $smileyImg)
        {
            if (strpos($text, $smileyText) !== false)
                $text = Utils::ucpPregReplace('%(?<=[>\s])'.preg_quote($smileyText, '%').'(?=[^\p{L}\p{N}])%um', '<img src="'.Utils::escape(URL::base().'/style/img/smilies/'.$smileyImg).'" alt="'.substr($smileyImg, 0, strrpos($smileyImg, '.')).'" />', $text);
        }
        return substr($text, 1, -1);
    }
}
