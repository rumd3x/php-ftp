<?php
namespace Rumd3x\Ftp;

use StdClass;
use Exception;
use DateTime;
use DateTimeZone;

class Ftp {

    private $host;
    private $user;
    private $pass;
    private $port = 21;
	private $secure = false;

    private $stream;
    private $connected = false;

    public function __construct() {
        if (func_num_args() === 0) return;

        $args = func_get_args();
        if (func_num_args() > 1) {
            $this->parseConnectionDetails($args);           
        } elseif (func_num_args() === 1 && (is_array($args[0]) || is_object(is_array($args[0])))) {
            if (array_keys($arr) !== range(0, count($arr) - 1)) {
                $data = (Array) $args[0];
                $this->host = $data['host'];
                $this->user = $data['user'];
                $this->pass = $data['pass'];
				$this->secure = $data['ssl'];
            } else {
                $this->parseConnectionDetails($args[0]);
            }
        } elseif (func_num_args() > 0) {
            throw new Exception("Argumentos passados inválidos");
        }
		
        if (!empty($this->host)) {
            $this->connect();
        } else {
            throw new Exception("Host inválido: \"{$this->host}\"");
		}

        if (!empty($this->user)) {
            $this->pass = empty($this->pass) ? '' : $this->pass;
            $this->login();
        }
    }

    public function __destruct() {
        ftp_close($this->getStream());
    }

    private function parseConnectionDetails(Array $args) {
        foreach($args as $arg) {
            if (strtoupper($arg) === "SSL") {
				$this->setSecure();
			} elseif (empty($this->host) && filter_var(gethostbyname($arg), FILTER_VALIDATE_IP)) {
                $this->host = $arg; 
            } elseif (filter_var($arg, FILTER_VALIDATE_INT)) {
                $this->port = $arg;
            } elseif (empty($this->user)) {
                $this->user = $arg;
            } elseif (empty($this->pass)) {
                $this->pass = $arg;
            }
        }
    }
	
	public function connect() {
		if ($this->isSecure()) {
			$this->stream = @ftp_ssl_connect($this->host, $this->port);
		} else {
			$this->stream = @ftp_connect($this->host, $this->port);
		}		
		
		if (empty($this->stream)) {
			$is_ssl = $this->isSecure() ? 'com' : 'sem';
            throw new Exception("Não foi possível conectar ao host {$is_ssl} SSL \"{$this->host}\" usando a porta {$this->port}");
        }
	}

    public function login() {
        $this->connected = @ftp_login($this->stream, $this->user, $this->pass);
        if (!$this->connected) {
            throw new Exception("Usuário ou senha inválidos");
        }
        ftp_pasv($this->stream, true);
        return $this;
    }

    public function isConnected() {
        return $this->connected;
    }

    public function getFiles($dir = ".") {
        if (!$this->isConnected()) {
            $this->connect();
            $this->login();
        }

        if (@!ftp_chdir($this->getStream(), $dir)) {
            return [];
        }

        $filenames_arr = ftp_nlist($this->getStream(), ".");
        $files_arr = [];
        foreach($filenames_arr as $filename) {
            $timestamp = new DateTime();
            $timestamp->setTimestamp(ftp_mdtm($this->getStream(), $filename));
            $timestamp->setTimezone(new DateTimeZone('America/Sao_Paulo'));

            $file = new FtpFile;
            $file->setFtp($this);
            $file->name = $filename;
            $file->timestamp = $timestamp;
            $files_arr[] = $file;
        }
        return $files_arr;         
    }

    public function getFile($name) {
        $retorno = NULL;
        foreach($this->getFiles() as $file) {
            if (strpos($file->name, $name) !== false) {
                $retorno = $file;
                break;
            }
        }
        return $retorno;
    }

    public function up() {
        if (!$this->isConnected()) {
            $this->connect();
            $this->login();
        }

        @ftp_cdup($this->getStream());
        return $this;
    }

    public function dir($dir) {
        if (!$this->isConnected()) {
            $this->connect();
            $this->login();
        }

        @ftp_chdir($this->getStream(), $dir);
        return $this;
    }

    public function currentFolder() {
        return ftp_pwd($this->getStream());
    }

    public function createFolder($path) {
        $dirs = explode("/", $path);
        foreach($dirs as $dir) {
            $success = @ftp_mkdir($this->getStream(), $dir);
            if (!$success) break;
            if ($success) $this->dir($dir);
        }
        return $this;
    }

    public function getHost(){
		return $this->host;
	}

	public function setHost($host){
        $this->host = $host;
        return $this;
	}

	public function getUser(){
		return $this->user;
	}

	public function setUser($user){
        $this->user = $user;
        return $this;
	}

	public function getPass(){
		return $this->pass;
	}

	public function setPass($pass){
        $this->pass = $pass;
        return $this;
	}

	public function getPort(){
		return $this->port;
	}

	public function setPort($port){
        $this->port = $port;
        return $this;
	}

	public function getStream(){
        if (!$this->isConnected()) {
            $this->connect();
            $this->login();
        }
		return $this->stream;
	}

	public function setStream($stream){
        $this->stream = $stream;
        return $this;
	}
	
	public function setSecure($secure = true) {
		$this->secure = $secure;
	}
	
	public function isSecure() {
		return boolval($this->secure);
	}

}
