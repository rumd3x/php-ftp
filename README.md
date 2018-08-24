# php-ftp
A Nice and easy to use PHP utility for handling Files over FTP.


## Installation
To install via composer add this to your composer.json
```json
"minimum-stability": "dev",
"prefer-stable": true,
```
And then run
```sh
  composer require "rumd3x/php-ftp:*"
```

## Usage
### Connecting to Server
The constructor takes any amount of arguments, in any order. It will identify automatically the host, port and ssl specifications, but you still have to specify the username first, then the password.

If you need to, you can specify the port as an integer, the default is 21.

You can specify if the connection uses SSL or not by passing an extra argument with the string 'SSL'.

You can also not specify any args and connect later.
```php
$ftp = new Rumd3x\Ftp\Ftp('host.example.com', 'user', 'pass', 21);
$conn1 = $ftp->isConnected(); 

print_r($conn1); // Returns a boolean;

//or

$ftp2 = new Rumd3x\Ftp\Ftp();
$conn2 = $ftp2->setHost('192.168.1.123')->setSecure()->setPort(666)->connect()
->setUser('test')->setPass('secret')->login()->isConnected();

print_r($conn2); // Returns a boolean;

// or 

$ftp3 = new Rumd3x\Ftp\Ftp(21, 'ssl', 'user', 'pass', 'host.example.com');
$conn3 = $ftp3->isConnected(); 

print_r($conn3); // Returns a boolean;
```

### Handling directories
You can navigate through folders and create new folders using methods built-in the connection.
```php
$dir = $ftp->currentFolder(); 
// $dir has "/"

$dir = $ftp->createFolder('test/example/123')->dir('test')->dir('example/123')->currentFolder();
// $dir now has "/test/example/123"

$dir = $ftp->up()->up()->currentFolder();
// $dir now has "/test"
```

You can also navigate through folders, create and delete using the FtpFolder Object.
```php
$ftp = new Rumd3x\Ftp\Ftp('host.example.com', 'user', 'pass', 21);
$folder = new Rumd3x\Ftp\FtpFolder($ftp);
$folder->create()->navigateTo();
$folder_name = $folder->name; // name property of FtpFolder 
$folder_full_name = $folder->full_name; // full_name property of FtpFolder 
$folder_timestamp = $folder->timestamp; // timestamp property of FtpFolder 
$folder_permission = $folder->permission; // permission property of FtpFolder 
$ftp->up();
$folder->delete();
```

To get the list of folders on your current directory:
```php
$ftp = new Rumd3x\Ftp\Ftp('host.example.com', 'user', 'pass', 21);
$folders = $ftp->getFolders(); // Outputs an array of Rumd3x\Ftp\FtpFolder
```

Or to get the FtpFolder instance of a specific folder by its name:
```php
$ftp = new Rumd3x\Ftp\Ftp('host.example.com', 'user', 'pass', 21);
$folder = $ftp->getFolder('test'); // Outputs an instance of Rumd3x\Ftp\FtpFolder in case the folder exists
```

### Handling files 
To-do the documentation of this
