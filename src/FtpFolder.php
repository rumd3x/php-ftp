<?php
namespace Rumd3x\Ftp;

class FtpFolder extends FtpObject {
    
    public function navigateTo() {
        $this->ftp->dir($this->full_name);
        return $this;
    }

    public function create() {
        $this->ftp->createFolder($this->full_name);
        return $this;
    }

    public function delete() {
        return @ftp_rmdir($this->ftp->getStream(), $this->full_name);
    }
}
