<?php
namespace Rumd3x\Ftp;

class FtpFile {

    private $ftp;
    private $contents;
    private $local_file;
    private $downloaded = false;

    public $name;
    public $timestamp;

    public function __construct($name = NULL, $contents = NULL) {
        if (!empty($name)) $this->name = $name;
        if (!empty($contents)) $this->contents = $contents;
    }

    public function download($filename = false) {
        $this->local_file = $filename ?: ($this->local_file ?: $this->name);
        $download = ftp_get($this->ftp->getStream(), $this->local_file, $this->name, FTP_ASCII);
        if ($download) {
            $this->contents = file_get_contents($this->local_file, FILE_TEXT);
        }        
        return $this;
    }
    
    public function upload($filename = false) {
        if (!empty($this->name)) {
            $this->local_file = $filename ?: ($this->local_file ?: $this->name);
            file_put_contents($this->local_file, $this->contents, FILE_TEXT);
            ftp_put($this->ftp->getStream(), $this->name, $this->local_file, FTP_ASCII);
        }
        return $this;
    }

    public function delete() {
        return @ftp_delete($this->ftp->getStream(), $this->name);
    }

    public function setFtp($ftp) {
        $this->ftp = $ftp;
        return $this;
    }

    public function getContents() {
        if (!$this->downloaded && is_null($this->contents)) $this->download();
        return $this->contents;
    }

    public function setContents($contents) {
        $this->contents = $contents;
        return $this;
    }

}
