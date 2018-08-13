<?php
namespace Rumd3x\Ftp;

use Closure;
use Exception;

class FtpFile extends FtpObject {

    protected $contents;
    protected $local_file;
    protected $downloaded = false;

    public $size;

    public function download($filename = false, $async = false) {
        if ($async) {
            $retorno = $this->downloadAsync($async, $filename);
        } else {
            $retorno = $this->downloadNormal($filename);
        }
        return $retorno;
    }
    
    protected function downloadNormal($filename = false) {
        $this->local_file = $filename ?: ($this->local_file ?: $this->full_name);
        $download = ftp_get($this->ftp->getStream(), $this->local_file, $this->full_name, FTP_BINARY);
        if ($download) {
            $this->contents = file_get_contents($this->local_file, FILE_BINARY);
        }        
        return $this;
    }
    
    protected function downloadAsync($callback, $filename = false) {
        $this->local_file = $filename ?: ($this->local_file ?: $this->full_name);        
        $ref_ts = microtime(true);
        $file_piece = ftp_nb_get($this->ftp->getStream(), $this->local_file, $this->full_name, FTP_BINARY);
        while($file_piece === FTP_MOREDATA) {
            try {
                $continue = false;
                
                if ((microtime(true) - $ref_ts) >= 30) {
                    $this->ftp->keepAlive();
                    $ref_ts = microtime(true);
                }      
            
                $file_piece = ftp_nb_continue($this->ftp->getStream());
            } finally {
                $continue = true;
            } 
            if ($continue) continue;
        }
        if (is_object($callback) && ($callback instanceof Closure)) $callback($file_piece);
        if ($file_piece !== FTP_FINISHED) {
            throw new Exception("Failed to download. Try again.");
        }
        $this->contents = file_get_contents($this->local_file, FILE_BINARY);
        return $this;
    }
    
    public function upload($filename = false) {
        if (!empty($this->full_name)) {
            $this->local_file = $filename ?: ($this->local_file ?: $this->full_name);
            file_put_contents($this->local_file, $this->contents, FILE_BINARY);
            ftp_put($this->ftp->getStream(), $this->full_name, $this->local_file, FTP_BINARY);
        }
        return $this;
    }

    public function delete() {
        return @ftp_delete($this->ftp->getStream(), $this->full_name);
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
