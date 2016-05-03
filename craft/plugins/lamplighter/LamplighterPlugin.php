<?php

namespace Craft;

class LamplighterPlugin extends BasePlugin
{

	/**
	 * This method defines the setting variables used by the Lamplighter plugin.
	 * @return array
	 */
    function defineSettings()
    {
        return array(
            'apiKey' => array(AttributeType::String, 'required' => false),
        );
    }


    /**
     * This method returns the view for configuring plugin settings.
     * @return mixed
     */
    function getSettingsHtml()
    {
        return craft()->templates->render('lamplighter/_settings', array(
            'settings' => $this->getSettings()
        ));
    }


    /**
     * This method registers/unregisters Lamplighter site tokens with the plugin.
     * @param array $settings
     * @return array
     */
    function prepSettings($settings)
    {
        $currentSettings = $this->getSettings();
        $apiKey = $settings['apiKey'];
        if (!$apiKey) {
            craft()->lamplighter_api->unregisterKey($currentSettings);
        } else {
        	$response = craft()->lamplighter_api->registerKey($apiKey, $currentSettings);
            if ($response['status'] == 'success') {
                craft()->lamplighter_api->sendInfo($settings);
                $settings['apiKey'] = $apiKey;
            } else {
            	craft()->userSession->setError('Error: '.$response['message']);
                $settings['apiKey'] = $currentSettings['apiKey'];
            }
        }
        return $settings;
    }


    /**
     * This method fetches the name of the plugin as it should appear on the site.
     * @return string
     */
    function getName()
    {
        return Craft::t('Lamplighter');
    }


    /**
     * This method returns the current version of the plugin.
	 * @return string
     */
    function getVersion()
    {
        return '1.1.2';
    }


    /**
     * This method returns the name of the plugin developer.
     * @return string
     */
    function getDeveloper()
    {
        return 'Lamplighter';
    }


    /**
	 * This method returns the URL of the developer's site.
	 * @return string
	 */
    function getDeveloperUrl()
    {
        return 'http://lamplighter.io';
    }

}
