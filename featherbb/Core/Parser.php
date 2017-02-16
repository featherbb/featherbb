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

    private $smilies;

    public function __construct()
    {
        $this->cacheDir = ForumEnv::get('FORUM_CACHE_DIR').'/parser';

        // Load smilies
        if (!Container::get('cache')->isCached('smilies')) {
            Container::get('smilies')->store('smilies', Cache::getSmilies());
        }
        $this->smilies = Container::get('cache')->retrieve('smilies');

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
        $xml  = $this->parser->parse($text);
        $html = $this->renderer->render($xml);

        if (!$hideSmilies) {
            $html = $this->doSmilies($html);
        }

        return $html;
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
        if (ForumSettings::get('p_message_bbcode') == '1') {
            if (ForumSettings::get('p_message_img_tag') !== '1' || User::getPref('show.img') !== '1') {
                $this->parser->disablePlugin('Autoimage');// enable after parsing?
                $this->parser->disablePlugin('Autovideo');
            }

            $xml  = $this->parser->parse($text);
            $html = $this->renderer->render($xml);
        }
        else {
            $html = Utils::escape($text);
        }

        if (User::getPref('show.smilies') == '1' && ForumSettings::get('o_smilies') == '1' && $hideSmilies == 0) {
            $html = $this->doSmilies($html);
        }

        return $html;
    }

    /**
     * Parse signature text
     *
     * @param string $text
     * @return string
     */
    public function parseSignature($text)
    {
        if (ForumSettings::get('p_sig_bbcode') == '1') {
            if (ForumSettings::get('p_sig_img_tag') !== '1' || User::getPref('show.img.sig') !== '1') {
                $this->parser->disablePlugin('Autoimage');// enable after parsing?
                $this->parser->disablePlugin('Autovideo');
            }

            $xml  = $this->parser->parse($text);
            $html = $this->renderer->render($xml);
        }
        else {
            $html = Utils::escape($text);
        }

        if (User::getPref('show.smilies') == '1' && ForumSettings::get('o_smilies') == '1') {
            $html = $this->doSmilies($html);
        }

        return $html;
    }

    public function parseForSave($text, &$errors)
    {
        $xml  = $this->parser->parse($text);

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
        $xml  = $this->parser->parse($text);
        return \s9e\TextFormatter\Unparser::unparse($xml);
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
