<?php
namespace Rumd3x\Ftp;

use StdClass;
use Exception;
use Carbon\Carbon;

use Rumd3x\BaseObject\BaseObject;

class Ftp extends BaseObject {

    private $host;
    private $user;
    private $pass;
    private $port = 21;
	private $secure = false;

    private $stream;
	private $system;
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
        $this->disconnect();
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
		$this->system = ftp_systype($this->stream);
		return $this;
	}

    public function login() {
        $this->connected = @ftp_login($this->stream, $this->user, $this->pass);
        if (!$this->connected) {
            throw new Exception("Usuário ou senha inválidos");
        }
        ftp_pasv($this->stream, true);
        return $this;
    }
	
	public function disconnect() {
		ftp_close($this->getStream());
		$this->setStream(NULL);
		$this->connected = false;
		return $this;
	}
	
	public function keepAlive() {
		$response = $this->executeRaw("NOOP");
		if ($response->code <= 200 || $response->code >= 400) {
			$currentFolder = $this->currentFolder();
			$this->disconnect();
			$this->connect();
			$this->login();
			$this->dir($currentFolder);
		}		
		return $this;
	}

    public function isConnected() {
        return $this->connected;
    }
	
	public function executeRaw($command) {
		$response = @ftp_raw($this->getStream(), trim($command));
		$code = intval(preg_replace('/\D/', '', $response));
		$responseObject = new StdClass;
		$responseObject->text = $response;
		$responseObject->code = $code;
		return $responseObject;
    }
    
    public function getAll() {
        $dirs = $this->getFolders();
        $files = $this->getFiles();
        return array_merge($dirs, $files);
    }

    private function getRawList() {
        if (!$this->isConnected()) {
            $this->connect();
            $this->login();
        }
        $ftp_rawlist = ftp_rawlist($this->getStream(), ".");
        $rawlist = [];
		if (strtoupper($this->system) !== strtoupper("Windows_NT")) {
			foreach ($ftp_rawlist as $v) {
			  $info = array();
			  $vinfo = preg_split("/[\s]+/", $v, 9);
			  if ($vinfo[0] !== "total") {
				$info['chmod'] = $vinfo[0];
				$info['num'] = $vinfo[1];
				$info['owner'] = $vinfo[2];
				$info['group'] = $vinfo[3];
				$info['size'] = $vinfo[4];
				$info['month'] = $vinfo[5];
				$info['day'] = $vinfo[6];
				$info['time'] = $vinfo[7];
				$info['name'] = $vinfo[8];
				$rawlist[$info['name']] = $info;
			  }
			}
		} else {
			foreach ($ftp_rawlist as $v) {
				$split = false;
				ereg("([0-9]{2})-([0-9]{2})-([0-9]{2}) +([0-9]{2}):([0-9]{2})(AM|PM) +([0-9]+|<DIR>) +(.+)", $v, $split);
				if (is_array($split)) {
					$parsed = [];
					$split[3] = $split[3]<70 ? $split[3]+=2000; : $split[3]+=1900; // 4digit year fix
					$parsed['chmod'] = $split[7]=="<DIR>" ? "d" : "";
					$parsed['num'] = "";
					$parsed['group'] = "";
					$parsed['owner'] = "";
					$parsed['size'] = $split[7];
					$parsed['month'] = $split[1];
					$parsed['day'] = $split[2];
					$parsed['time'] = $split[3];
					$parsed['name'] = $split[8];
					$rawlist[$parsed['name']] = $parsed;
				 }
			}			
		}
        
        return $rawlist;
    }

    public function getFiles() {   
        $file = array();
        $currentFolder = $this->currentFolder();
        foreach ($this->getRawList() as $k => $v) {
          if ($v['chmod']{0} !== "d" && $v['chmod']{0} === "-") {
            $file[$k] = $v;
          }
        }
        $files = [];
        foreach ($file as $filename => $fileinfo) {
            $this_file = new FtpFile($this, $filename);
            $this_file->full_name = "{$currentFolder}/{$filename}";
            $this_file->permission = $fileinfo['chmod'];
            $this_file->owner = $fileinfo['owner'];
            $this_file->group = $fileinfo['group'];
            $this_file->size = $fileinfo['size'];
            $this_file->setFtp($this);
            $timestamp = ftp_mdtm($this->getStream(), $this_file->name);
            $this_file->timestamp = Carbon::createFromTimestamp($timestamp);
            $files[] = $this_file;
        }
        return $files;
    }

    public function getFolders() {
        $dir = array();
        $currentFolder = $this->currentFolder();
        foreach ($this->getRawList() as $k => $v) {
          if ($v['chmod']{0} === "d") {
            $dir[$k] = $v;
          }
        }
        $dirs = []; 
        foreach ($dir as $dirname => $dirinfo) {
            $this_dir = new FtpFolder($this, $dirname);
            $this_dir->full_name = "{$currentFolder}/{$dirname}";            
            $this_dir->permission = $dirinfo['chmod'];
            $this_dir->owner = $dirinfo['owner'];
            $this_dir->group = $dirinfo['group'];
            $timestamp = ftp_mdtm($this->getStream(), $this_dir->name);
            $this_dir->timestamp = Carbon::createFromTimestamp($timestamp);
            $this_dir->setFtp($this);
            $dirs[] = $this_dir;
        }
        return $dirs;
    }

    public function getFolder($name) {
        $retorno = NULL;
        foreach($this->getFolders() as $folder) {
            if (strpos($folder->full_name, $name) !== false) {
                $retorno = $folder;
                break;
            }
        }
        return $retorno;
    }

    public function getFile($name) {
        $retorno = NULL;
        foreach($this->getFiles() as $file) {
            if (strpos($file->full_name, $name) !== false) {
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
