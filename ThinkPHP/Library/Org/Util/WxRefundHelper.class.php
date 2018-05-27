<?php

/**
 * @Author nick
 * @Blog http://www.lampnick.com
 * @Email nick@lampnick.com
 */
 namespace Org\Util;
 
class WxRefundHelper
{
	const CIPHER = MCRYPT_RIJNDAEL_128;
	const MCRYPT_MODE = MCRYPT_MODE_ECB;
    public static $mch_key = 'xxxxxxxxxxxxxxxxxxxxxxxxxxx';

    public function __construct($mch_key='')
    {
        if($mch_key) self::$mch_key = $mch_key;
    }

    /**
	 * You should implements this method to handle you own business logic.
	 * @param array $decryptedData
	 * @param string $msg this message will return to wechat if something error.
	 * @return bool
	 */
	protected function handelInternal(array $decryptedData,  &$msg)
	{
		//You should implements this method to handle you own business logic.
		return true;
	}
	
	/**
	 * handle wechat pay refund notify
	 */
	public function handle($data='')
	{

		try {
		    if(!$data){
                $xml = file_get_contents("php://input");
                $data = $this->xml2array($xml);
            }

			$encryptData = base64_decode($data['req_info']);

			$decryptedData = $this->_decryptAesData($encryptData);

			$msg = 'OK';
			$result = $this->handelInternal($decryptedData, $msg);

			$returnArray['return_msg'] = $msg;
			if (true === $result) {
				$returnArray['return_code'] = 'SUCCESS';
			} else {
				$returnArray['return_code'] = 'FAIL';
			}
			//return $decryptedData;
			$this->replyNotify($returnArray);
		} catch (\Exception $e) {
			throw new \Exception($e);
		}
	}
	
	/**
	 * reply to wechat
	 * @param $xml
	 */
	public function replyNotify($xml)
	{
		if (is_array($xml)) {
			$xml = $this->toXml($xml);
		}
		echo $xml;
	}
	
	/**
	 * @param string $xml
	 * @return array
	 * @throws \Exception
	 */
	public function xml2array( $xml)
	{
		if (empty($xml)) {
			throw new \Exception('Error xml data!');
		}
		$p = xml_parser_create();
		xml_parse_into_struct($p, $xml, $values, $index);
		xml_parser_free($p);
		$result = [];
		foreach ($values as $val) {
			$result[strtolower($val['tag'])] = $val['value'];
		}
		return $result;
	}
	
	/**
	 * output xml
	 * @param array $array
	 * @return string
	 * @throws \Exception
	 */
	public function toXml(array $array)
	{
		if (empty($array)) {
			throw new \Exception("array is empty！");
		}
		$xml = "<xml>";
		foreach ($array as $key => $val) {
			if (is_numeric($val)) {
				$xml .= "<" . $key . ">" . $val . "</" . $key . ">";
			} else {
				$xml .= "<" . $key . "><![CDATA[" . $val . "]]></" . $key . ">";
			}
		}
		$xml .= "</xml>";
		return $xml;
	}
	
	/**
	 * decrypt data
	 * @param string $encryptData
	 * @param string $md5LowerKey
	 * @return array
	 */
	private function _decryptAesData( $encryptData,  $md5LowerKey = '')
	{
		if (empty($md5LowerKey)) {
			$md5LowerKey = strtolower(md5(self::$mch_key));
		}
		$iv = mcrypt_create_iv(mcrypt_get_iv_size(self::CIPHER, self::MCRYPT_MODE), MCRYPT_RAND);
		$decrypted = mcrypt_decrypt(self::CIPHER, $md5LowerKey, $encryptData, self::MCRYPT_MODE, $iv);
		return $this->xml2array($decrypted);
	}
}
