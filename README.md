# Thumbnail creator in Symfony 5.3 and PHP8.0

Thumbnail creator with default maximum width of 150 pixels. 
It can store converted images in local file system, dropbox or Amazon S3.
To proper working you need to configure this services and set proper access. 

## Setup

First of all download the code.
To get it working, follow these steps:

**Download Composer dependencies**

Make sure you have [Composer installed](https://getcomposer.org/download/)
and then run:

```
composer install
```

You may alternatively need to run `php composer.phar install`, depending
on how you installed Composer.

**Configure the .env (or .env.local) File**

Open the `.env` file and make any adjustments you need - specifically
`DROPBOX_ACCESS_TOKEN` for Dropbox access, `AWS_S3_*` for Amazon S3 service and.`LOCAL_DIR` if target would be in local filesystem. 
Or, if you want, you can create a `.env.local` file
and *override* any configuration you need there (instead of changing
`.env` directly).

**Run from console**

To get full list of options run

```
bin/console help app:thumbnail
```

First argument is folder containing photos for conversion

If you don't specify destinationType in parameters, application will ask you about destination file system.
You can also specify destination folder on target resource.
For example:

```
bin/console app:thumbnail -t amazon -d 'test/'
```

will store thumbnails on Amazon S3 service in test folder.

## Technical information 

System uses [Flysystem](https://flysystem.thephpleague.com/) file abstraction 
as mechanism to store files and [Imagick](https://www.php.net/manual/en/book.imagick.php) for image rescaling.   