<?php

namespace IFW\Web\Encoder;

use DOMDocument;
use Exception;
use IFW\Web\Request;
use IFW\Web\Response;

class XmlEncoder implements EncoderInterface {

	/**
	 * Encodes array to XML. 
	 *
	 * Response is put in a "message" root element.
	 * 
	 * eg:
	 * 
	 * <code>
	 * 
	 * <?xml version="1.0"?>
	  <message>
	  <success type="boolean">true</success>
	  <user>
	  <id type="integer">1</id>
	  <deleted type="boolean">false</deleted>
	  <enabled type="boolean">true</enabled>
	  <username type="string">admin</username>
	  <password type="string"></password>
	  <digest type="string"></digest>
	  <createdAt type="string">2014-07-21T14:01:17Z</createdAt>
	  <modifiedAt type="string">2015-08-31T13:32:19Z</modifiedAt>
	  <loginCount type="integer">131</loginCount>
	  <lastLogin type="string">2015-08-31T15:32:19Z</lastLogin>
	  <isAdmin type="boolean">true</isAdmin>
	  <permissions>
	  <create type="boolean">true</create>
	  <read type="boolean">true</read>
	  <update type="boolean">true</update>
	  <delete type="boolean">true</delete>
	  <changePermissions type="boolean">true</changePermissions>
	  </permissions>
	  <validationErrors/>
	  <className type="string">GO\Core\Modules\Users\Model\User</className>
	  <currentPassword type="NULL"></currentPassword>
	  <markDeleted type="boolean">false</markDeleted>
	  </user>
	  <XSRFToken type="string">e4ec7d42d8b2152a404d5845cc19d89e6e72b800</XSRFToken>
	  </message>


	 * </code>
	 * @param Response $responseData
	 * @return type
	 * @throws Exception
	 */
	public function encode($responseData) {
		$json = $this->_xmlEncode(['message' => $responseData]);

		if (empty($json)) {
			throw new Exception('XML encoding error: ' . var_export($responseData, true));
		}

		return $json;
	}

	private function _xmlEncode($mixed, $domElement = null, $DOMDocument = null) {

		if (is_null($DOMDocument)) {
			$DOMDocument = new DOMDocument;
			$DOMDocument->formatOutput = true;
			$this->_xmlEncode($mixed, $DOMDocument, $DOMDocument);
			return $DOMDocument->saveXML();
		} else {
			if (is_array($mixed)) {
				foreach ($mixed as $index => $mixedElement) {
					if (is_int($index)) {
						if ($index === 0) {
							$arrayRoot = $domElement;
						}						
						$node = $DOMDocument->createElement('item');
						$arrayRoot->appendChild($node);						
					} else {						
						$node = $DOMDocument->createElement($index);
						$domElement->appendChild($node);
					}

					$this->_xmlEncode($mixedElement, $node, $DOMDocument);
				}
			} else {
				$domElement->setAttribute('type', strtolower(gettype($mixed)));
				$mixed = is_bool($mixed) ? ($mixed ? 'true' : 'false') : $mixed;
				$domElement->appendChild($DOMDocument->createTextNode($mixed));
			}
		}
	}

	/**
	 * 
	 * Decode XML string into array
	 * 
	 * A root element "message" is expected
	 * 
	 * Example:
	 * 
	 * <code>
	 * 
	 * <?xml version="1.0"?>
	  <message>
	  <username type="string">admin</username>
	  <password type="string">Admin1!</password>
	  </message>


	 * </code>
	 *
	 * @param Request $requestBody
	 * @return type
	 * @throws Exception
	 */
	public function decode($requestBody) {
		

		$xml = simplexml_load_string($requestBody);
		$json = json_encode($xml);

		$data = $requestBody != "" ? json_decode($json, true) : [];

		// Check if the post is filled with an array. Otherwise make it an empty array.
		if (!is_array($data)) {
			throw new Exception("Malformed XML posted: \n\n" . var_export($requestBody, true));
		}

		return $data;
	}

	public static function getContentType() {
		return 'text/xml;charset=UTF-8;';
	}

}
