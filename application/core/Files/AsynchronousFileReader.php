<?php
namespace ManiaControl\Files;

use ManiaControl\ManiaControl;

/**
 * Asynchronous File Reader
 *
 * @author kremsy & steeffeen
 */
class AsynchronousFileReader {
	/**
	 * Constants
	 */
	const TIMEOUT_ERROR        = 'Timed out while reading data';
	const RESPONSE_ERROR       = 'Connection or response error';
	const NO_DATA_ERROR        = 'No data returned';
	const INVALID_RESULT_ERROR = 'Invalid Result';
	const SOCKET_TIMEOUT       = 10;


	/**
	 * Private Properties
	 */
	private $sockets = array();
	private $maniaControl = null;

	/**
	 * Construct
	 *
	 * @param ManiaControl $maniaControl
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;
	}

	/**
	 * Appends the Data
	 */
	public function appendData() {
		foreach($this->sockets as $key => &$socket) {
			/** @var SocketStructure $socket */
			do {
				$line = fgets($socket->socket, 4096);
				if (empty($socket->header) && $line == "\r\n") {
					$socket->header       = $this->parseHeader($socket->streamBuffer);
					$socket->streamBuffer = "";
					$line                 = "";
				}
				$socket->streamBuffer .= $line;

				$chunked = isset($socket->header["transfer-encoding"]) && $socket->header["transfer-encoding"] == "chunked" && $line == "0";
				if ($chunked || isset($socket->header["content-length"]) && strlen($socket->streamBuffer) >= $socket->header["content-length"]) {
					fclose($socket->socket);
					unset($this->sockets[$key]);
					$this->handleContent($socket);
					continue 2;
				}

				$meta = stream_get_meta_data($socket->socket);
			} while($meta["unread_bytes"] > 0);
		}
	}

	/**
	 * Handles the Content
	 *
	 * @param $socket
	 */
	private function handleContent(SocketStructure $socket) { //TODO timeout handling
		//if (feof($socket->socket) || time() > ($socket->creationTime + self::SOCKET_TIMEOUT)) {
		$result = "";
		$error  = 0;
		/*if (time() > ($socket->creationTime + self::SOCKET_TIMEOUT)) {
			$error = self::TIMEOUT_ERROR;
		} else*/
		if ($socket->header["status"] != "200") {
			$error  = self::RESPONSE_ERROR;
			$result = $this->parseResult($socket);

			if (intval($socket->header["status"]) > 300 && intval($socket->header["status"]) < 310) {
				$this->maniaControl->errorHandler->triggerDebugNotice("HTTP Error, Code:" . $socket->header["status"] . "URL: " . $socket->url);
			}

		} else if ($socket->streamBuffer == '') {
			$error = self::NO_DATA_ERROR;
		} else {
			$result = $this->parseResult($socket);
			if ($result == self::INVALID_RESULT_ERROR) {
				$error = self::INVALID_RESULT_ERROR;
			}
		}
		call_user_func($socket->function, $result, $error);
		//}
	}

	/**
	 * Parse the Stream Result
	 *
	 * @param SocketStructure $socket
	 * @internal param $streamBuffer
	 * @return string
	 */
	private function parseResult(SocketStructure $socket) {

		if (isset($socket->header["transfer-encoding"]) && $socket->header["transfer-encoding"] == "chunked") {
			$result = $this->decode_chunked($socket->streamBuffer);
		} else {
			$result = $socket->streamBuffer;
		}

		return $this->decompressData($socket->header, $result);
	}

	/**
	 * Checks if the data is Compressed and uncompress it
	 *
	 * @param $header
	 * @param $data
	 * @return string
	 */
	private function decompressData($header, $data) {
		if (isset($header["content-encoding"])) {
			switch($header["content-encoding"]) {
				case "gzip":
				case "gzip;":
					return gzdecode($data);
				case "deflate":
				case "deflate;":
					return gzinflate($data);
			}
		}
		return $data;
	}

	/**
	 * Decode Chunks
	 *
	 * @param $str
	 * @return string
	 */
	private function decode_chunked($str) {
		for($res = ''; !empty($str); $str = trim($str)) {
			$pos = strpos($str, "\r\n");
			$len = hexdec(substr($str, 0, $pos));
			$res .= substr($str, $pos + 2, $len);
			$str = substr($str, $pos + 2 + $len);
		}
		return $res;
	}

