<?php

namespace ProcessMaker\Traits;

use Log;
use Illuminate\Support\Facades\Storage;

trait StreamsJsonToFile
{
	/**
	 * The file resource.
	 *
	 * @var object
	 */
	private $file;

	/**
	 * The path where our file should be stored.
	 *
	 * @var string
	 */
	protected $filePath;
	
	/**
	 * The name of our file.
	 *
	 * @var string
	 */
	protected $fileName;
	
	/**
	 * Open the file resource.
	 *
	 * @param string $name
	 * @param string|null $directory
	 *
	 * @return void
	 */	
	private function openFile($name, $directory = null)
	{
		$this->fileName = $name;
		Storage::put("{$directory}{$this->fileName}", '');
		$this->filePath = storage_path("app/{$directory}{$this->fileName}");
		$this->file = fopen($this->filePath, 'w+');
		fwrite($this->file, '{');
	}
	
	/**
	 * Write to the file resource.
	 *
	 * @param string $data
	 *
	 * @return void
	 */	
	private function write($data)
	{
		fwrite($this->file, $data);
	}
	
	/**
	 * Seek within the file resource.
	 *
	 * @param int $offset
	 * @param int $whence one of SEEK_SET, SEEK_CUR, SEEK_END
	 *
	 * @return void
	 */		
	private function seek($offset, $whence)
	{
		fseek($this->file, $offset, $whence);
	}
	
	/**
	 * Push a key/value pair.
	 *
	 * @param string|array|object $data
	 * @param string|array|object|null $value
	 * @param boolean $isLast
	 *
	 * @return void
	 */		
	private function push($data, $value = null, $isLast = false)
	{
		if ($value && (is_string($value) || is_array($value) || is_object($value))) {
			$data = preg_replace('/^{|}$/', '', json_encode([$data => $value]));
		} else {
			$data = json_encode($data);
		}
		
		if (! $isLast) {
			$data .= ', ';
		}
		
		fwrite($this->file, $data);
	}
	
	/**
	 * Push a JSON key.
	 *
	 * @param string $data
	 *
	 * @return void
	 */	
	private function pushKey($data)
	{
		$data = preg_replace('/^{|}$/', '', json_encode($data));
		$data .= ': ';
		
		fwrite($this->file, $data);
	}

	/**
	 * Push a JSON value.
	 *
	 * @param string|array|object $data
	 *
	 * @return void
	 */	
	private function pushValue($data)
	{
		$data = preg_replace('/^{|}$/', '', json_encode($data));		
		fwrite($this->file, $data);
	}
	
	/**
	 * Close and save the file.
	 *
	 * @return boolean
	 */
	protected function closeFile($noBracket = false)
	{
		if (! $noBracket) {
			fwrite($this->file, '}');
		}
		return fclose($this->file);
	}
}