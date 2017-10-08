# Error Log Dashboard Application
Visualises error logs stored in database by using well-arranged charts.

Application features:
- multiple projects
- filtering errors
- marking errors as resolved
- hiding resolved and selected muted errors from the dashboard

Makes it easier and saves time to analyze error logs and helps to fix bugs.

Frontend of the application is written in Vue.js and backend in PHP.

![alt text](https://martyhora.cz/img/portfolio/thumbnails/3.png)

# Installation

To run the project you will need Apache, PHP, Composer, MySQL and NodeJs installed.

- clone project by running ```git clone https://github.com/martyhora/error-log-dashboard.git``` into your DocumentRoot path
- create a MySQL database and execute sql script ```api/v1/sql/error-log-report.sql```
- copy file ```api/v1/config.local.php``` into ```api/v1/config.php``` and setup your projects and database connection
- change folder ```cd api/v1``` and run ```composer install```
- run ```npm i``` in the project root
- run ```webpack --watch``` to compile changes in JS a LESS files
- open the project in the browser
