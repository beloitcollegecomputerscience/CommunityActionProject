

#!/bin/bash
# Script for Automatically downloading and doing a new install of limesurvey
# in proper folder
# Ellery Addington-White 5/3/15

#Get passed variables
GROUPNAME=$1;


#Create folder to do an install
mkdir  ~/public_html/${GROUPNAME};

#Copy our install version of lime survey to newly created folder
cp ~/baseInstallLimeSurveyFiles ~/public_html/${GROUPNAME};

### Set all necesarry permisions in new install ###

#Set Permisions for tmp/ used for imports and uploads
chmod -R 755  ~/public_html/${GROUPNAME}/tmp/;
chown -R apache  ~/public_html/${GROUPNAME}tmp/;


#Set Permisions for upload/ used to enable media upload
chmod -R 755  ~/public_html/${GROUPNAME}/upload/;
chown -R apache  ~/public_html/${GROUPNAME}/upload/;

#Set Permisions for application/config
chmod -R 755  ~/public_html/${GROUPNAME}application/config;
chown -R apache  ~/public_html/${GROUPNAME}/application/config;


### Create New DataBase and user ###

#Generate Password
#Note: Need to have makepasswd installed run 'apt-get install makepasswd'
echo makepasswd --chars 16 | newPassword;

#execute sql command
#Note: must have your .my.conf set up properly
mysql -h localhost -e "create database ${GROUPNAME}; GRANT ALL PRIVILEGES ON ${GROUPNAME}.* TO ${GROUPNAME}_user@localhost IDENTIFIED BY '${newPassword}'";

#Echo out pass word for sql user for now We should probably catch this some where
echo ${newPassword};