	/**
	 * Parse the Header
	 *
	 * @param $header
	 * @return array
	 */
	function parseHeader($header) {
		$headers = explode("\r\n", $header);
		$output  = array();

		if ('HTTP' === substr($headers[0], 0, 4)) {
			list(, $output['status'], $output['status_text']) = explode(' ', $headers[0]);
			unset($headers[0]);
		}

		foreach($headers as $v) {
			if ($v == "") {
				break;
			}
			$h                         = preg_split('/:\s*/', $v);
			$output[strtolower($h[0])] = $h[1];
		}

		return $output;
	}


	/**
	 * Send Data via POST Method
	 *
	 * @param        $url
	 * @param        $function
	 * @param        $content
	 * @param string $contentType
	 * @return bool|null
	 */
	public function postData($url, $function, $content, $compressed = false, $contentType = 'UTF-8') {
		if (!is_callable($function)) {
			$this->maniaControl->log("Function is not callable");
			return false;
		}

		if (!$url) {
			return null;
		}
		$urlData = parse_url($url);
		$port    = (isset($urlData['port']) ? $urlData['port'] : 80);

		$socket = @fsockopen($urlData['host'], $port, $errno, $errstr, 4);
		if (!$socket) {
			return false;
		}

		$query = 'POST ' . $urlData['path'] . ' HTTP/1.1' . PHP_EOL;
		$query .= 'Host: ' . $urlData['host'] . PHP_EOL;
		$query .= 'Accept-Charset: utf-8' . PHP_EOL;
		$query .= 'Accept-Encoding: gzip, deflate' . PHP_EOL;
		//$query .= 'Content-Encoding: gzip' . PHP_EOL;
		$query .= 'Content-Type: text/xml; charset=utf-8;' . PHP_EOL;
		$query .= 'Keep-Alive: 300' . PHP_EOL;
		$query .= 'Connection: Keep-Alive' . PHP_EOL;
		$query .= 'User-Agent: ManiaControl v' . ManiaControl::VERSION . PHP_EOL;
		$query .= 'Content-Length: ' . strlen($content) . PHP_EOL . PHP_EOL;

		$query .= $content . PHP_EOL;

		fwrite($socket, $query);

		$success = stream_set_blocking($socket, 0);
		if (!$success) {
			return false;
		}

		$socketStructure = new SocketStructure($url, $socket, $function);
		array_push($this->sockets, $socketStructure);

		return true;
	}

	/**
	 * Load a remote file
	 *
	 * @param string $url
	 * @param        $function
	 * @param string $contentType
	 * @param string $customHeader
	 * @return bool
	 */
	public function loadFile($url, $function, $contentType = 'UTF-8', $customHeader = '') {
		if (!is_callable($function)) {
			$this->maniaControl->log("Function is not callable");
			return false;
		}

		if (!$url) {
			return null;
		}
		$urlData  = parse_url($url);
		$port     = (isset($urlData['port']) ? $urlData['port'] : 80);
		$urlQuery = isset($urlData['query']) ? "?" . $urlData['query'] : "";

		$socket = @fsockopen($urlData['host'], $port, $errno, $errstr, 4);
		if (!$socket) {
			return false;
		}

		if ($customHeader == '') {
			$query = 'GET ' . $urlData['path'] . $urlQuery . ' HTTP/1.1' . PHP_EOL;
			$query .= 'Host: ' . $urlData['host'] . PHP_EOL;
			$query .= 'Content-Type: ' . $contentType . PHP_EOL;
			$query .= 'Connection: close' . PHP_EOL;
			$query .= 'User-Agent: ManiaControl v' . ManiaControl::VERSION . PHP_EOL;
			$query .= PHP_EOL;
		} else {
			$query = $customHeader;
		}

		fwrite($socket, $query);

		$success = stream_set_blocking($socket, 0);
		if (!$success) {
			return false;
		}

		$socketStructure = new SocketStructure($url, $socket, $function);
		array_push($this->sockets, $socketStructure);

		return true;
	}
}