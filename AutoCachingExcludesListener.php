<?php

namespace Statamic\Addons\AutoCachingExcludes;

use Statamic\API\File;
use Statamic\API\Cache;
use Statamic\API\Collection;
use Statamic\API\Stache;
use Statamic\API\YAML;
use Statamic\Extend\Listener;

class AutoCachingExcludesListener extends Listener
{
    /**
     * The events to be listened for, and the methods to call.
     *
     * @var array
     */
    public $events = [
        'cp.page.published' => 'checkStaticCachingExclude',
    ];

    private $entry;
    private $caching_config;

     /**
     * Update caching.yaml so the page get's excluded from static caching if it contains a contact form.
     */
    public function checkStaticCachingExclude($entry) 
    {
        $this->entry = $entry;

        $content_field = $this->entry->get($this->getConfig('content_field'));
        $forms = $this->getConfig('forms');
        $form_included = false;
        $url = $this->entry->url();
        $locales = $this->entry->locales();

        $this->loadCachingConfig();

        if($content_field) {
            foreach ($content_field as $content)
            {
                if(in_array($content['type'], $forms)) {
                    $form_included = true;
                } 
            }
            if($form_included) {
                foreach($locales as $locale) {
                    $this->addToCachingConfig($this->entry->in($locale)->uri());
                }
            } else {
                foreach($locales as $locale) {
                    $this->removeFromCachingConfig($this->entry->in($locale)->uri());
                }
            }
        } else {
            foreach($locales as $locale) {
                $this->removeFromCachingConfig($this->entry->in($locale)->uri());
            }
        }
        
        $this->saveCachingConfig();
    }

    private function addToCachingConfig($url) {
        $static_caching_exclude = $this->caching_config['static_caching_exclude'];
        if (in_array($url, $static_caching_exclude) == false) {
            $static_caching_exclude[] = $url;
        } 
        $this->caching_config['static_caching_exclude'] = $static_caching_exclude;
    }
    
    private function removeFromCachingConfig($url) {
        $static_caching_exclude = $this->caching_config['static_caching_exclude'];
        if (in_array($url, $static_caching_exclude) == true) {
            unset($static_caching_exclude[array_search($url, $static_caching_exclude)]);
        }
        $this->caching_config['static_caching_exclude'] = $static_caching_exclude;
    }

    private function loadCachingConfig() {
        $caching_config_file = settings_path('caching.yaml');
        $caching_config = YAML::parse(File::get($caching_config_file));

        $this->caching_config = $caching_config;
    }

    private function saveCachingConfig() {
        $caching_config_file = settings_path('caching.yaml');
        $caching_config = YAML::dump($this->caching_config);
        File::put($caching_config_file, $caching_config);
    }
}
