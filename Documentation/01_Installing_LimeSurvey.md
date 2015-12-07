* To ensure that limesurvey will work properly, you'll want to choose one of the following hosting services compatible with limesurvey:

	* 1&1
	* A2 Hosting
	* Bluehost
	* Dreamhost
	* Host Color
	* HostPC
	* InMotion
	* ICDSoft
	* IX Webhosting
	* RoseHosting.com
	* Viux
	* WebFaction

(This list contains compatible services in the US. See this link for the full list : https://manual.limesurvey.org/LimeSurvey-compatible_hosting_companies)

* Some of these services may offer one click installation. Use the following instructions if you want to install manually.

* There are some additional requirements your server has to meet. These will also be checked automatically during the installation. You should be fine if you're using one of the compatible hosting sites, but if you are using another server you may want to check this link for details: https://manual.limesurvey.org/Installation

* To start, first download the latest Limesurvey zip package from this link: https://www.limesurvey.org/en/download
Unzip the package into a dedicated folder.
Then you need to upload the files to your web server using an FTP program, such as FileZilla or CyberDuck.

* Using the FTP program, connect to your webserver. To do this you will need to know your **server name**, and the **username** and **password** for your server. These information should be available to you after you register for one of the hosting companies.

* Copy the folder of the unzipped file with its directory structure into the `www` directory.  The uploading process will take a while.  Be sure to set your program to binary mode (how to do this varies by program), or the upload won't work correctly!

* Once the files are successfully uploaded, you should change some permissions for these directories:

	* The `/limesurvey/tmp` directory and all of its sub-directories and files are used for imports & uploads and should be set to _Read & Write_ for your webserver.

	* The `/limesurvey/upload/` directory and all its sub-directories and files must also have _Read & Write_ for your webserver in order to enable picture and media files upload.

	* The `/limesurvey/application/config/` directory also needs _Read & Write_ permissions for your web server.

	* The other directories can be set to Read Only or in Linux/Unix. You may wish to set the permissions on each file within the `/limesurvey/admin` directory to _Read Only_.

<p></p>

* You may do this through your FTP program by right clicking on the target directories and going to _file permissions_. Then you can check or uncheck the permissions.

* For more information on Linux permissions, please see http://www.linux.com/learn/tutorials/309527-understanding-linux-file-permissions

* LimeSurvey needs the **username** and **password** of a user of the database that it will use. So make sure that you have this information and that you use a user with the following permissions:

	* **MySQL:** SELECT, CREATE, INSERT, UPDATE, DELETE, ALTER, DROP, INDEX
	* **PostgreSQL:** SELECT, INSERT, UPDATE, DELETE, TRUNCATE, TRIGGER

<p></p>

* Next, Limesurvey will need an empty database and a user.  You can create them (and set user permissions) through the control panel of the server, likely through a "MYSQL Databases" or similar button.

* The user Limesurvey will use will need a certain set of permissions.  Depending on the database type you're using, they'll need: 
* * **MySQL:** SELECT, CREATE, INSERT, UPDATE, DELETE, ALTER, DROP, INDEX
* **PostgreSQL:** SELECT, INSERT, UPDATE, DELETE, TRUNCATE, TRIGGER

* Now you can start to run the installation script by going to the admin folder in your browser, or  http://www.yourdomain.org/limesurvey/admin.  If the installation is set up under a subdomain, be sure to replace "www." with "yoursubdomain."

* If the configurations are all correct you would see the installation screen. follow the instructions to start installing.
* LimeSurvey needs to connect to the database to set up tables inside it & begin using it. If you have a dedicated database created on your server beforehand (as described above), you can type in the name. Otherwise, LimeSurvey will create a new database under that name. 

* You will need the **username** and **password** for the desired user of your database during this process.

* After the installation is done, you can use the same link, http://www.yourdomain.org/limesurvey/admin, to access your LimeSurvey login page!  Use the same login and password as you did for the database user.


