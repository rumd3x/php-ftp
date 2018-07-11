# php-ftp
A Nice and easy to use PHP utility for handling Files over FTP.


## Installation
To install via composer add this to your composer.json
```json
  "minimum-stability": "dev",
	"repositories": [
		{ "type": "git", "url": "https://github.com/rumd3x/php-ftp.git" }
  ]
```
And then run
```sh
  composer require "rumd3x/php-ftp:*"
```

## Usage
### Connecting to Server
The constructor takes any amount of arguments, in any order. But you have to specify the username first, and then the password.

You can specify the port as an integer, the default is 21.

You can specify if the connection uses SSL or not by passing an extra argument with the string 'SSL'.

You can also not specify any args and connect later.
```php
$ftp = new Rumd3x\Ftp('host.example.com', 'user', 'pass', 21);
$conn1 = $ftp->isConnected(); 

print_r($conn1); // Returns a boolean;

//or

$ftp2 = new Rumd3x\Ftp();
$conn2 = $ftp2->setHost('192.168.1.123')->setSecure()->setPort(666)->connect()
->setUser('test')->setPass('secret')->login()->isConnected();

print_r($conn2); // Returns a boolean;
```

### Handling directories
You can navigate through folders and create new folders using built-in methods.
```php
$dir = $ftp->currentFolder(); 
// $dir has "/"

$dir = $ftp->createFolder('test/example/123')->dir('test')->dir('example/123')->currentFolder();
// $dir now has "/test/example/123"

$dir = $ftp->up()->up()->currentFolder();
// $dir now has "/test"
```
