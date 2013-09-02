<?php

    namespace IdnoPlugins\Pingback {

        class Main extends \Idno\Common\Plugin {
            
            function registerPages() {
                // Extend header
                \Idno\Core\site()->template()->extendTemplate('shell/head','pingback/shell/head');
                header('X-Pingback: ' . \Idno\Core\site()->config()->url . 'pingback/');
                
                // Register endpoint
                \Idno\Core\site()->addPageHandler('/pingback/?','\IdnoPlugins\Pingback\Endpoint');
                
                
            }
        }

    }