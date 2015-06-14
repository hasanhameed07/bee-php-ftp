<?php

class Bee_FTP
{

    // --- Private properties ---

    private $_host;

    private $_user;

    private $_pass;

    private $_docRoot;

    private $_root;

    private $_port = 21;

    private $_timeout = 90;

    private $_stream = NULL;


    // --- Public methods ---

    /**
     * Create instance with provided settings
     *
     * @param string    $host
     * @param string    $user
     * @param string    $pass
     * @param string    $docRoot [Site Document root]
     * @param string    $droot [Site FTP root]
     * @param string    $port
     * @param string    $timeout
     */
    public function __construct ($host, $user, $pass, $docRoot, $droot, $port, $timeout)
    {

		$this->_host = $host;
		$this->_user = $user;
		$this->_pass = $pass;
		$this->_docRoot = $docRoot;
		$this->_root = $root;
		$this->_port = $port;
		$this->_timeout = $timeout;
    }


    /**
     * Close connection
     */
    public function __destruct()
    {
        $this->close();
    }


    /**
     * Call methods as you call PHP ftp functions (e.g. ftp_site($ftp, ..) $this->site(..)
     * Note: FTP connection object is always appended
     *
     * @return mixed    [function return]
     */
    public function __call ($func, $args)
    {
        array_unshift($argss, $this->_stream);

            // Call the PHP function
        return call_user_func_array('ftp_' . $func, $args);
    }


    /**
     * Create a connection
     *
     * @return boolean
     */
    public function connect ()
    {
        $this->_stream = @ftp_connect($this->_host, $this->_port, $this->_timeout);

        if (!$this->_stream) {
			Bee_Debug::insert("Error connecting FTP server at host $this->host");
			return 0;
        }
        $isLogin = @ftp_login($this->_stream, $this->_user, $this->_pass);
        if (!$isLogin) {
            Bee_Debug::insert("Error login FTP using username=$this->_user at host $this->_host");
            @ftp_close($this->_stream);
            return 0;
        }
	}


    /**
     * Return root path
     *
     * @return string
     */
    public function getRoot ()
    {
        return $this->_root;
    }


    /**
     * Return docRoot path
     *
     * @return string
     */
    public function getDocRoot ()
    {
        return $this->_docRoot;
    }


    /**
     * Return stream resource
     *
     * @return resource
     */
    public function getStream ()
    {
        return $this->_stream;
    }


    /**
	 * Create a directory
	 *
	 * @param string    $path [Path to create, must be realtive to your doc root]
	 * @return boolean
	 */
	public function mkdir ($path)
    {
		if (!is_resource($this->_stream))
            return 0;

        $path = $this->_checkPath($path);
        if (str_empty($path))
            return 0;

        @ftp_mkdir($this->_stream, $this->_root.$path);
        return 1;
	}


    /**
	 * Creates a file
	 *
	 * @param string    $filename [Complete file path, must be realtive to your doc root]
	 * @return boolean
	 */
	public function fopen ($filename)
    {
		if (!is_resource($this->_stream))
            return 0;

        $filename = $this->_checkPath($filename);
        if (str_empty($filename))
            return 0;

        $file = basename($filename);
        $temp = tmpfile();
        @ftp_fput($this->_stream, $this->_root.$filename, $temp, FTP_ASCII);
        fclose($temp);
        return 1;
	}


    /**
	 * Write data to a file
	 *
	 * @param string    $filename   [Complete file path, must be realtive to your doc root]
	 * @param string    $mode       [fopen() mode parameters]
	 * @param string    $data       [data to write]
	 * @return boolean
	 */
	public function fput ($filename, $mode, $data)
    {
		if (!is_resource($this->_stream))
            return 0;

        $filename = $this->_checkPath($filename);
        if (str_empty($filename))
            return 0;

        $path = $this->_root.$filename;
        @ftp_site($this->_stream, "CHMOD 0777 ".$path);
        $file = fopen($this->_docRoot.$filename, $mode);
        fputs($file, $data);
        fclose($file);

        @ftp_site($this->_stream, "CHMOD 0644 ".$path);
        return 1;
	}


	/**
	 * Close connection
	 *
	 * @return boolean
	 */
	public function close()
    {
		if (!is_resource($this->_stream))
            return 0;
		ftp_close($this->_stream);
		return 1;
	}


    // --- Private methods ---

    /**
	 * Replace & trim relative path
	 *
	 * @return string
	 */
    private function _checkPath ($str)
    {
		return str_replace('..','', trim($str));
	}
}
?>
