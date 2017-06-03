# Error Log Dashboard Application
Application for visualising error logs stored in database by using well-arranged charts. Supports multiple projects, filtering errors and resolving errors. Makes it easier to analyze error logs and helps to fix bugs.

# Installation

To run the project you will need Apache, PHP, MySQL and NodeJs installed.

- clone project by running ```git clone https://github.com/martyhora/error-log-dashboard.git``` into your DocumentRoot path
- create a MySQL database and execute sql script ```api/v1/sql/error-log-report.sql```
- copy file ```api/v1/config.local.php``` into ```api/v1/config.php``` and setup your projects and database connection
- run ```npm i``` in the project root
- run ```webpack --watch``` to compile changes in JS a LESS files
- open the project in the browser
