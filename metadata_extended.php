<?php namespace Grav\Plugin;

use Grav\Common\Plugin;
use RocketTheme\Toolbox\Event\Event;

class Metadata_ExtendedPlugin extends Plugin
{
    public static function getSubscribedEvents()
    {
        return [
            'onPluginsInitialized' => ['onPluginsInitialized', 0]
        ];
    }

    public function onPluginsInitialized()
    {
        if ($this->isAdmin()) {
            $this->active = false;
            return;
        }

        $this->enable([
            'onPageProcessed' => ['onPageProcessed', 0]
        ]);
    }

    public function onPageProcessed(Event $event)
    {
        $twig_local = new \Twig_Environment(new \Twig_Loader_String());

        $twig_vars = $this->grav['twig']->twig_vars;

        $page = $event['page'];
        $page_metadata = $page->metadata();

        // Add page object to twig vars.
        $twig_vars['page'] = $page;

        $fields = $this->grav['config']->get('plugins.metadata_extended.fields');

        foreach ($fields as $key => $value) {
            // If this is a property type metadata: "og", "twitter", "facebook" etc
            if (is_array($value)) {
                foreach ($value as $property => $prop_value) {
                    // Render the value with Twig.
                    $prop_value = $twig_local->render($prop_value, $twig_vars);

                    $prop_key = "{$key}:{$property}";
                    $page_metadata[$prop_key] = ['property' => $prop_key, 'content' => htmlspecialchars($prop_value, ENT_QUOTES)];
                }
            // If it this is a standard meta data type
            } else {
                // Render the value with Twig.
                $value = $twig_local->render($value, $twig_vars);

                $header_tag_http_equivs = ['content-type', 'default-style', 'refresh'];

                if (in_array($key, $header_tag_http_equivs)) {
                    $page_metadata[$key] = ['http_equiv' => $key, 'content' => htmlspecialchars($value, ENT_QUOTES)];
                } else {
                    $page_metadata[$key] = ['name' => $key, 'content' => htmlspecialchars($value, ENT_QUOTES)];
                }
            }
        }

        $page->metadata($page_metadata);

        $event->page = $page;
    }
}
