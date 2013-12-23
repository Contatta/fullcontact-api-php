<?php
/*
Copyright 2012 FullContact, Inc.
Licensed under the Apache License, Version 2.0 (the "License");
you may not use this file except in compliance with the License.
You may obtain a copy of the License at

http://www.apache.org/licenses/LICENSE-2.0

Unless required by applicable law or agreed to in writing, software
distributed under the License is distributed on an "AS IS" BASIS,
WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
See the License for the specific language governing permissions and
limitations under the License.
 */
namespace FullContact;
/**
 * Class FullContactAPI
 * @package FullContact
 */
class FullContactAPI {
    const FC_BASE_URL = "https://api.fullcontact.com/";
    const FC_API_VERSION = "v2";
    const FC_USER_AGENT = "FullContact/PHP 0.2";

    /**
     * @var string
     */
    private $_apiKey;

    /**
     * Supported lookup methods
     * @var array
     */
    private $_supportedMethods = array('email', 'phone', 'twitter', 'facebookUsername');

    /**
     * Construct API
     * @param string $api_key
     */
    public function __construct($api_key) {
        $this->_apiKey = $api_key;
    }

    /*
    * Return an array of data about a specific email address/phone number -- Mario Falomir http://github.com/mariofalomir
    *
    * @param String - Search Term (Could be an email address or a phone number, depending on the specified search type)
    * @param String - Search Type (Specify the API search method to use. E.g. email -- tested with email and phone)
    * @param String (optional) - timeout
    *
    * @return Array - All information associated with this email address
    */
    public function doLookup($term = null, $type="email") {
        if(!in_array($type, $this->_supportedMethods)){
            throw new FullContactAPIException("UnsupportedLookupMethodException: Invalid lookup method specified [{$type}]");
        }

        $return_value = null;

        if ($term != null) {

            $result = $this->restHelper(self::FC_BASE_URL . self::FC_API_VERSION 
            . "/person.json?{$type}=" . urlencode($term) . "&apiKey=" . urlencode($this->_apiKey));

            if ($result != null) {
                $return_value = $result;
            }//end inner if
        }//end outer if

        return $return_value;
    }

    /****************************************************************************/
    /****************************************************************************/

    /**
     * @param $json_endpoint
     * @return bool|mixed|string
     */
    private function request($json_endpoint) {
        $curl_handle=curl_init();
        curl_setopt($curl_handle,CURLOPT_URL,$json_endpoint);
        curl_setopt($curl_handle,CURLOPT_CONNECTTIMEOUT,3);
        curl_setopt($curl_handle,CURLOPT_RETURNTRANSFER,1);
        $buffer = curl_exec($curl_handle);
        curl_close($curl_handle);
        $buffer = utf8_encode($buffer);
        if (empty($buffer))  {
            return false;
        } else {
            return ($buffer);
        }
    }

    /*********************************
     **** PRIVATE helper function ****
     *********************************/
    function restHelper($json_endpoint) {

        $return_value = null;

        $stream_contents = $this->request($json_endpoint);
        if ($stream_contents !== false) {

            if (strlen($stream_contents) > 0) {

                //We're receiving stream data back from the API, json decode it here.
                $result = json_decode($stream_contents, true);

                //if result is NULL we have some sort of error
                if ($result === null) {

                    $return_value = array();
                    $return_value['is_error'] = true;

                    //does the stream meta data give us something to go on?
                    if (isset($stream_meta_data['wrapper_data'][0])) {

                        $return_value['http_header_error_message'] = $stream_meta_data['wrapper_data'][0];
                        /*
                         * IN this case the response status (422 or 403) is in the stream_meta_data
                         * object.  We'll grab it and return it to the user.
                         *
                         * This occurs if:
                         *  -Invalid email address
                         *  -Invalid or over limit API key
                         */
                        if (strpos($stream_meta_data['wrapper_data'][0], "403") !== false) {
                            $return_value['error_message'] = "Your API key is invalid, missing, or has exceeded its quota.";

                        } else if (strpos($stream_meta_data['wrapper_data'][0], "422") !== false) {
                            $return_value['error_message'] = "The server understood the content type and syntax of the request but was unable to process the contained instructions (Invalid email).";
                        }
                    }//end if (isset)

                } else {

                    $result['is_error'] = false;
                    $return_value = $result;
                }
            }//end if (strlen)

            //The stream_contents failed.
        } else {
            throw new \Exception("$json_endpoint failed");
        }//end outer else

        return $return_value;
    }//end restHelper
}//end FullContactAPI
