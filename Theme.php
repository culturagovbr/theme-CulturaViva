<?php

namespace CulturaViva;

use MapasCulturais\App;

#die;

class Theme extends \MapasCulturais\Themes\BaseV2\Theme
{

    static function getThemeFolder()
    {
        return __DIR__;
    }

    function _init()
    {
        parent::_init();

        $app = App::i();

    }
}
