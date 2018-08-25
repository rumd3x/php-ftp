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

### Other FTP Commands
To keep the connection alive
```php
$ftp = new Rumd3x\Ftp\Ftp('host.example.com', 'user', 'pass', 21);
$ftp->keepAlive(); // Sends NOOP to the server to keep the connection alive

$return = $ftp->executeRaw("NOOP"); // Allows you to send a arbitrary commands to the server 

print_r($return); // Outputs a object with the response data
```

To list everything in the current directory, simply:
```php
$files = $ftp->getAll(); 
// Returns an array of mixed Directories and Files as instances of FtpFolder and FtpFile respectively
// All directories comes first, then all the files
```

### Handling directories
You can navigate through folders and create new folders using methods built-in the connection.
```php
$dir = $ftp->currentFolder(); // gets the current folder directory you are in on the server 
// $dir has "/"

$dir = $ftp->createFolder('test/example/123')->dir('test')->dir('example/123')->currentFolder();
// $dir now has "/test/example/123"

$dir = $ftp->up()->up()->currentFolder();
// $dir now has "/test"
```

To get the list of folders on your current directory:
```php
$ftp = new Rumd3x\Ftp\Ftp('host.example.com', 'user', 'pass', 21);
$folders = $ftp->getFolders(); // Outputs an array of Rumd3x\Ftp\FtpFolder
```

Or to get the FtpFolder instance of a specific folder by its name:
```php
$folder = $ftp->getFolder('test'); 
// Outputs an instance of Rumd3x\Ftp\FtpFolder in case the folder with name 'test' exists in the current directory

$folder_name = $folder->name; // name property of FtpFolder 
$folder_full_name = $folder->full_name; // full_name property of FtpFolder 
$folder_timestamp = $folder->timestamp; // timestamp property of FtpFolder 
$folder_permission = $folder->permission; // permission property of FtpFolder 
```

#### Creating and Deleting Directories

You can also navigate through folders, create and delete using the FtpFolder Object.
```php
//Connect to the FTP Server
$ftp = new Rumd3x\Ftp\Ftp('host.example.com', 'user', 'pass', 21);

//Create the folder 'FolderName' on your current dir
$folder = new Rumd3x\Ftp\FtpFolder($ftp, 'FolderName');

$folder->create()->navigateTo(); // creates the folder and navigates to it
$ftp->up(); // navigates one level up
$folder->delete(); // deletes the folder from the server
```

### Handling files 

To get the list of files on your current directory:
```php
$ftp = new Rumd3x\Ftp\Ftp('host.example.com', 'user', 'pass', 21);
$folders = $ftp->getFiles(); // Outputs an array of Rumd3x\Ftp\FtpFile
```
Or to get the FtpFile instance of a specific file by its name:
```php
// Outputs an instance of Rumd3x\Ftp\FtpFile in case the folder with name 'file.txt' exists in the current directory
$file = $ftp->getFolder('file.txt'); 

// File properties
$file_name = $file->name; // name property of FtpFile
$file_full_name = $file->full_name; // full_name property of FtpFile
$file_timestamp = $file->timestamp; // timestamp property of FtpFile
$file_permission = $file->permission; // permission property of FtpFile
```

To read the contents of a file on the server simply
```php
$file->getContents(); // Returns a string with the file contents
```

To remove a file from the server simply
```php
$file->delete(); // Returns a boolean with the success flag
```

#### Downloading Files

To download a file on the server simply
```php
$local_file = '/tmp/file.txt';
$file->download($local_file); // Will download the file to the local file path
```

For large files you can also make async downloads by passing a second parameter
```php
$local_file = '/tmp/file.txt';
$file->download($local_file, true); // Will download the file asynchronously to the local file path 
```

You can also pass a callback to be executed on the download completion.
```php
$local_file = '/tmp/file.txt';

// Will also download the file asynchronously to the local file path 
$file->download($local_file, function($status) {
  if ($status === FTP_FINISHED) {
    echo 'Download success.';
  } elseif ($status === FTP_FAILED) {
    echo 'Download failed.';
  } else {
    echo 'Something else happened.';
  }
}); 
```

#### Editing Files
```php
$contents = 'new file contents';
$file->setContents($contents);
$file->upload();
```

#### Creating new Files
```php
$ftp = new Rumd3x\Ftp\Ftp('host.example.com', 'user', 'pass', 21);

$local_file = '/etc/file.txt';
$contents = file_get_contents($local_file);

//Create the file 'file.txt' on your current dir
$file = new Rumd3x\Ftp\FtpFile($ftp, 'file.txt');
$file->setContents($contents);
$file->upload();
```
