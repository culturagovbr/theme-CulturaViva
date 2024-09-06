<?php

namespace MapasAmfri;

use MapasCulturais\App;

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
