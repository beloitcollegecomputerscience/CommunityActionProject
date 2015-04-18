* To ensure that limesurvey could work properly, you may want to choose one of the following hosting service compatible with limesurvey:

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

(This list contains compatible services in the US, see this link for the full list : https://manual.limesurvey.org/LimeSurvey-compatible_hosting_companies)

* Some of these service may offer one click installation. Use the following instructions if you want to install manually.

* There are some additional requirements your server has to meet. These will also be checked automatically during the installation. If you are using one of the compatible hosting sites then it is likely that you are ok but if you are using another server you may want to check this link for details: https://manual.limesurvey.org/Installation

* To start, first download the latest Limesurvey zip package from this link: https://www.limesurvey.org/en/download
Unzip the package into a dedicated folder.
Then you need to upload the files to your web server. To do this you need to use a FTP program such as FileZilla or CyberDuck.

* Using the FTP program, connect to your webserver. To do this you will need to know your **server name**, and the **username** and **password** for your server. These information should be available to you after you register for one of the hosting companies.

* Copy the dedicated folder of the unzipped file with its directory structure into the `www` directory.  The uploading process will take a while.

* When the files are successfully uploaded, you may need to change some permissions for some directories:

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

* You can create the user and set their permission via the control panel of your hosting site. You will have access to your control panel after you register with a hosting company and you may be able to manage your database users under the database section (the specific layout varies between hosts).

* Now you can start to run the installation script by going to http://www.yourdomain.org/limesurvey/admin

* If the configurations are all correct you would see the installation screen. follow the instruction and start installing.

* LimeSurvey needs to connect to create its database and the tables inside it. If you have a dedicated database created before hand on your server, you could type in the name. Otherwise if the name you put does not exist in the database yet, LimeSurvey will create a new database under that name. 

* You will need the **username** and **password** for the desired user of your database during this process.
after the installation is done, you can use the same link, http://www.yourdomain.org/limesurvey/admin, to access your LimeSurvey login page!


