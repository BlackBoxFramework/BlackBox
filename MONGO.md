# Installing MongoDB

BlackBox's ActiveRecord class uses MongoDB to store models. Use the instructions below are for Windows 64-bit:

If you are using Windows 64-bit, it's a good idea to use the 64-bit version of MongoDB. Before you start, you need to install a Hotfix for Windows : http://support.microsoft.com/kb/2731284

1. Download the current version of MongoDB from http://www.mongodb.org/downloads
2. Extract the zip file
3. Open command prompt and type:
	
	cd C:\
	md data
	md data\

4. Go to the folder where you extracted MongoDB and go into the `bin` folder. Type `mondod.exe`. (This gets runs the database)
5. Go to https://s3.amazonaws.com/drivers.mongodb.org/php/index.html and download the latest PHP driver for MongoDB
6. Extract the MongoDB driver which corresponds to your version of PHP to your `ext` folder. (should be something like php_mongo-1.3.3-5.5-vc11.dll). If you're running the 64-bit version of PHP, choose the file ending in -x86_64
7. Rename the extracted .dll file to `php_mongo.dll`
8. Add the following line to your `php.ini`:

	extension=php_mongo.dll
	