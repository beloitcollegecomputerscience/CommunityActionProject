#Community Action
####Beloit College Database Capstone project

---
##Build Requirements

1. Node.js & NPM - [http://nodejs.org/](http://nodejs.org/)
2. Run `npm update -g` & `npm cache clear`
3. Run `npm install -g bower grunt grunt-bower-cli`
4. Install git [http://git-scm.com/downloads] 
5. If you are on Windows during the install check yes to command line UNIX commands and GIT, it will make your life much easier.

##Steps to install

1. Clone Repo
2. Run the following in a terminal window at the projects root
  1. Install the npm modules: `$ npm install`
  2. Install the bower components: `$ bower install`
  3. Start Grunt `$ grunt serve`
3. Visit the application in your browser at [http://localhost:9000](http://localhost:9000)

4. If you want to run tests run `$ grunt test` in project directory
  * We are using phantom js browser for running tests. It is a headless browser so you have to look in the terminal to see if any test failed

5. To Build production version run `$ grunt build` 
6. Code to your hearts content! :computer: :heart_eyes:
