# Codeigniter database diff sql statement generator 

This tool is veryusell full if you are stuck in condition where you made changes on database on localhost and after the testing you want to make same changes on production, just connect both database as src and dist and run the script, it will give you the output sql commands poisbally alter table commands, now copy this command and exicute in production database. 

# 1 configure basics
file  application/config/config.php

# 2 configure database
file application/config/database.php 
add source and target database

# 3 Run 
open the URL yoururl.com/ci-database-diff-generator/

#PR Submit from vaimeo1

#PR Resolved vaimeo1
