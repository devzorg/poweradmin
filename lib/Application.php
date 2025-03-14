<?php

/*  Poweradmin, a friendly web-based admin tool for PowerDNS.
 *  See <https://www.poweradmin.org> for more details.
 *
 *  Copyright 2007-2010 Rejo Zenger <rejo@zenger.nl>
 *  Copyright 2010-2023 Poweradmin Development Team
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

namespace Poweradmin;

use Symfony\Bridge\Twig\Extension\TranslationExtension;
use Symfony\Component\Translation\Loader\PoFileLoader;
use Symfony\Component\Translation\Translator;
use Twig\Environment;
use Twig\Error\Error;
use Twig\Loader\FilesystemLoader;

class Application {

    protected $templateRenderer;
    protected $configuration;

    public function __construct() {
        $loader = new FilesystemLoader('templates');
        $this->templateRenderer = new Environment($loader, [ 'debug' => false ]);

        global $iface_lang;
        $translator = new Translator($iface_lang);
        $translator->addLoader('po', new PoFileLoader());
        $translator->addResource('po', $this->getLocaleFile($iface_lang), $iface_lang);

        $this->templateRenderer->addExtension(new TranslationExtension($translator));
        $this->configuration = new Configuration();
    }

    public function render($template, $params = []) {
        try {
            echo $this->templateRenderer->render($template, $params);
        } catch (Error $e) {
            die($e->getMessage());
        }
    }

    public function config($name) {
        $raw_value = $this->configuration->get($name);
        return $raw_value ? str_replace(['"', "'"], "", $raw_value) : null;
    }

    public function getLocaleFile(string $iface_lang): string
    {
        if (in_array($iface_lang, ['cs_CZ', 'de_DE', 'fr_FR', 'ja_JP', 'nb_NO', 'nl_NL', 'pl_PL', 'ru_RU', 'tr_TR', 'zh_CN'])) {
            $short_locale = substr($iface_lang, 0, 2);
            return "locale/$iface_lang/LC_MESSAGES/$short_locale.po";
        }
        return "locale/en_EN/LC_MESSAGES/en.po";
    }
}