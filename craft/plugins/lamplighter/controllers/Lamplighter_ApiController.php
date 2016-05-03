<?php

namespace Craft;

class Lamplighter_ApiController extends BaseController
{
    protected $allowAnonymous = true;

    /**
     * This method handles requests from the Lamplighter app and returns a 
     * JSON response.
     * @return string
     */
    public function actionRequest()
    {
    	// Get our Lamplighter plugin
    	$lamplighter = craft()->plugins->getPlugin('lamplighter');

    	// Get our settings from the Lamplighter plugin
    	$settings    = $lamplighter->getSettings();

    	// Find out what Lamplighter is asking for.  Addons is default.
    	$requestType = craft()->request->getSegment(5) ?: 'addons';

    	// Route our request.
    	switch ($requestType) {
    		case 'addons':
				$return = craft()->lamplighter_api->sendInfo($settings) ? array('status' => 'success', 'message' => 'information sent') : array('status' => 'error');
    			break;
    		case 'backup':
    			$return = craft()->lamplighter_api->getBackup($settings) ? array('status' => 'success', 'message' => 'backup complete') : array('status' => 'error');
    			break;
    		default:
    			$return = array('status' => 'error', 'message' => 'Invalid Lamplighter endpoint.');
    			break;

    	}

    	// Return our status.
		header('Content-Type: application/json');
    	exit(json_encode($return));
    }

}
